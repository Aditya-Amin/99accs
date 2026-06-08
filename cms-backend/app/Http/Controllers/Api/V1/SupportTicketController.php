<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSupportTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketReplyRequest;
use App\Http\Requests\Api\V1\UpdateSupportTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketMessageResource;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Support\AdminNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    /** GET /support/tickets — the customer's own tickets, paginated. */
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::query()
            ->where('customer_id', $request->user()->id)
            ->with('openingMessage')
            // reply_count = messages minus the opening one, without loading them.
            ->withCount(['messages as replies_count' => fn ($q) => $q->where('is_opening', false)])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('game')) {
            $query->where('game', $request->string('game'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $query->where(fn ($q) => $q
                ->where('subject', 'like', $term)
                ->orWhere('ticket_number', 'like', $term));
        }

        $tickets = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            // resolve() (not toArray()) strips the unloaded `messages` key — a raw
            // toArray() leaves whenLoaded's placeholder behind as an empty object.
            'data'  => array_map(
                fn ($ticket) => (new SupportTicketResource($ticket))->resolve($request),
                $tickets->items(),
            ),
            'meta'  => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'per_page'     => $tickets->perPage(),
                'total'        => $tickets->total(),
            ],
            'links' => [
                'first' => $tickets->url(1),
                'last'  => $tickets->url($tickets->lastPage()),
                'next'  => $tickets->nextPageUrl(),
                'prev'  => $tickets->previousPageUrl(),
            ],
        ]);
    }

    /** GET /support/tickets/{id} — one ticket with its full message thread. */
    public function show(Request $request, int $id): SupportTicketResource
    {
        $ticket = $this->findOwnedTicket($request, $id);
        $ticket->load($this->threadEagerLoad());

        return new SupportTicketResource($ticket);
    }

    /** POST /support/tickets — open a new ticket with its first message. */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $customer = $request->user();

        $ticket = SupportTicket::create([
            'customer_id' => $customer->id,
            'game'        => $request->validated('game'),
            'subject'     => $request->validated('subject'),
            'status'      => SupportTicket::STATUS_NEW,
        ]);

        $opening = new SupportTicketMessage([
            'body'       => $request->validated('body'),
            'is_opening' => true,
        ]);
        $opening->ticket()->associate($ticket);
        $opening->author()->associate($customer);
        $opening->save();

        $this->notifyAdmins(
            $ticket,
            'New Support Ticket',
            "{$ticket->ticket_number} · {$ticket->subject} — from {$customer->full_name}",
        );

        $ticket->load($this->threadEagerLoad());

        return (new SupportTicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /support/tickets/{id} — status change (customer can close). */
    public function update(UpdateSupportTicketRequest $request, int $id): SupportTicketResource
    {
        $ticket = $this->findOwnedTicket($request, $id);

        $ticket->status = $request->validated('status');
        $ticket->save();

        $ticket->load($this->threadEagerLoad());

        return new SupportTicketResource($ticket);
    }

    /** POST /support/tickets/{id}/replies — append a customer reply. */
    public function storeReply(StoreTicketReplyRequest $request, int $id): JsonResponse
    {
        $ticket   = $this->findOwnedTicket($request, $id);
        $customer = $request->user();

        $message = new SupportTicketMessage([
            'body'       => $request->validated('body'),
            'is_opening' => false,
        ]);
        $message->ticket()->associate($ticket);
        $message->author()->associate($customer);
        $message->save();

        // Customer activity re-opens a closed thread and stamps the reply time.
        $ticket->markRepliedByCustomer();

        if ($request->boolean('close_ticket')) {
            $ticket->close();
        }

        $this->notifyAdmins(
            $ticket,
            'New Ticket Reply',
            "{$ticket->ticket_number} · {$customer->full_name} replied",
        );

        return (new SupportTicketMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /**
     * Resolve a ticket that belongs to the authenticated customer, or 404.
     * A foreign ticket 404s (not 403) so we never leak its existence.
     */
    private function findOwnedTicket(Request $request, int $id): SupportTicket
    {
        return SupportTicket::where('customer_id', $request->user()->id)
            ->findOrFail($id);
    }

    /** Eager-load the thread oldest-first (opening message leads). */
    private function threadEagerLoad(): array
    {
        return [
            'messages' => fn ($q) => $q->with('author')->orderBy('created_at')->orderBy('id'),
        ];
    }

    private function notifyAdmins(SupportTicket $ticket, string $title, string $body): void
    {
        AdminNotifier::notify(
            title: $title,
            body: $body,
            icon: 'heroicon-o-lifebuoy',
            iconColor: 'info',
            actionUrl: url("/admin/support-tickets/{$ticket->id}"),
            actionLabel: 'View ticket',
        );
    }
}
