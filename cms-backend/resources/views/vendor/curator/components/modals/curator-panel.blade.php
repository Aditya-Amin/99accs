<div
    x-data="{
        activeTab: 'library',
        _prevFilesCount: 0,
        _uploadTimer: null,
        init() {
            this._prevFilesCount = ($wire.files ?? []).length;

            // Auto-commit: when Livewire finishes uploading, save files to library
            this.$el.addEventListener('livewire-upload-finish', () => {
                clearTimeout(this._uploadTimer);
                this._uploadTimer = setTimeout(() => {
                    if (this.activeTab === 'upload') {
                        $wire.mountAction('addFiles');
                    }
                }, 600);
            });

            // Auto-switch to library tab when new files are committed to the library
            $wire.$watch('files', (files) => {
                const n = (files ?? []).length;
                if (n > this._prevFilesCount) {
                    this.activeTab = 'library';
                }
                this._prevFilesCount = n;
            });
        },
        handleItemClick: function (mediaId = null, event) {
            if (! mediaId) return;

            if ($wire.isMultiple && event && event.{{ config('curator.multi_select_key') }}) {
                if (this.isSelected(mediaId)) {
                    let toRemove = Object.values($wire.selected).find(obj => obj.id == mediaId)
                    $wire.removeFromSelection(toRemove.id);
                    return;
                }
                $wire.addToSelection(mediaId);
                return;
            }

            if ($wire.selected.length === 1 && $wire.selected[0].id != mediaId) {
                $wire.removeFromSelection($wire.selected[0].id);
                $wire.addToSelection(mediaId);
                return;
            }

            if ($wire.selected.length === 1 && $wire.selected[0].id == mediaId) {
                $wire.removeFromSelection($wire.selected[0].id);
                return;
            }

            $wire.addToSelection(mediaId);
        },
        isSelected: function (mediaId = null) {
            if ($wire.selected.length === 0) return false;
            return Object.values($wire.selected).find(obj => obj.id == mediaId) !== undefined;
        },
    }"
    class="curator-panel h-full absolute inset-0 flex flex-col"
>

    {{-- ═══════════════════════════════════════════════════════════════
         TAB BAR — Upload Files | Media Library  +  search on the right
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="cpm-tab-bar">
        <div class="cpm-tab-list">

            <button
                type="button"
                class="cpm-tab"
                :class="{ 'cpm-tab--active': activeTab === 'library' }"
                @click="activeTab = 'library'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-3 3Zm8-7.56a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd"/>
                </svg>
                Media Library
                @php $fileCount = count($files); @endphp
                @if ($fileCount > 0)
                    <span class="cpm-tab-badge">{{ $fileCount }}</span>
                @endif
            </button>

            <button
                type="button"
                class="cpm-tab"
                :class="{ 'cpm-tab--active': activeTab === 'upload' }"
                @click="activeTab = 'upload'; $wire.selected = []"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.955 3.129a.75.75 0 0 0 1.09-1.03l-4.25-4.5a.75.75 0 0 0-1.09 0l-4.25 4.5a.75.75 0 1 0 1.09 1.03L9.25 4.636v8.614Z"/>
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                </svg>
                Upload Files
            </button>

        </div>

        {{-- Search + controls (library tab only) --}}
        <div class="cpm-tab-controls" x-show="activeTab === 'library'">
            @if ($isMultiple)
                <x-filament::button
                    size="xs"
                    color="gray"
                    x-on:click="$wire.selected = []"
                    x-show="$wire.selected.length > 1"
                >
                    {{ trans('curator::views.panel.deselect_all') }}
                </x-filament::button>
            @endif
            @if($currentPage < $lastPage)
                <x-filament::button size="xs" color="gray" wire:click="loadMoreFiles()">
                    {{ trans('curator::views.panel.load_more') }}
                </x-filament::button>
            @endif
            <label class="cpm-search">
                <span class="sr-only">{{ trans('curator::views.panel.search_label') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="cpm-search-icon">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                </svg>
                <input
                    type="search"
                    placeholder="{{ trans('curator::views.panel.search_placeholder') }}"
                    wire:model.live.debounce.500ms="search"
                    class="cpm-search-input"
                />
                <svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                     class="cpm-search-spinner animate-spin"
                     wire:loading.delay wire:target="search">
                    <path clip-rule="evenodd" d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                    <path d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z" fill="currentColor"></path>
                </svg>
            </label>
        </div>
    </div>
    {{-- ═══ END TAB BAR ═══ --}}


    {{-- ═══════════════════════════════════════════════════════════════
         BODY — gallery + sidebar in a flex row
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex overflow-hidden" style="min-height: 0;">

        {{-- ── Gallery column (hidden when upload tab is active) ── --}}
        <div class="cpm-gallery" x-show="activeTab === 'library'">

            @if ($isMultiple)
                <p class="cpm-multiselect-hint" x-show="$wire.selected.length === 0" x-cloak>
                    @if (config('curator.multi_select_key') === 'metaKey')
                        Hold <kbd>⌘ Cmd</kbd> to select multiple files
                    @else
                        Hold <kbd>{{ config('curator.multi_select_key') }}</kbd> to select multiple files
                    @endif
                </p>
            @endif

            <ul class="curator-picker-grid">
                @forelse ($files as $file)
                    <li
                        wire:key="media-{{ $file['id'] }}"
                        class="cpm-grid-item"
                        x-bind:class="{ 'cpm-grid-item--dim': $wire.selected.length > 0 && !isSelected('{{ $file['id'] }}') }"
                    >
                        <button
                            type="button"
                            x-on:click="handleItemClick('{{ $file['id'] }}', $event)"
                            class="cpm-grid-btn"
                        >
                            @if (str_contains($file['type'], 'image'))
                                <img
                                    src="{{ $file['thumbnail_url'] }}"
                                    alt="{{ $file['alt'] ?? '' }}"
                                    width="240" height="240"
                                    loading="lazy"
                                />
                            @else
                                <div class="curator-document-image grid place-items-center w-full h-full uppercase relative">
                                    <div class="relative grid place-items-center w-full h-full">
                                        @if (str_contains($file['type'], 'video'))
                                            <x-filament::icon alias="curator::icons.video-camera" icon="heroicon-o-video-camera" class="w-10 h-10 opacity-30"/>
                                        @else
                                            <x-filament::icon alias="curator::icons.document" icon="heroicon-o-document" class="w-10 h-10 opacity-30"/>
                                        @endif
                                    </div>
                                    <span class="absolute bottom-1.5 text-[10px] font-bold tracking-wider text-gray-400">{{ $file['ext'] }}</span>
                                </div>
                            @endif
                        </button>

                        <p class="cpm-grid-name">{{ $file['pretty_name'] }}</p>

                        {{-- Selected overlay --}}
                        <button
                            type="button"
                            x-on:click="handleItemClick('{{ $file['id'] }}', $event)"
                            x-show="isSelected('{{ $file['id'] }}')"
                            x-cloak
                            class="cpm-grid-selected"
                        >
                            <span class="cpm-grid-check">
                                <x-filament::icon alias="curator::icons.check" icon="heroicon-s-check" class="w-4 h-4"/>
                            </span>
                            <span class="sr-only">{{ trans('curator::views.panel.deselect') }}</span>
                        </button>
                    </li>
                @empty
                    <li class="cpm-grid-empty">
                        <div class="cpm-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" class="cpm-empty-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                            </svg>
                            <p class="cpm-empty-heading">{{ $search ? 'No results for "' . $search . '"' : 'No media yet' }}</p>
                            <p class="cpm-empty-sub">{{ $search ? 'Try a different search term.' : 'Upload some files to get started.' }}</p>
                            @if (! $search)
                                <button type="button" class="cpm-empty-cta" @click="activeTab = 'upload'">
                                    Upload your first file
                                </button>
                            @endif
                        </div>
                    </li>
                @endforelse
            </ul>
        </div>
        {{-- ── END GALLERY ── --}}


        {{-- ══════════════════════════════════════════════════════════
             SIDEBAR — always mounted; expands full-width in upload mode.
             $this->form is rendered exactly once here.
             ══════════════════════════════════════════════════════════ --}}
        <div
            class="cpm-sidebar"
            :class="{ 'cpm-sidebar--upload': activeTab === 'upload' }"
        >
            {{-- ── Selected file preview (library mode, 1 item selected) ── --}}
            <div class="cpm-preview" x-show="activeTab === 'library' && $wire.selected.length === 1" x-cloak>
                <div class="cpm-preview-thumb checkered">
                    <img
                        x-show="$wire.selected[0]?.thumbnail_url"
                        :src="$wire.selected[0]?.thumbnail_url ?? ''"
                        :alt="$wire.selected[0]?.name ?? ''"
                        class="object-contain w-full h-full"
                    />
                    <div x-show="!$wire.selected[0]?.thumbnail_url" class="grid place-items-center w-full h-full opacity-30">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </div>
                </div>
                <div class="cpm-preview-info">
                    <p class="cpm-preview-name" x-text="$wire.selected[0]?.name"></p>
                    <div class="cpm-preview-meta">
                        <span class="cpm-preview-badge" x-text="($wire.selected[0]?.ext ?? '').toUpperCase()"></span>
                        <span x-text="$wire.selected[0]?.size_for_humans"></span>
                        <span x-show="$wire.selected[0]?.width" x-text="$wire.selected[0]?.width + ' × ' + $wire.selected[0]?.height + ' px'"></span>
                    </div>
                </div>
            </div>

            {{-- ── No-selection placeholder (library mode, nothing selected) ── --}}
            <div class="cpm-placeholder" x-show="activeTab === 'library' && $wire.selected.length === 0" x-cloak>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.25" stroke="currentColor" class="cpm-placeholder-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672Zm-7.518-.267A8.25 8.25 0 1 1 20.25 10.5M8.288 14.212A5.25 5.25 0 1 1 17.25 10.5"/>
                </svg>
                <p>Select a file to see details</p>
            </div>

            {{-- ── Section heading ── --}}
            <h4 class="cpm-sidebar-heading">
                <span x-show="activeTab === 'upload'">Upload Files</span>
                <span x-show="activeTab === 'library' && $wire.selected.length === 1">{{ trans('curator::views.panel.edit_media') }}</span>
                <span x-show="activeTab === 'library' && $wire.selected.length !== 1">{{ trans('curator::views.panel.add_files') }}</span>
            </h4>

            {{-- ── Filament form (upload zone OR edit metadata — rendered once) ── --}}
            <div class="cpm-sidebar-form">
                {{ $this->form }}
                <x-filament-actions::modals/>
            </div>

            {{-- ── Footer actions ── --}}
            <div class="cpm-sidebar-footer">
                @if (count($selected) !== 1)
                    <div class="flex gap-2 flex-wrap">
                        @if ($this->addFilesAction->isVisible())
                            {{ $this->addFilesAction }}
                        @endif
                        {{ $this->addInsertFilesAction }}
                    </div>
                @endif
                @if (count($selected) === 1)
                    <div class="flex gap-2">
                        @if ($this->updateFileAction->isVisible())
                            {{ $this->updateFileAction }}
                        @endif
                        {{ $this->cancelEditAction }}
                    </div>
                @endif
                @if (count($selected) > 0)
                    <div class="cpm-insert-wrap">
                        {{ $this->insertMediaAction }}
                    </div>
                @endif
            </div>
        </div>
        {{-- ══ END SIDEBAR ══ --}}

    </div>
    {{-- ═══ END BODY ═══ --}}

</div>
