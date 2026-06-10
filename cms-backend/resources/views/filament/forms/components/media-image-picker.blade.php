@php
    $statePath = $getStatePath();
    $value = $getState();

    // Unwrap array if needed
    if (is_array($value)) {
        $value = $value[0] ?? null;
    }

    // Resolve URL
    $imageUrl = null;
    if ($value) {
        if (ctype_digit((string) $value)) {
            $imageUrl = \App\Models\CuratorMedia::find((int) $value)?->url;
        } else {
            $imageUrl = (string) $value;
        }
    }
@endphp

<div x-data="{ imageUrl: @js($imageUrl) }" class="space-y-2">
    <!-- Preview Area -->
    <div class="overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50"
         style="width: {{ $previewWidth }}px; height: {{ $previewHeight }}px;">

        <template x-if="imageUrl">
            <div class="relative h-full w-full group cursor-pointer">
                <img :src="imageUrl" alt="Preview" class="h-full w-full object-cover" />
                <button type="button" onclick="this.closest('[x-data]').querySelector('[data-action=delete]').click()" class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg" title="Delete image">
                    <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
        </template>

        <template x-if="!imageUrl">
            <div class="h-full w-full flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100 transition" onclick="this.closest('[x-data]').querySelector('[data-action=edit]').click()">
                <svg class="h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-sm font-medium text-gray-700">No image selected</p>
                <p class="text-xs text-gray-500 mt-1">Click to select or drag to upload</p>
            </div>
        </template>
    </div>

    <!-- Action buttons (hidden, used by preview) -->
    <button type="button" data-action="edit" class="hidden"></button>
    <button type="button" data-action="delete" @click="$wire.set(@js($statePath), null); imageUrl = null;" class="hidden"></button>
</div>
