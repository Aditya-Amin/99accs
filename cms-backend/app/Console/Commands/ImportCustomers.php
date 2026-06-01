<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportCustomers extends Command
{
    /**
     * Usage:
     *   php artisan customers:import storage/imports/legacy.csv
     *   php artisan customers:import storage/imports/legacy.csv --dry-run
     *   php artisan customers:import storage/imports/legacy.csv --map=email:user_email,first_name:fname,last_name:lname
     *
     * Idempotent: matches existing rows by legacy_id first, falls back to email.
     * Re-running the same import updates existing rows without creating duplicates.
     *
     * Every imported customer is flagged is_legacy=true + must_reset_password=true
     * so their first login attempt triggers the LEGACY_PASSWORD_RESET_REQUIRED flow.
     * No emails are sent at import time.
     */
    protected $signature = 'customers:import
                            {file : Path to the CSV file (absolute or relative to project root)}
                            {--dry-run : Parse and validate without writing to the database}
                            {--map= : Column mapping overrides, e.g. email:user_email,phone:tel}
                            {--delimiter=, : CSV delimiter character (default: ,)}';

    protected $description = 'Import legacy customers from a CSV file into the customers table';

    /**
     * Default column mapping. Override per-import with --map=target:source,...
     * Keys are Customer columns; values are the CSV header to read from.
     */
    private array $defaultMap = [
        'email'      => 'email',
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
        'phone'      => 'phone',
        'legacy_id'  => 'id',
        'created_at' => 'created_at',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_readable($file)) {
            $file = base_path($file);
        }
        if (! is_readable($file)) {
            $this->error("File not found or unreadable: {$this->argument('file')}");
            return self::FAILURE;
        }

        $map = $this->resolveMapping();
        $this->line('Column mapping: ' . json_encode($map));

        $delimiter = $this->option('delimiter') ?: ',';
        $dryRun    = (bool) $this->option('dry-run');

        $handle = fopen($file, 'r');
        if (! $handle) {
            $this->error("Could not open file for reading.");
            return self::FAILURE;
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (! $headers) {
            $this->error("CSV appears to be empty.");
            fclose($handle);
            return self::FAILURE;
        }
        $headers = array_map('trim', $headers);
        // Validate required columns exist
        $missing = collect($map)->reject(fn ($col) => in_array($col, $headers, true))->values();
        if ($missing->isNotEmpty()) {
            $this->warn('Headers in CSV: ' . implode(', ', $headers));
            $this->error('Missing expected columns in CSV: ' . $missing->implode(', '));
            $this->line('Adjust the mapping with --map=target:source pairs and re-run.');
            fclose($handle);
            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];
        $rowNum  = 1;

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Importing from {$file}");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
                if (count($row) === 1 && trim($row[0]) === '') continue;

                $assoc = array_combine($headers, array_pad($row, count($headers), null));
                if (! $assoc) {
                    $errors[] = "Row {$rowNum}: column count mismatch";
                    $skipped++;
                    continue;
                }

                $payload = $this->buildPayload($assoc, $map);
                if (! $payload['email']) {
                    $errors[] = "Row {$rowNum}: missing email";
                    $skipped++;
                    continue;
                }

                // Match priority: legacy_id (preferred — stable across email changes),
                // then email (fallback for legacy systems without per-user IDs).
                $existing = null;
                if (! empty($payload['legacy_id'])) {
                    $existing = Customer::where('legacy_id', $payload['legacy_id'])->first();
                }
                if (! $existing) {
                    $existing = Customer::where('email', $payload['email'])->first();
                }

                if ($existing) {
                    if (! $dryRun) {
                        $existing->fill($payload)->save();
                    }
                    $updated++;
                } else {
                    // Random unguessable placeholder password — user can never log in with
                    // it. The must_reset_password flag forces the reset flow on first attempt.
                    $payload['password']            = Hash::make(Str::random(64));
                    $payload['is_legacy']           = true;
                    $payload['must_reset_password'] = true;
                    $payload['migrated_at']         = now();

                    if (! $dryRun) {
                        Customer::create($payload);
                    }
                    $created++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry-run complete — no changes written.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted: ' . $e->getMessage());
            fclose($handle);
            return self::FAILURE;
        }

        fclose($handle);

        $this->newLine();
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Skipped row details:');
            foreach (array_slice($errors, 0, 20) as $msg) {
                $this->line('  ' . $msg);
            }
            if (count($errors) > 20) {
                $this->line('  ...and ' . (count($errors) - 20) . ' more');
            }
        }

        return self::SUCCESS;
    }

    private function resolveMapping(): array
    {
        $map = $this->defaultMap;
        $override = $this->option('map');
        if (! $override) return $map;

        foreach (explode(',', $override) as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) !== 2) continue;
            [$target, $source] = array_map('trim', $parts);
            if ($target && $source) {
                $map[$target] = $source;
            }
        }
        return $map;
    }

    private function buildPayload(array $row, array $map): array
    {
        $get = fn (string $key) => isset($map[$key], $row[$map[$key]]) ? trim((string) $row[$map[$key]]) : null;

        $payload = [
            'email'      => $get('email') ? strtolower($get('email')) : null,
            'first_name' => $get('first_name') ?: '',
            'last_name'  => $get('last_name') ?: '',
            'phone'      => $get('phone') ?: null,
            'legacy_id'  => $get('legacy_id') ?: null,
        ];

        if ($createdAt = $get('created_at')) {
            try {
                $payload['created_at'] = Carbon::parse($createdAt);
            } catch (\Throwable) {
                // Bad timestamp — let Eloquent set created_at = now() on insert.
            }
        }

        return $payload;
    }
}
