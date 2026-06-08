<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a SupportTicketMessage to the shape the Next.js thread expects
 * (see SupportTicketMessage in 99accs-app/lib/api/types.ts).
 *
 * This API is consumed only by the ticket's owning customer, so "owner" always
 * means "authored by the Customer". To preserve pixel parity with the frontend
 * mock the owner's messages render as "You"; staff replies carry the admin name.
 */
class SupportTicketMessageResource extends JsonResource
{
    // Static template avatars (live in 99accs-app/public/img/images). Neither
    // Customer nor admin User carries an avatar column yet, so we reuse the same
    // two assets the mock used — owner vs. staff.
    private const OWNER_AVATAR = '/img/images/comment_avatar02.png';
    private const STAFF_AVATAR = '/img/images/comment_avatar01.png';

    public function toArray(Request $request): array
    {
        $isOwner = $this->isOwnerAuthored();

        return [
            'id'            => $this->id,
            'ticket_id'     => $this->ticket_id,
            'is_owner'      => $isOwner,
            'author_name'   => $isOwner ? 'You' : ($this->author?->name ?? 'Support'),
            'author_avatar' => $isOwner ? self::OWNER_AVATAR : self::STAFF_AVATAR,
            'body'          => $this->body,
            // Only emitted for the opening message — matches the optional
            // `is_opening?` field in the contract.
            'is_opening'    => $this->when($this->is_opening, true),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
