<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Component;

class MediaImagePicker extends Component
{
    protected string $view = 'filament.forms.components.media-image-picker';

    protected ?string $buttonLabel = 'Select image';

    protected ?int $previewWidth = 300;

    protected ?int $previewHeight = 200;

    public static function make(?string $name = null): self
    {
        return parent::make($name);
    }

    public function buttonLabel(string $label): self
    {
        $this->buttonLabel = $label;
        return $this;
    }

    public function previewWidth(int $width): self
    {
        $this->previewWidth = $width;
        return $this;
    }

    public function previewHeight(int $height): self
    {
        $this->previewHeight = $height;
        return $this;
    }

    public function getButtonLabel(): string
    {
        return $this->buttonLabel ?? 'Select image';
    }

    public function getPreviewWidth(): int
    {
        return $this->previewWidth ?? 300;
    }

    public function getPreviewHeight(): int
    {
        return $this->previewHeight ?? 200;
    }
}
