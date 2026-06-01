<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use XMLReader;

class ExtractWpSlugsFromXml extends Command
{
    protected $signature = 'wp:extract-slugs
                            {xml : Absolute path to the WordPress XML export}
                            {--out= : Output CSV path (default: same folder as input, named product-slugs.csv)}
                            {--include-variations : Also export product_variation rows (rarely needed)}';

    protected $description = 'Extract product (ID, post_name, post_title, post_status) from a WordPress WXR XML export into a CSV the importer can merge.';

    public function handle(): int
    {
        $xmlPath = $this->argument('xml');

        if (! file_exists($xmlPath)) {
            $this->error("File not found: {$xmlPath}");
            return Command::FAILURE;
        }

        $outPath = $this->option('out') ?: dirname($xmlPath) . DIRECTORY_SEPARATOR . 'product-slugs.csv';

        $allowedTypes = ['product'];
        if ($this->option('include-variations')) {
            $allowedTypes[] = 'product_variation';
        }

        $this->info("Reading: {$xmlPath}");
        $this->info('Allowed post_types: ' . implode(', ', $allowedTypes));

        $reader = new XMLReader();
        if (! $reader->open($xmlPath)) {
            $this->error('Failed to open XML reader.');
            return Command::FAILURE;
        }

        // Use streaming XMLReader so we don't load 32 MB into memory
        $out = fopen($outPath, 'w');
        fputcsv($out, ['ID', 'post_name', 'post_title', 'post_status']);

        $count   = 0;
        $skipped = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'item') {
                continue;
            }

            // expandSimpleXml() returns the full <item>...</item> subtree as SimpleXMLElement
            $node = $reader->expand();
            if (! $node) continue;

            $dom = new \DOMDocument();
            $dom->appendChild($dom->importNode($node, true));
            $xml = simplexml_import_dom($dom);

            // wp:post_type, wp:post_id, wp:post_name, wp:status live in the wp: namespace
            $wp = $xml->children('wp', true);

            $postType = (string) ($wp->post_type ?? '');
            if (! in_array($postType, $allowedTypes, true)) {
                $skipped++;
                continue;
            }

            $id     = trim((string) ($wp->post_id ?? ''));
            $slug   = trim((string) ($wp->post_name ?? ''));
            $status = trim((string) ($wp->status   ?? ''));
            $title  = trim((string) ($xml->title   ?? ''));

            if ($id === '' || $slug === '') {
                $skipped++;
                continue;
            }

            fputcsv($out, [$id, $slug, $title, $status]);
            $count++;
        }

        fclose($out);
        $reader->close();

        $this->info("Wrote: {$outPath}");
        $this->info("Products exported: {$count}");
        $this->info("Items skipped (other post_types or missing data): {$skipped}");

        return Command::SUCCESS;
    }
}
