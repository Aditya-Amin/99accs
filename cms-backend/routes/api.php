<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Payment webhooks (public, signature-verified, outside versioned prefix) ──
Route::post('/webhooks/stripe',    [WebhookController::class, 'stripe']);
Route::post('/webhooks/cryptomus', [WebhookController::class, 'cryptomus']);

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────────────────
    Route::get('/home', [HomeController::class, 'index']);

    Route::get('/products',        [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    Route::get('/support/articles',        [SupportController::class, 'articles']);
    Route::get('/support/articles/{slug}', [SupportController::class, 'article']);
    Route::get('/support/faqs',            [SupportController::class, 'faqs']);
    Route::post('/support/contact',        [SupportController::class, 'contact']);

    Route::get('/menus/{slug}', [MenuController::class, 'show']);

    Route::get('/pages/{slug}', [PageController::class, 'show']);

    // ── Auth (canonical paths) ────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me',      [AuthController::class, 'me']);
        });
    });

    // ── Auth aliases (Next.js frontend expects these flat paths) ─────────────
    Route::post('/register',       [AuthController::class, 'register']);
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password',[AuthController::class, 'forgotPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user',    [AuthController::class, 'me']);     // getMe() calls /user
    });

    // ── Authenticated (Customer) ───────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Account
        Route::get('/account/dashboard',              [AccountController::class, 'dashboard']);
        Route::get('/account/orders',                 [AccountController::class, 'orders']);
        Route::get('/account/orders/{id}',            [AccountController::class, 'order']);
        Route::get('/account/profile',                [AccountController::class, 'profile']);
        Route::patch('/account/profile',              [AccountController::class, 'updateProfile']);
        Route::get('/account/wishlist',               [AccountController::class, 'wishlist']);
        Route::post('/account/wishlist',              [AccountController::class, 'addToWishlist']);
        Route::delete('/account/wishlist/{id}',       [AccountController::class, 'removeFromWishlist']);

        // Cart
        Route::get('/cart',              [CartController::class, 'index']);
        Route::post('/cart',             [CartController::class, 'add']);
        Route::patch('/cart/{item_id}',  [CartController::class, 'update']);
        Route::delete('/cart/{item_id}', [CartController::class, 'remove']);
        Route::delete('/cart',           [CartController::class, 'clear']);

        // Checkout (Phase 5)
        Route::post('/checkout',             [CheckoutController::class, 'create']);
        Route::get('/checkout/{id}',         [CheckoutController::class, 'show']);
        Route::post('/checkout/{id}/pay',    [CheckoutController::class, 'pay']);
        Route::post('/checkout/{id}/cancel', [CheckoutController::class, 'cancel']);
    });
});
