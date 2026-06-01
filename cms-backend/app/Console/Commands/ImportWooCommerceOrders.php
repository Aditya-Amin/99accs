<?php

namespace App\Console\Commands;

use App\Jobs\ImportWooCommerceOrders as ImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportWooCommerceOrders extends Command
{
    protected $signature = 'import:wc-orders
                            {file : Absolute path to the WooCommerce orders CSV export}
                            {--dry-run : Validate and count rows without writing to the database}
                            {--queue=default : Queue name to dispatch the job on}';

    protected $description = 'Import a WooCommerce orders CSV export into the orders + order_items tables';

    public function handle(): int
    {
        $file   = $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        $queue  = $this->option('queue');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        // Count rows so the user knows what they're importing
        $rowCount = max(0, $this->countCsvRows($file) - 1); // subtract header
        $this->info("Found {$rowCount} order rows.");

        if ($dryRun) {
            $this->warn('--dry-run active: no data will be written.');
        }

        // Copy the file into Laravel storage so the queued job can read it
        // regardless of which worker process picks it up.
        $storageName = 'imports/wc_orders_' . now()->format('YmdHis') . '_' . uniqid() . '.csv';
        Storage::put($storageName, fopen($file, 'rb'));

        $this->info("Stored as: storage/app/{$storageName}");
        $this->info("Dispatching import job on queue [{$queue}]...");

        ImportJob::dispatch($storageName, $dryRun)->onQueue($queue);

        $this->info('Job dispatched successfully.');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Set QUEUE_CONNECTION=database in .env (if not already)');
        $this->line('  2. Run: php artisan queue:work --queue=' . $queue);
        $this->line('  3. Monitor failures: storage/logs/laravel.log');

        return Command::SUCCESS;
    }

    private function countCsvRows(string $path): int
    {
        $count  = 0;
        $handle = fopen($path, 'r');
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }
        fclose($handle);
        return $count;
    }
}
