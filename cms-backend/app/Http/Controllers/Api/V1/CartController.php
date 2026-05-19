<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product');

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'sometimes|integer|min:1|max:99',
        ]);

        $product  = Product::findOrFail($request->integer('product_id'));
        $quantity = $request->integer('quantity', 1);

        $cart = $this->getOrCreateCart($request);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $quantity,
                'price'      => $product->price,
            ]);
        }

        $cart->load('items.product');

        return response()->json(['data' => $this->formatCart($cart)], 201);
    }

    public function update(Request $request, int $itemId)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:99']);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);
        $item->update(['quantity' => $request->integer('quantity')]);

        $cart->load('items.product');

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    public function remove(Request $request, int $itemId)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->findOrFail($itemId)->delete();

        $cart->load('items.product');

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json(['data' => $this->formatCart($cart)]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getOrCreateCart(Request $request): Cart
    {
        $customer = $request->user();

        return Cart::firstOrCreate(['customer_id' => $customer->id]);
    }

    private function formatCart(Cart $cart): array
    {
        $items    = $cart->items ?? collect();
        $subtotal = $items->sum(fn ($i) => $i->price * $i->quantity);

        return [
            'id'       => $cart->id,
            'items'    => $items->map(fn ($item) => [
                'id'         => $item->id,
                'product_id' => $item->product_id,
                'title'      => $item->product?->name,
                'price'      => (float) $item->price,
                'quantity'   => $item->quantity,
                'subtotal'   => (float) ($item->price * $item->quantity),
                'image'      => $item->product?->featured_image_url,
            ])->values()->toArray(),
            'subtotal' => (float) $subtotal,
            'total'    => (float) $subtotal,
        ];
    }
}
