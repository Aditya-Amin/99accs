# Support Tickets — Laravel API Guide

The contract between the Next.js Support Portal (`/support`, `/support/tickets`, `/support/tickets/new`, `/support/tickets/[id]`) and the Laravel backend.

All ticket endpoints are **auth-gated** (`auth:sanctum`). Guests get `401 Unauthenticated`. The frontend's auth flow:

1. Guest clicks the **Create ticket** button on `/support` ([SupportPortalCta.tsx](../../components/support/SupportPortalCta.tsx)).
2. If `useAuthStore().status !== 'authed'`, the modal opens with `authPostLoginRedirect='/support/tickets'`.
3. `POST /auth/login` succeeds → AuthModal calls `setUser()` on the auth store and `window.location.assign('/support/tickets')`.
4. The server-rendered ticket list page reads the `99accs_token` cookie via `cookies()` and either renders the table or `redirect('/support')` if the cookie is missing.

Auth is currently mocked with an httpOnly cookie set by [/api/mock/auth/login/route.ts](../../app/api/mock/auth/login/route.ts). In production, Laravel issues a Sanctum personal-access token; the Next layout's [AuthHydrator.tsx](../../components/layout/AuthHydrator.tsx) calls `GET /auth/me` on first paint to fill the client store.

---

## Endpoints

```
GET    /api/support/tickets                       # paginated list of the auth'd user's tickets
POST   /api/support/tickets                       # create a new ticket
GET    /api/support/tickets/{id}                  # single ticket with full messages[]
PATCH  /api/support/tickets/{id}                  # update ticket (close/reopen)
POST   /api/support/tickets/{id}/replies          # post a reply, optionally close
```

- All five require `Authorization: Bearer {token}` (or `Cookie: laravel_session`).
- Authorization rule: a user may only access tickets where `tickets.user_id = auth()->id()`. Cross-user access returns `404 Not Found` (not 403 — leaking existence).

### `GET /api/support/tickets`

| Query param | Type | Notes |
|---|---|---|
| `status` | enum | `new` \| `open` \| `closed`. Omit for all. |
| `game` | enum | `valorant` \| `fortnite` \| `legends`. Omit for all. |
| `search` | string | Matches `subject` or `preview` (ILIKE). |
| `page` / `per_page` | int | Default `per_page=20`. |

Response shape:

```json
{
  "data": [
    {
      "id": 1,
      "ticket_number": "#15941",
      "user_id": 1,
      "game": "valorant",
      "order_number": "#AAAA12",
      "subject": "How Valorant Elo Boost Work?",
      "preview": "Just choose your preferred platform…",
      "status": "new",
      "reply_count": 0,
      "created_at": "2023-11-14T10:32:00Z",
      "last_reply_at": null
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 },
  "links": { "first": null, "last": null, "next": null, "prev": null }
}
```

**Important**: the list response **omits `messages[]`** — that field is only present on the detail endpoint. Use a separate Eloquent select list to keep the list query lean.

### `GET /api/support/tickets/{id}`

Returns the same `Ticket` shape plus `messages[]`:

```json
{
  "data": {
    "id": 1,
    "ticket_number": "#15941",
    /* …all list fields… */
    "messages": [
      {
        "id": 11,
        "ticket_id": 1,
        "is_owner": true,
        "author_name": "You",
        "author_avatar": "/img/images/comment_avatar02.png",
        "body": "Hi, I want to know how Valorant Elo boosting works…",
        "is_opening": true,
        "created_at": "2023-11-14T10:32:00Z"
      }
    ]
  }
}
```

`messages` is ordered **ascending by `created_at`** (oldest first). The Next.js frontend reverses it for display so the most recent reply renders at the top of the thread, matching support-3.html.

### `POST /api/support/tickets`

```json
{
  "subject": "Can't connect to the server",
  "body": "When I try to launch the game…",
  "game": "valorant"
}
```

Validation:
- `subject` required, string, max 255
- `body` required, string, max 5000
- `game` required, in `[valorant, fortnite, legends]`

> **`order_number` is generated server-side, not supplied by the client.** The Next.js form does not collect it. The Laravel service generates a unique `#AAAA##` style code at creation time (4 random letters + 2 digits). See [tickets/route.ts](../../app/api/mock/support/tickets/route.ts) `generateOrderNumber()` for the mock implementation. In Laravel, prefer a retry-on-collision loop bounded by a `UNIQUE` constraint:
>
> ```php
> do {
>     $orderNumber = '#' . Str::upper(Str::random(4)) . random_int(10, 99);
> } while (SupportTicket::where('order_number', $orderNumber)->exists());
> ```

On success returns `201` with the created `Ticket` (including the opening message). The first message in `messages[]` has `is_opening: true` and `is_owner: true`.

The Laravel service sets on insert:
- `ticket_number` — human-readable, e.g. `#` + zero-padded id, or a hash. Must be unique. Mock format: `#NNNNN`.
- `order_number` — server-generated unique code, format `#AAAA##` (see above).
- `preview` — `Str::limit($body, 200)` for the list view.
- `status` — `'new'`.
- `reply_count` — `0`.

### `POST /api/support/tickets/{id}/replies`

```json
{
  "body": "Thanks for the quick reply!",
  "close_ticket": false
}
```

Validation:
- `body` required, string, max 5000
- `close_ticket` boolean, default false

Returns `201` with the created message. Side effects on the ticket:
- `reply_count` += 1
- `last_reply_at` = now
- If `close_ticket` → `status = 'closed'`; else if `status === 'new'` → `status = 'open'`

### `PATCH /api/support/tickets/{id}`

```json
{ "status": "closed" }
```

Only `status` is mutable from this endpoint (subject/body/game are immutable once a ticket exists). Returns `200` with the updated ticket.

---

## Models

```php
// app/Models/SupportTicket.php
class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'user_id', 'game', 'order_number',
        'subject', 'preview', 'status', 'reply_count', 'last_reply_at',
    ];

    protected $casts = [
        'reply_count'   => 'integer',
        'last_reply_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }
}

// app/Models/SupportTicketMessage.php
class SupportTicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'is_owner', 'author_name', 'author_avatar',
        'body', 'is_opening',
    ];

    protected $casts = [
        'is_owner'   => 'boolean',
        'is_opening' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
}
```

---

## Migrations

```php
Schema::create('support_tickets', function (Blueprint $t) {
    $t->id();
    $t->string('ticket_number', 16)->unique();   // human-readable id, e.g. #15941
    $t->foreignId('user_id')->constrained()->cascadeOnDelete();
    $t->enum('game', ['valorant', 'fortnite', 'legends'])->index();
    $t->string('order_number', 16)->unique();    // server-generated `#AAAA##`
    $t->string('subject');
    $t->string('preview', 220);                  // Str::limit($body, 200) + ellipsis
    $t->enum('status', ['new', 'open', 'closed'])->default('new')->index();
    $t->unsignedInteger('reply_count')->default(0);
    $t->timestamp('last_reply_at')->nullable();
    $t->timestamps();

    $t->index(['user_id', 'status']);            // table filter query path
    $t->index(['user_id', 'created_at']);
});

Schema::create('support_ticket_messages', function (Blueprint $t) {
    $t->id();
    $t->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
    $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $t->boolean('is_owner')->default(false);     // true = owner, false = staff
    $t->string('author_name');                   // denormalised for staff name display
    $t->string('author_avatar')->nullable();
    $t->text('body');
    $t->boolean('is_opening')->default(false);   // marks the first message of the thread
    $t->timestamps();

    $t->index(['ticket_id', 'created_at']);
});
```

---

## Controller

```php
class SupportTicketController extends Controller
{
    public function __construct() { $this->middleware('auth:sanctum'); }

    public function index(Request $r)
    {
        $q = SupportTicket::where('user_id', $r->user()->id);

        if ($r->filled('status'))  $q->where('status', $r->string('status'));
        if ($r->filled('game'))    $q->where('game',   $r->string('game'));
        if ($r->filled('search')) {
            $term = '%' . $r->string('search') . '%';
            $q->where(fn($w) => $w->where('subject', 'like', $term)
                                  ->orWhere('preview', 'like', $term));
        }

        return SupportTicketListResource::collection(
            $q->orderByDesc('created_at')->paginate($r->integer('per_page', 20)),
        );
    }

    public function show(Request $r, SupportTicket $ticket)
    {
        if ($ticket->user_id !== $r->user()->id) abort(404);
        $ticket->load('messages');
        return new SupportTicketDetailResource($ticket);
    }

    public function store(StoreSupportTicketRequest $r)
    {
        $ticket = DB::transaction(function () use ($r) {
            // order_number is server-generated; we retry on the (very rare)
            // unique-constraint collision rather than trusting the client.
            do {
                $orderNumber = '#' . Str::upper(Str::random(4)) . random_int(10, 99);
            } while (SupportTicket::where('order_number', $orderNumber)->exists());

            $ticket = SupportTicket::create([
                'ticket_number' => '#' . Str::padLeft($this->nextNumber(), 5, '0'),
                'user_id'       => $r->user()->id,
                'game'          => $r->string('game'),
                'order_number'  => $orderNumber,
                'subject'       => $r->string('subject'),
                'preview'       => Str::limit($r->string('body'), 200, ''),
                'status'        => 'new',
                'reply_count'   => 0,
            ]);
            $ticket->messages()->create([
                'user_id'       => $r->user()->id,
                'is_owner'      => true,
                'author_name'   => 'You',
                'author_avatar' => '/img/images/comment_avatar02.png',
                'body'          => $r->string('body'),
                'is_opening'    => true,
            ]);
            $ticket->load('messages');
            return $ticket;
        });
        return (new SupportTicketDetailResource($ticket))->response()->setStatusCode(201);
    }

    public function update(Request $r, SupportTicket $ticket)
    {
        if ($ticket->user_id !== $r->user()->id) abort(404);
        $r->validate(['status' => ['nullable', Rule::in(['new', 'open', 'closed'])]]);
        if ($r->filled('status')) $ticket->update(['status' => $r->string('status')]);
        return new SupportTicketDetailResource($ticket->load('messages'));
    }

    public function reply(StoreReplyRequest $r, SupportTicket $ticket)
    {
        if ($ticket->user_id !== $r->user()->id) abort(404);

        $message = DB::transaction(function () use ($r, $ticket) {
            $msg = $ticket->messages()->create([
                'user_id'       => $r->user()->id,
                'is_owner'      => true,
                'author_name'   => 'You',
                'author_avatar' => '/img/images/comment_avatar02.png',
                'body'          => $r->string('body'),
            ]);
            $ticket->reply_count += 1;
            $ticket->last_reply_at = now();
            if ($r->boolean('close_ticket'))         $ticket->status = 'closed';
            elseif ($ticket->status === 'new')       $ticket->status = 'open';
            $ticket->save();
            return $msg;
        });

        return (new SupportTicketMessageResource($message))->response()->setStatusCode(201);
    }
}
```

---

## Resources

Two list/detail resources to avoid shipping `messages[]` on the list query:

```php
class SupportTicketListResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return [
            'id'             => $this->id,
            'ticket_number'  => $this->ticket_number,
            'user_id'        => $this->user_id,
            'game'           => $this->game,
            'order_number'   => $this->order_number,
            'subject'        => $this->subject,
            'preview'        => $this->preview,
            'status'         => $this->status,
            'reply_count'    => $this->reply_count,
            'created_at'     => $this->created_at?->toIso8601String(),
            'last_reply_at'  => $this->last_reply_at?->toIso8601String(),
        ];
    }
}

class SupportTicketDetailResource extends SupportTicketListResource
{
    public function toArray(Request $r): array
    {
        return array_merge(parent::toArray($r), [
            'messages' => SupportTicketMessageResource::collection($this->whenLoaded('messages')),
        ]);
    }
}

class SupportTicketMessageResource extends JsonResource
{
    public function toArray(Request $r): array
    {
        return [
            'id'            => $this->id,
            'ticket_id'     => $this->ticket_id,
            'is_owner'      => (bool) $this->is_owner,
            'author_name'   => $this->author_name,
            'author_avatar' => $this->author_avatar,
            'body'          => $this->body,
            'is_opening'    => (bool) $this->is_opening,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
```

---

## Routes

```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('support')->group(function () {
    Route::get   ('tickets',              [SupportTicketController::class, 'index']);
    Route::post  ('tickets',              [SupportTicketController::class, 'store']);
    Route::get   ('tickets/{ticket}',     [SupportTicketController::class, 'show']);
    Route::patch ('tickets/{ticket}',     [SupportTicketController::class, 'update']);
    Route::post  ('tickets/{ticket}/replies', [SupportTicketController::class, 'reply']);
});
```

---

## Frontend ↔ Backend wiring

The Next.js Support Portal currently reads from local mocks ([lib/mock/support.ts](../../lib/mock/support.ts)) on the **server-rendered** pages, and posts to the **mock HTTP routes** ([app/api/mock/support/tickets/](../../app/api/mock/support/tickets/)) for client-side mutations. When Laravel is live:

1. Set `NEXT_PUBLIC_API_BASE_URL=https://api.99accs.com/api`.
2. Switch the four `getMock…` reads in the page files to `getSupportTickets()` / `getSupportTicket()` from [lib/api/endpoints.ts](../../lib/api/endpoints.ts), passing the token from the request cookie.
3. Replace the client-side `fetch('/api/mock/support/tickets', …)` calls in [SupportTicketCreateForm.tsx](../../components/support/SupportTicketCreateForm.tsx) and [SupportTicketThread.tsx](../../components/support/SupportTicketThread.tsx) with `createSupportTicket()` / `replySupportTicket()` / `updateSupportTicket()`.
4. Delete [app/api/mock/support/tickets/](../../app/api/mock/support/tickets/) and [mocks/support/tickets.json](./tickets.json).

The wire shape (URL paths, query params, request bodies, response envelopes) does not change between mock and live — that's the whole point of pinning them in this document.

---

## Build order — suggested phasing

1. **Migrations + Eloquent models + factory + seeder** importing the eight rows from [mocks/support/tickets.json](./tickets.json).
2. **`SupportTicketController@index` + `@show`** with the Sanctum middleware. Flip `NEXT_PUBLIC_API_BASE_URL` and verify the list + thread pages render against Laravel.
3. **`@store` + `@reply` + `@update`** with `FormRequest` validation. Verify the create form + reply form + close button against the live backend.
4. **Background worker** for staff replies / SLA reminders (out of scope for the frontend contract but should fire `'SupportTicketUpdated'` events that the Next.js page revalidates against — `router.refresh()` is already wired in `SupportTicketThread`).
5. **Rate limiting** — sensible per-user caps on `store` (3/hour) and `reply` (10/minute). Sanctum supports `throttle:` on the route group.
