@php
    $statePath = $getStatePath();
    $items = $getState() ?? [];
    $itemsCount = count($items);
    $isMultiple = $isMultiple();
    $maxItems = $getMaxItems();
    $shouldDisplayAsList = $shouldDisplayAsList();
    $canAddMore = ! $maxItems || $itemsCount < $maxItems;
@endphp

{{--
    Overrides Awcodes\Curator picker view (curator::components.forms.picker).
    Adds a friendly drop-zone empty state and a clean image preview with
    edit/remove controls on hover. Native Curator actions (open_curator_picker,
    edit, remove, download, reorder) remain wired to the modal + form state, so
    this works for every CuratorPicker across the admin — single or multiple.
--}}

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">

    <div
        x-data="{
            insertMedia: function (event) {
                if (event.detail.statePath !== '{{ $statePath }}') return;
                $wire.$set(event.detail.statePath, event.detail.media);
            },
        }"
        x-on:insert-content.window="insertMedia($event)"
        class="curator-media-picker w-full"
    >
        {{-- ─── Empty state: dashed drop-zone placeholder ─────────────────── --}}
        @if ($itemsCount === 0)
            <button
                type="button"
                onclick="this.parentElement.querySelector('[data-curator-open] button, [data-curator-open] .fi-btn')?.click()"
                class="group flex w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-primary-500 hover:bg-primary-50 dark:border-gray-600 dark:bg-white/5 dark:hover:border-primary-500 dark:hover:bg-primary-500/10"
            >
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition group-hover:bg-primary-100 group-hover:text-primary-500 dark:bg-white/10 dark:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">No image selected</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">Click to open the media library</span>
            </button>

            {{-- The real Curator open action — hidden, triggered by the box above --}}
            <div data-curator-open class="mt-3 flex justify-center">
                {{ $getAction('open_curator_picker') }}
            </div>

        {{-- ─── Filled state: preview(s) with hover actions ──────────────── --}}
        @else
            <ul
                @class([
                    'w-full',
                    'flex items-center gap-6 flex-wrap' => $itemsCount <= 3 && ! $shouldDisplayAsList,
                    'curator-grid-container' => $itemsCount >= 3 && ! $shouldDisplayAsList,
                    'overflow-hidden bg-white border border-gray-300 rounded-lg shadow-sm divide-y divide-gray-300 dark:border-gray-700 dark:text-white dark:divide-gray-700 dark:bg-white/5' => $shouldDisplayAsList,
                ])
                x-sortable
                wire:end.stop="mountFormComponentAction('{{ $statePath }}', 'reorder', { items: $event.target.sortable.toArray() })"
                style="{{ $itemsCount === 1 ? '--grid-column-count: 1' : '' }}"
            >
                @foreach ($items as $uuid => $item)
                    <li
                        wire:key="{{ $this->getId() }}.{{ $uuid }}.{{ $field::class }}.item"
                        x-sortable-item="{{ $uuid }}"
                        {{ $attributes->merge($getExtraAttributes())->class(['relative w-full']) }}
                    >
                        @if ($shouldDisplayAsList)
                            <div class="w-full flex items-center gap-4 text-xs pe-2">
                                <div class="curator-picker-list-preview flex-shrink-0 h-12 w-12 checkered">
                                    @if (str($item['type'] ?? '')->contains('image'))
                                        <img
                                            src="{{ $item['url'] ?? $item['thumbnail_url'] ?? '' }}"
                                            alt="{{ $item['alt'] ?? $item['pretty_name'] ?? $item['name'] ?? '' }}"
                                            @if ($shouldLazyLoad()) loading="lazy" @endif
                                            @class([
                                                'h-full',
                                                'object-contain' => $isConstrained(),
                                                'object-cover w-full' => ! $isConstrained(),
                                            ])
                                        />
                                    @else
                                        <x-curator::document-image
                                            label="{{ $item['name'] ?? '' }}"
                                            type="{{ $item['type'] ?? '' }}"
                                            extension="{{ $item['ext'] ?? '' }}"
                                            icon-size="md"
                                        />
                                    @endif
                                </div>
                                <div class="curator-picker-list-details min-w-0 overflow-hidden py-2">
                                    <p>{{ $item['pretty_name'] ?? $item['name'] ?? '' }}</p>
                                </div>
                                <div class="curator-picker-list-details flex-shrink-0 ml-auto py-2">
                                    <p>{{ $item['size_for_humans'] ?? '' }}</p>
                                </div>
                                <div class="curator-picker-list-actions flex-shrink-0">
                                    <div class="relative flex items-center">
                                        @if ($isMultiple)
                                            <div x-sortable-handle class="flex items-center justify-center flex-none w-8 h-8 transition text-gray-400 hover:text-gray-300">
                                                {{ $getAction('reorder') }}
                                            </div>
                                        @endif
                                        <div class="flex items-center justify-center flex-none w-8 h-8">
                                            <x-filament-actions::group
                                                :actions="[
                                                    $getAction('view')(['url' => $item['url'] ?? '#']),
                                                    $getAction('edit')(['id' => $item['id'] ?? null]),
                                                    $getAction('download')(['uuid' => $uuid]),
                                                    $getAction('remove')(['uuid' => $uuid]),
                                                ]"
                                                color="gray"
                                                size="xs"
                                                dropdown-placement="bottom-end"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div
                                @class([
                                    'curator-preview group relative flex items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-sm checkered dark:bg-gray-800 dark:border-gray-700 dark:text-white',
                                    'curator-preview--single' => ! $isMultiple,
                                    'curator-preview--multiple md:flex-1' => $isMultiple,
                                ])
                            >
                                @if (str($item['type'] ?? '')->contains('image'))
                                    <img
                                        src="{{ $item['url'] ?? $item['large_url'] ?? '' }}"
                                        alt="{{ $item['alt'] ?? $item['pretty_name'] ?? $item['name'] ?? '' }}"
                                        @if ($shouldLazyLoad()) loading="lazy" @endif
                                        class="curator-preview-img"
                                    />
                                @elseif (str($item['type'] ?? '')->contains('video'))
                                    <video controls src="{{ $item['url'] ?? '' }}" class="h-full w-full" @if ($shouldLazyLoad()) preload="none" @endif></video>
                                @else
                                    <x-curator::document-image
                                        label="{{ $item['name'] ?? '' }}"
                                        icon-size="xl"
                                        type="{{ $item['type'] ?? '' }}"
                                        extension="{{ $item['ext'] ?? '' }}"
                                    />
                                @endif

                                {{-- Reorder handle (multiple only) --}}
                                @if ($isMultiple)
                                    <div x-sortable-handle class="absolute top-2 left-2 flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900/70 text-gray-200 opacity-0 shadow transition hover:text-white group-hover:opacity-100" style="cursor: move;">
                                        {{ $getAction('reorder') }}
                                    </div>
                                @endif

                                {{-- Hover overlay: edit (pencil → Curator edit screen) + remove (trash).
                                     Reveal is driven by .curator-preview:hover in filament-admin.css,
                                     not Tailwind group-hover (which isn't in the compiled CSS). --}}
                                <div class="curator-preview-actions">
                                    @if (! empty($item['id']))
                                        <a
                                            href="{{ \Awcodes\Curator\Resources\MediaResource::getUrl('edit', ['record' => $item['id']]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="curator-preview-btn curator-preview-btn--edit"
                                            title="Edit image (opens in a new tab)"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="mountFormComponentAction('{{ $statePath }}', 'remove', { uuid: '{{ $uuid }}' })"
                                        class="curator-preview-btn curator-preview-btn--remove"
                                        title="Remove image"
                                    >
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Footer (multiple only): add more / clear all.
                 Single image uses the hover pencil/trash overlay, so no footer. --}}
            @if ($isMultiple)
                <div class="mt-4 flex items-center gap-3">
                    @if ($canAddMore)
                        {{ $getAction('open_curator_picker') }}
                    @endif
                    @if ($itemsCount > 1)
                        {{ $getAction('removeAll') }}
                    @endif
                </div>
            @endif
        @endif
    </div>
</x-dynamic-component>
