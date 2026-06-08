<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Serializes a SupportTicket to the shape the Next.js frontend expects
 * (see SupportTicket in 99accs-app/lib/api/types.ts).
 *
 * Field-mapping notes:
 *  • The storefront user is a Customer, but the frontend names the owner field
 *    `user_id` — so customer_id is exposed as user_id here.
 *  • `preview` and `reply_count` are derived, never stored.
 *  • `messages` is only present on detail reads (whenLoaded).
 */
class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'ticket_number' => $this->ticket_number,
            'user_id'       => $this->customer_id,
            'game'          => $this->game,
            'order_number'  => $this->order_number,
            'subject'       => $this->subject,
            'preview'       => $this->resolvePreview(),
            'status'        => $this->status,
            'reply_count'   => $this->resolveReplyCount(),
            'created_at'    => $this->created_at?->toISOString(),
            'last_reply_at' => $this->last_reply_at?->toISOString(),
            'messages'      => $this->whenLoaded('messages', fn () =>
                SupportTicketMessageResource::collection($this->messages)
            ),
        ];
    }

    /** First 200 chars of the opening message — the "Conversation" column excerpt. */
    protected function resolvePreview(): string
    {
        $opening = $this->resolveOpeningMessage();

        return $opening ? Str::limit($opening->body, 200, '') : '';
    }

    /** Replies = every message except the opening one. */
    protected function resolveReplyCount(): int
    {
        // Preferred: a withCount() alias loaded by the list query (cheap).
        if (isset($this->replies_count)) {
            return (int) $this->replies_count;
        }

        // Fallback: derive from a fully-loaded thread (detail reads).
        if ($this->relationLoaded('messages')) {
            return $this->messages->where('is_opening', false)->count();
        }

        return 0;
    }

    protected function resolveOpeningMessage(): ?SupportTicketMessage
    {
        if ($this->relationLoaded('openingMessage')) {
            return $this->openingMessage->first();
        }

        if ($this->relationLoaded('messages')) {
            return $this->messages->firstWhere('is_opening', true);
        }

        return null;
    }
}
