<?php
namespace Core\Modules\Cart\Services;

use Core\Modules\Cart\Models\Cart;
use Core\Modules\Cart\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;

class CartService {
    public function getCart(?int $userId, ?string $sessionId): Cart {
        if ($userId) return Cart::firstOrCreate(['user_id' => $userId]);
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }
    public function addItem(Cart $cart, int $productId, int $quantity = 1, ?int $variationId = null): CartItem {
        $product = Product::findOrFail($productId);
        $price = $variationId ? ProductVariation::findOrFail($variationId)->price : $product->price;
        $item = CartItem::firstOrNew(['cart_id' => $cart->id, 'product_id' => $productId, 'product_variation_id' => $variationId]);
        $item->quantity = $item->exists ? $item->quantity + $quantity : $quantity;
        $item->price = $price;
        $item->save();
        return $item;
    }
}
