<?php

namespace App\Console\Commands;

use App\Models\CuratorMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLegacyMedia extends Command
{
    protected $signature = 'media:import-legacy
                            {--disk=public : Storage disk to scan}
                            {--dry-run : List files that would be imported without inserting}';

    protected $description = 'Import files already on disk into the Curator media library';

    private const IMAGE_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml',
        'image/gif', 'image/avif', 'image/bmp', 'image/tiff',
    ];

    private const MIME_TYPE_MAP = [
        'image/jpeg'   => 'image',
        'image/png'    => 'image',
        'image/webp'   => 'image',
        'image/svg+xml'=> 'image',
        'image/gif'    => 'image',
        'image/avif'   => 'image',
        'image/bmp'    => 'image',
        'image/tiff'   => 'image',
        'application/pdf' => 'document',
    ];

    public function handle(): int
    {
        $disk = $this->option('disk');
        $dryRun = $this->option('dry-run');

        $files = Storage::disk($disk)->allFiles();
        $skip = ['.gitignore', '.gitkeep'];

        $files = array_filter($files, fn ($f) => !in_array(basename($f), $skip, true));
        $files = array_values($files);

        $this->info(sprintf('Found %d file(s) on the "%s" disk.', count($files), $disk));

        $existing = CuratorMedia::pluck('path')->flip();

        $imported = 0;
        $skipped  = 0;

        foreach ($files as $path) {
            if ($existing->has($path)) {
                $this->line("  SKIP (already in library): $path");
                $skipped++;
                continue;
            }

            $mime = $this->mimeType($disk, $path);
            if (! $mime) {
                $this->warn("  SKIP (unknown mime): $path");
                $skipped++;
                continue;
            }

            $type = self::MIME_TYPE_MAP[$mime] ?? null;
            if (! $type) {
                $this->warn("  SKIP (unsupported type $mime): $path");
                $skipped++;
                continue;
            }

            $ext       = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $filename  = pathinfo($path, PATHINFO_FILENAME);
            $directory = ltrim(dirname($path), './');
            $size      = Storage::disk($disk)->size($path);

            [$width, $height] = $this->imageDimensions($disk, $path, $mime);

            if ($dryRun) {
                $this->line("  WOULD IMPORT: $path (dir=$directory, ext=$ext, ${size}B)");
                $imported++;
                continue;
            }

            CuratorMedia::create([
                'disk'       => $disk,
                'directory'  => $directory ?: 'media',
                'visibility' => 'public',
                'name'       => $filename,
                'path'       => $path,
                'width'      => $width,
                'height'     => $height,
                'size'       => $size,
                'type'       => $type,
                'ext'        => $ext,
                'alt'        => null,
                'title'      => null,
            ]);

            $this->line("  IMPORTED: $path");
            $imported++;
        }

        $this->newLine();
        $label = $dryRun ? 'Would import' : 'Imported';
        $this->info("$label $imported file(s), skipped $skipped.");

        return self::SUCCESS;
    }

    private function mimeType(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->mimeType($path) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function imageDimensions(string $disk, string $path, string $mime): array
    {
        if (! in_array($mime, self::IMAGE_MIMES, true) || $mime === 'image/svg+xml') {
            return [null, null];
        }

        try {
            $fullPath = Storage::disk($disk)->path($path);
            $info = @getimagesize($fullPath);
            return $info ? [$info[0], $info[1]] : [null, null];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
