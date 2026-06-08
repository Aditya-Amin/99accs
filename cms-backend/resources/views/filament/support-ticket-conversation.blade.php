@php
    /** @var \Illuminate\Support\Collection $messages */
    $messages = $getState() ?? collect();
    $ticket   = $getRecord();
    $isClosed = $ticket?->status === \App\Models\SupportTicket::STATUS_CLOSED;
@endphp

<style>
    .stc-window {
        display: flex; flex-direction: column; gap: 1rem; padding: .25rem .25rem 1rem;
        max-height: 460px; overflow-y: auto;
    }
    .stc-window::-webkit-scrollbar { width: 6px; }
    .stc-window::-webkit-scrollbar-thumb { background: rgba(120,120,120,.35); border-radius: 6px; }
    .stc-row { display: flex; align-items: flex-end; gap: .625rem; }
    .stc-row--own { flex-direction: row-reverse; }      /* staff / admin = "us", on the right */
    .stc-bubble-wrap { display: flex; flex-direction: column; max-width: 78%; min-width: 0; }
    .stc-meta { display: flex; gap: .5rem; align-items: baseline; margin-bottom: .25rem; font-size: .75rem; }
    .stc-row--own .stc-meta { flex-direction: row-reverse; }
    .stc-author { font-weight: 600; }
    .stc-author--customer { color: #047857; }           /* emerald-700 */
    .stc-author--staff { color: #b45309; }              /* amber-700  */
    .stc-time { color: #9ca3af; }                       /* gray-400   */
    .stc-bubble {
        padding: .625rem .875rem; border-radius: 1rem; line-height: 1.5;
        font-size: .875rem; word-break: break-word; white-space: pre-wrap;
    }
    .stc-row--other .stc-bubble { background: #f3f4f6; color: #111827; border-top-left-radius: .25rem; }
    .stc-row--own   .stc-bubble { background: #fef3c7; color: #78350f; border-top-right-radius: .25rem; }
    .stc-empty { text-align: center; color: #9ca3af; padding: 2rem 0; }

    /* Composer — WhatsApp/Messenger style: rounded input + circular send button */
    .stc-closed-hint { font-size: .75rem; color: #9ca3af; margin: .25rem .25rem .5rem; }
    .stc-composer {
        display: flex; align-items: center; gap: .5rem;
        margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;
    }
    .stc-composer-input {
        flex: 1; min-width: 0;
        border: 1px solid #e5e7eb; border-radius: 9999px;
        padding: .625rem 1.125rem; font-size: .875rem; outline: none;
        background: #fff; color: #111827;
    }
    .stc-composer-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.18); }
    .stc-composer-send {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.5rem; height: 2.5rem; border-radius: 9999px; border: none; flex-shrink: 0;
        background: #f59e0b; color: #fff; cursor: pointer;
        transition: background .15s ease, opacity .15s ease;
    }
    .stc-composer-send:hover:not(:disabled) { background: #d97706; }
    .stc-composer-send:disabled { opacity: .4; cursor: not-allowed; }
    .stc-composer-send svg { width: 18px; height: 18px; }

    /* Dark mode (Filament toggles `.dark` on <html>) */
    .dark .stc-author--customer { color: #34d399; }
    .dark .stc-author--staff { color: #fbbf24; }
    .dark .stc-row--other .stc-bubble { background: #374151; color: #f3f4f6; }
    .dark .stc-row--own   .stc-bubble { background: #92400e; color: #fef3c7; }
    .dark .stc-composer { border-top-color: #374151; }
    .dark .stc-composer-input { background: #1f2937; border-color: #374151; color: #f3f4f6; }
</style>

<div
    class="stc-window"
    wire:poll.5s
    x-data="{
        stick: true,
        init() {
            const el = this.$el;
            el.scrollTop = el.scrollHeight;
            el.addEventListener('scroll', () => {
                this.stick = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
            });
            // After each poll/morph adds messages, snap to bottom only if the
            // agent was already near the bottom (don't interrupt reading history).
            new MutationObserver(() => { if (this.stick) el.scrollTop = el.scrollHeight; })
                .observe(el, { childList: true, subtree: true });
        }
    }"
>
    @forelse ($messages as $message)
        {{-- Admin perspective: staff/admin replies sit on the RIGHT ("us"),
             the customer's messages on the LEFT. --}}
        @php $fromStaff = ! $message->isOwnerAuthored(); @endphp
        <div class="stc-row {{ $fromStaff ? 'stc-row--own' : 'stc-row--other' }}">
            <div class="stc-bubble-wrap">
                <div class="stc-meta">
                    @if ($fromStaff)
                        <span class="stc-author stc-author--staff">Staff · {{ $message->author?->name ?? 'Support' }}</span>
                    @else
                        <span class="stc-author stc-author--customer">Customer</span>
                    @endif
                    <span class="stc-time">{{ $message->created_at?->format('M j, Y · g:i A') }}</span>
                </div>
                <div class="stc-bubble">{{ $message->body }}</div>
            </div>
        </div>
    @empty
        <p class="stc-empty">No messages yet.</p>
    @endforelse
</div>

@if ($isClosed)
    <p class="stc-closed-hint">This ticket is closed — sending a message will reopen it.</p>
@endif

<form
    wire:submit.prevent="sendReply"
    class="stc-composer"
    x-data="{ body: $wire.entangle('replyBody') }"
>
    <input
        type="text"
        x-model="body"
        class="stc-composer-input"
        placeholder="Type a message…"
        autocomplete="off"
        wire:loading.attr="disabled"
    />
    <button
        type="submit"
        class="stc-composer-send"
        x-bind:disabled="! body || ! body.trim()"
        wire:loading.attr="disabled"
        aria-label="Send message"
    >
        {{-- paper-plane (heroicon solid), inlined to avoid the panel's disabled blade-icon components --}}
        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M3.105 2.289a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.085l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.154.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.289Z" />
        </svg>
    </button>
</form>
