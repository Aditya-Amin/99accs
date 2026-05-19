@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    $id = $getId();
    $imageCropAspectRatio = $getImageCropAspectRatio();
    $imageResizeTargetHeight = $getImageResizeTargetHeight();
    $imageResizeTargetWidth = $getImageResizeTargetWidth();
    $isAvatar = $isAvatar();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasImageEditor = $hasImageEditor();
    $hasCircleCropper = $hasCircleCropper();

    $alignment = $getAlignment() ?? Alignment::Start;
    if (! $alignment instanceof Alignment) {
        $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    }

    $previewUrl = $getPreviewUrl();
    $previewFilename = $getPreviewFilename();
    $tileLabel = $getTileLabel();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div class="image-tile-upload">
        {{-- ── Preview tile (rendered server-side, always visible) ───────── --}}
        <div class="image-tile-upload__preview">
            @if ($previewUrl)
                <div class="image-tile-upload__frame">
                    <img src="{{ $previewUrl }}" alt="{{ $tileLabel }}" loading="lazy">
                </div>
                <div class="image-tile-upload__meta">
                    <span class="image-tile-upload__label">Current {{ $tileLabel }}</span>
                    @if ($previewFilename)
                        <span class="image-tile-upload__filename">{{ $previewFilename }}</span>
                    @endif
                </div>
            @else
                <div class="image-tile-upload__frame image-tile-upload__frame--empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <path d="M21 15l-5-5L5 21"></path>
                    </svg>
                </div>
                <div class="image-tile-upload__meta">
                    <span class="image-tile-upload__label">No {{ strtolower($tileLabel) }} yet</span>
                    <span class="image-tile-upload__filename">Drop a file below or click Browse — PNG, JPG, or SVG.</span>
                </div>
            @endif
        </div>

        {{-- ── Filepond dropzone (kept verbatim from Filament so Alpine + Livewire wiring stays intact) ── --}}
        <div
            @if (FilamentView::hasSpaMode())
                {{-- format-ignore-start --}}x-load="visible || event (ax-modal-opened)"{{-- format-ignore-end --}}
            @else
                x-load
            @endif
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
            x-data="fileUploadFormComponent({
                    acceptedFileTypes: @js($getAcceptedFileTypes()),
                    imageEditorEmptyFillColor: @js($getImageEditorEmptyFillColor()),
                    imageEditorMode: @js($getImageEditorMode()),
                    imageEditorViewportHeight: @js($getImageEditorViewportHeight()),
                    imageEditorViewportWidth: @js($getImageEditorViewportWidth()),
                    deleteUploadedFileUsing: async (fileKey) => {
                        return await $wire.deleteUploadedFile(@js($statePath), fileKey)
                    },
                    getUploadedFilesUsing: async () => {
                        return await $wire.getFormUploadedFiles(@js($statePath))
                    },
                    hasImageEditor: @js($hasImageEditor),
                    hasCircleCropper: @js($hasCircleCropper),
                    canEditSvgs: @js($canEditSvgs()),
                    isSvgEditingConfirmed: @js($isSvgEditingConfirmed()),
                    confirmSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.confirmation')),
                    disabledSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.disabled')),
                    imageCropAspectRatio: @js($imageCropAspectRatio),
                    imagePreviewHeight: @js($getImagePreviewHeight()),
                    imageResizeMode: @js($getImageResizeMode()),
                    imageResizeTargetHeight: @js($imageResizeTargetHeight),
                    imageResizeTargetWidth: @js($imageResizeTargetWidth),
                    imageResizeUpscale: @js($getImageResizeUpscale()),
                    isAvatar: @js($isAvatar),
                    isDeletable: @js($isDeletable()),
                    isDisabled: @js($isDisabled),
                    isDownloadable: @js($isDownloadable()),
                    isMultiple: @js($isMultiple()),
                    isOpenable: @js($isOpenable()),
                    isPasteable: @js($isPasteable()),
                    isPreviewable: @js($isPreviewable()),
                    isReorderable: @js($isReorderable()),
                    itemPanelAspectRatio: @js($getItemPanelAspectRatio()),
                    loadingIndicatorPosition: @js($getLoadingIndicatorPosition()),
                    locale: @js(app()->getLocale()),
                    panelAspectRatio: @js($getPanelAspectRatio()),
                    panelLayout: @js($getPanelLayout()),
                    placeholder: @js($getPlaceholder()),
                    maxFiles: @js($getMaxFiles()),
                    maxSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
                    minSize: @js(($size = $getMinSize()) ? "{$size}KB" : null),
                    mimeTypeMap: @js($getMimeTypeMap()),
                    maxParallelUploads: @js($getMaxParallelUploads()),
                    removeUploadedFileUsing: async (fileKey) => {
                        return await $wire.removeFormUploadedFile(@js($statePath), fileKey)
                    },
                    removeUploadedFileButtonPosition: @js($getRemoveUploadedFileButtonPosition()),
                    reorderUploadedFilesUsing: async (files) => {
                        return await $wire.reorderFormUploadedFiles(@js($statePath), files)
                    },
                    shouldAppendFiles: @js($shouldAppendFiles()),
                    shouldOrientImageFromExif: @js($shouldOrientImagesFromExif()),
                    shouldTransformImage: @js($imageCropAspectRatio || $imageResizeTargetHeight || $imageResizeTargetWidth),
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    uploadButtonPosition: @js($getUploadButtonPosition()),
                    uploadingMessage: @js($getUploadingMessage()),
                    uploadProgressIndicatorPosition: @js($getUploadProgressIndicatorPosition()),
                    uploadUsing: (fileKey, file, success, error, progress) => {
                        $wire.upload(
                            `{{ $statePath }}.${fileKey}`,
                            file,
                            () => {
                                success(fileKey)
                            },
                            error,
                            (progressEvent) => {
                                progress(true, progressEvent.detail.progress, 100)
                            },
                        )
                    },
                })"
            wire:ignore
            wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.{{
                substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)
            }}"
            {{
                $attributes
                    ->merge([
                        'aria-labelledby' => "{$id}-label",
                        'id' => $id,
                        'role' => 'group',
                    ], escape: false)
                    ->merge($getExtraAttributes(), escape: false)
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class([
                        'fi-fo-file-upload image-tile-upload__dropzone flex flex-col gap-y-2 [&_.filepond--root]:font-sans',
                        match ($alignment) {
                            Alignment::Start, Alignment::Left => 'items-start',
                            Alignment::Center => 'items-center',
                            Alignment::End, Alignment::Right => 'items-end',
                            default => $alignment,
                        },
                    ])
            }}
        >
            <div
                @class([
                    'h-full',
                    'w-32' => $isAvatar,
                    'w-full' => ! $isAvatar,
                ])
            >
                <input
                    x-ref="input"
                    {{
                        $getExtraInputAttributeBag()
                            ->merge([
                                'aria-labelledby' => "{$id}-label",
                                'disabled' => $isDisabled,
                                'multiple' => $isMultiple(),
                                'type' => 'file',
                            ], escape: false)
                    }}
                />
            </div>

            <div
                x-show="error"
                x-text="error"
                x-cloak
                class="text-sm text-danger-600 dark:text-danger-400"
            ></div>
        </div>
    </div>
</x-dynamic-component>
