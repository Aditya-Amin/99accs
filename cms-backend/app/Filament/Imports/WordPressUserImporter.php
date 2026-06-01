<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class WordPressUserImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            // ── Identity ──────────────────────────────────────────────────────
            ImportColumn::make('legacy_id')
                ->label('WP User ID')
                ->requiredMapping()
                ->guess(['ID', 'id', 'customer_id']),

            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->rules(['required', 'email:rfc']),

            // ── Profile ───────────────────────────────────────────────────────
            ImportColumn::make('first_name')
                ->label('First Name')
                ->guess(['first_name', 'billing_first_name']),

            ImportColumn::make('last_name')
                ->label('Last Name')
                ->guess(['last_name', 'billing_last_name']),

            ImportColumn::make('phone')
                ->label('Phone')
                ->guess(['billing_phone', 'phone']),

            // ── WP-specific: not direct DB columns, read in afterFill ─────────
            // display_name: fallback when first_name/last_name are empty
            ImportColumn::make('display_name')
                ->label('Display Name')
                ->guess(['display_name']),

            // user_pass: two WP hash formats — handled with custom logic
            ImportColumn::make('raw_password')
                ->label('Password Hash (WP)')
                ->guess(['user_pass', 'password']),

            // user_registered → our created_at
            ImportColumn::make('registered_at')
                ->label('Registered Date')
                ->guess(['user_registered']),

            // ── Activity ──────────────────────────────────────────────────────
            ImportColumn::make('last_login_at')
                ->label('Last Active (WooCommerce)')
                ->guess(['wc_last_active', 'last_update']),

            // ── Spend history (from WooCommerce aggregates) ───────────────────
            ImportColumn::make('total_spent')
                ->label('Total Spent ($)')
                ->numeric()
                ->guess(['total_spent']),

            ImportColumn::make('legacy_orders_count')
                ->label('Order Count')
                ->castStateUsing(fn (?string $state): int => (int) $state)
                ->guess(['orders']),
        ];
    }

    // ── Record resolution ─────────────────────────────────────────────────────

    public function resolveRecord(): ?Customer
    {
        $email    = trim($this->data['email'] ?? '');
        $legacyId = trim((string) ($this->data['legacy_id'] ?? ''));

        if (empty($email)) {
            return null; // Skip rows without an email address
        }

        // Skip rows that are already in our database (idempotent re-imports).
        // Returning null = silent skip (not counted as failure).
        if (Customer::where('email', $email)->exists()) {
            return null;
        }

        if ($legacyId && Customer::where('legacy_id', $legacyId)->exists()) {
            return null;
        }

        return new Customer();
    }

    // ── Post-fill hooks ───────────────────────────────────────────────────────

    protected function afterFill(): void
    {
        $this->handlePassword();
        $this->handleNameFallback();
        $this->handleDates();

        // Always mark imported rows as legacy
        $this->record->is_legacy  = true;
        $this->record->migrated_at = now();

        // These ImportColumns are virtual mapping helpers — they exist so the user
        // can map CSV columns in the Filament UI and so $this->data carries them
        // into our handlePassword/handleNameFallback/handleDates() methods above.
        // They are NOT real DB columns on `customers`, so we must unset them from
        // the record's attributes before save() runs.
        unset($this->record->display_name);
        unset($this->record->raw_password);
        unset($this->record->registered_at);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function handlePassword(): void
    {
        $raw = trim($this->data['raw_password'] ?? '');

        if ($raw && str_starts_with($raw, '$wp$2y$')) {
            // WordPress 6.8+ stores bcrypt with a "$wp" prefix: "$wp$2y$10$...".
            // The prefix is 3 bytes ("$wp"); stripping them leaves the standard
            // "$2y$10$..." Laravel BCrypt hash. (Stripping 4 also eats the "$" of
            // "$2y$", producing an invalid hash that makes Hash::check throw.)
            // We preserve the hash, but per policy every imported account must
            // set a fresh password before its first login (see below).
            $bcrypt = substr($raw, 3);

            // setRawAttributes bypasses the 'hashed' cast so we don't double-hash.
            $this->record->setRawAttributes(
                array_merge($this->record->getAttributes(), ['password' => $bcrypt]),
                false
            );
        } else {
            // phpass ($P$...) or unrecognised format — Laravel cannot verify these.
            // Set a throwaway random password.
            $this->record->password = Str::random(64);
        }

        // Policy: ALL imported accounts must reset before they can log in, so a
        // migrated user can never sign in with their old (untrusted) password.
        // The login endpoint returns LEGACY_PASSWORD_RESET_REQUIRED for these.
        $this->record->must_reset_password = true;
    }

    private function handleNameFallback(): void
    {
        $firstName = trim($this->record->first_name ?? '');
        $lastName  = trim($this->record->last_name ?? '');

        if (!empty($firstName)) {
            return; // Already set by column mapping — nothing to do
        }

        // Try display_name: split on the first whitespace boundary
        $display = trim($this->data['display_name'] ?? '');
        if ($display) {
            $parts = preg_split('/\s+/u', $display, 2);
            $this->record->first_name = $parts[0];
            if (empty($lastName)) {
                $this->record->last_name = $parts[1] ?? '';
            }
            return;
        }

        // Last resort: leave first_name as empty string (the column allows null)
        $this->record->first_name = '';
    }

    private function handleDates(): void
    {
        // user_registered → created_at
        // WordPress stores 0000-00-00 00:00:00 for system/guest accounts.
        // Carbon::parse() converts that to year -0001, which MySQL rejects (error 1292).
        $registered = null;
        if (!empty($this->data['registered_at'])) {
            try {
                $parsed = Carbon::parse($this->data['registered_at']);
                if ($parsed->year > 1900) {
                    $registered = $parsed;
                }
            } catch (\Throwable) {}
        }

        $this->record->created_at        = $registered ?? now();
        $this->record->email_verified_at = $registered ?? now();

        // wc_last_active → last_login_at
        if (!empty($this->data['last_login_at'])) {
            try {
                $parsed = Carbon::parse($this->data['last_login_at']);
                if ($parsed->year > 1900) {
                    $this->record->last_login_at = $parsed;
                }
            } catch (\Throwable) {}
        }
    }

    // ── Completion notification ───────────────────────────────────────────────

    public static function getCompletedNotificationBody(Import $import): string
    {
        $success = number_format($import->successful_rows);
        $body    = "WordPress user import completed — {$success} customers imported.";

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failed) . ' rows failed (duplicate email or invalid data) — download the failure report to review.';
        }

        return $body;
    }
}
