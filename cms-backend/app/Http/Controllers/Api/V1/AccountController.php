<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductListResource;
use App\Models\Order;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = $request->user();

        $orders = Order::where('customer_id', $customer->id)
            ->with('items')
            ->latest()
            ->take(5)
            ->get();

        $totalSpent = Order::where('customer_id', $customer->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('total_price');

        return response()->json([
            'data' => [
                'order_count'    => Order::where('customer_id', $customer->id)->count(),
                'wishlist_count' => 0,  // Phase 3: Wishlist model
                'total_spent'    => (float) $totalSpent,
                'recent_orders'  => $this->formatOrders($orders),
            ],
        ]);
    }

    public function orders(Request $request)
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data'  => $this->formatOrders($orders->items()),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
            'links' => [
                'first' => $orders->url(1),
                'last'  => $orders->url($orders->lastPage()),
                'next'  => $orders->nextPageUrl(),
                'prev'  => $orders->previousPageUrl(),
            ],
        ]);
    }

    public function order(Request $request, int $id)
    {
        $order = Order::where('customer_id', $request->user()->id)
            ->with('items')
            ->findOrFail($id);

        return response()->json(['data' => $this->formatOrder($order)]);
    }

    public function profile(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'data' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'full_name'  => $customer->full_name,
                'email'      => $customer->email,
                'phone'      => $customer->phone,
                'created_at' => $customer->created_at?->toISOString(),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $data = $request->validate([
            'first_name'           => 'sometimes|string|max:255',
            'last_name'            => 'sometimes|string|max:255',
            'email'                => "sometimes|email|unique:customers,email,{$customer->id}",
            'phone'                => 'sometimes|nullable|string|max:30',
            'password'             => 'sometimes|string|min:8|confirmed',
            'current_password'     => 'required_with:password|string',
        ]);

        if (isset($data['password'])) {
            if (! Hash::check($data['current_password'], $customer->password)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['current_password' => ['Current password is incorrect.']],
                ], 422);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
        }

        $customer->update($data);

        return response()->json(['data' => $customer->fresh()]);
    }

    public function wishlist(Request $request)
    {
        $items = Wishlist::where('customer_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data'  => ProductListResource::collection($items->map(fn ($w) => $w->product))->toArray($request),
            'meta'  => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function addToWishlist(Request $request)
    {
        $request->validate(['product_id' => 'required|integer|exists:products,id']);

        $wishlist = Wishlist::firstOrCreate([
            'customer_id' => $request->user()->id,
            'product_id'  => $request->integer('product_id'),
        ]);

        return response()->json(['data' => $wishlist], 201);
    }

    public function removeFromWishlist(Request $request, int $id)
    {
        Wishlist::where('customer_id', $request->user()->id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist.']);
    }

    // ─── Formatters ──────────────────────────────────────────────────────────

    private function formatOrders(array|\Illuminate\Support\Collection $orders): array
    {
        return collect($orders)->map(fn ($o) => $this->formatOrder($o))->toArray();
    }

    private function formatOrder(Order $order): array
    {
        return [
            'id'         => $order->id,
            'number'     => $order->number,
            'status'     => $order->status,
            'total'      => (float) $order->total_price,
            'created_at' => $order->created_at?->toISOString(),
            'items'      => $order->items->map(fn ($item) => [
                'id'                 => $item->id,
                'product_id'         => $item->product_id,
                'product_title'      => $item->product_name_snapshot ?? 'Product #' . $item->product_id,
                'product_image'      => $item->product_image_snapshot,
                'quantity'           => $item->quantity,
                'price_at_purchase'  => (float) $item->price_snapshot,
            ])->toArray(),
        ];
    }
}
