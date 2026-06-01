<?php

namespace App\Forms\Components;

use Filament\Forms\Components\FileUpload;

/**
 * Image upload field with an integrated WordPress-style preview tile.
 *
 * Extends Filament's FileUpload but renders through a custom Blade view that
 * wraps the preview AND the Filepond dropzone in one cohesive widget. Filepond
 * still handles the actual upload (we keep all of its Alpine/Livewire wiring
 * intact); we only replace what the user sees so the saved image is visible
 * in edit mode — which Filepond's stock preview is unreliable about.
 *
 * Usage:
 *   ImageTileUpload::for('icon', 'Game Icon', 'game-icons')
 */
class ImageTileUpload extends FileUpload
{
    protected string $view = 'forms.components.image-tile-upload';

    protected string $tileLabel = 'Image';

    /**
     * Sensible defaults so each Resource call stays terse: image-only mime
     * types, public disk, live state, 2 MB cap. Override per-field as needed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $idleLabel =
            '<svg class="upload-idle-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' .
                '<path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5"/>' .
                '<path d="M16.5 7.5l-4.5-4.5-4.5 4.5M12 3v13.5"/>' .
            '</svg>' .
            '<span class="upload-idle-label">' .
                '<strong>Drop image here</strong>' .
                '<span>or <span class="filepond--label-action">browse files</span></span>' .
            '</span>';

        $this->disk('public')
            ->live()
            ->panelLayout('integrated')
            ->placeholder($idleLabel)
            ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg'])
            ->mimeTypeMap([
                'image/svg+xml' => 'svg',
                'image/png'     => 'png',
                'image/jpeg'    => 'jpg',
            ])
            ->maxSize(2048);
    }

    /**
     * Override Filament's strict `mimetypes:` validation with an extension-based
     * `mimes:` rule. PHP's finfo() frequently mis-detects SVG content as
     * text/xml or application/xml — that fails the default rule even though
     * the file is a legitimate SVG. `mimes:` checks via the filename + Laravel's
     * own mapping, which knows .svg → image/svg+xml.
     */
    public function getValidationRules(): array
    {
        return collect(parent::getValidationRules())
            ->map(fn ($rule) => (is_string($rule) && str_starts_with($rule, 'mimetypes:'))
                ? 'mimes:svg,png,jpg,jpeg'
                : $rule)
            ->all();
    }

    /**
     * Shorthand factory — preset name, label, and target directory in one call.
     * Anything else is set via the usual fluent chain.
     */
    public static function for(string $name, string $label, string $directory): static
    {
        return static::make($name)
            ->label($label)
            ->tileLabel($label)
            ->directory($directory);
    }

    public function tileLabel(string $label): static
    {
        $this->tileLabel = $label;
        return $this;
    }

    public function getTileLabel(): string
    {
        return $this->tileLabel;
    }

    /**
     * URL for the current preview image — handles three state shapes:
     *  - Livewire TemporaryUploadedFile (in-progress upload, before save)
     *  - Saved disk path string (post-save, e.g. "game-icons/abc.png")
     *  - Legacy rooted paths from seeds (e.g. "/img/icons/header_cat01.svg")
     */
    public function getPreviewUrl(): ?string
    {
        $state = $this->getState();
        if (is_array($state)) {
            $state = reset($state) ?: null;
        }

        if (is_object($state) && method_exists($state, 'temporaryUrl')) {
            try {
                return $state->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($state) && $state !== '') {
            return (str_starts_with($state, '/') || str_starts_with($state, 'http'))
                ? $state
                : asset('storage/' . ltrim($state, '/'));
        }

        return null;
    }

    public function getPreviewFilename(): ?string
    {
        $state = $this->getState();
        if (is_array($state)) {
            $state = reset($state) ?: null;
        }

        if (is_object($state) && method_exists($state, 'getClientOriginalName')) {
            return $state->getClientOriginalName();
        }

        if (is_string($state) && $state !== '') {
            return basename($state);
        }

        return null;
    }
}
