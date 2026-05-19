<?php

namespace App\Filament\Forms;

use App\Forms\Components\ImageTileUpload;

/**
 * Thin factory — keeps the same callsite signature used across Resources
 * (IconUpload::make('icon', 'Icon', 'game-icons')) but returns the new
 * single-component widget that bundles preview + dropzone together.
 *
 * @see ImageTileUpload  — the actual Filament component.
 */
class IconUpload
{
    public static function make(string $name, string $label, string $directory, int $maxKb = 2048): ImageTileUpload
    {
        return ImageTileUpload::for($name, $label, $directory)
            ->maxSize($maxKb)
            ->columnSpanFull();
    }
}
