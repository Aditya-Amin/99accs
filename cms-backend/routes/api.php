<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupportController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Payment webhooks (public, signature-verified, outside versioned prefix) ──
Route::post('/webhooks/stripe',    [WebhookController::class, 'stripe']);
Route::post('/webhooks/cryptomus', [WebhookController::class, 'cryptomus']);

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────────────────
    Route::get('/home',   [HomeController::class, 'index']);
    Route::get('/footer', [HomeController::class, 'footer']);

    Route::get('/products',        [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    Route::get('/support/articles',        [SupportController::class, 'articles']);
    Route::get('/support/articles/{slug}', [SupportController::class, 'article']);
    Route::get('/support/faqs',            [SupportController::class, 'faqs']);
    Route::post('/support/contact',        [SupportController::class, 'contact']);

    Route::get('/menus/{slug}', [MenuController::class, 'show']);

    Route::get('/pages/{slug}', [PageController::class, 'show']);

    // ── Auth (canonical paths under /auth) ────────────────────────────────────
    Route::prefix('auth')->group(function () {
        // Unauthenticated — rate-limited to slow credential stuffing.
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login',    [AuthController::class, 'login']);
        });

        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
            Route::post('/password/reset',  [PasswordResetController::class, 'reset']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout',          [AuthController::class, 'logout']);
            Route::get('/me',               [AuthController::class, 'me']);
            Route::post('/password/change', [AuthController::class, 'changePassword']);
        });
    });

    // ── Auth aliases (Next.js BFF posts to /api/v1/{login,register,...}) ─────
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
    });
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('/reset-password',  [PasswordResetController::class, 'reset']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user',    [AuthController::class, 'me']);
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

        // Support tickets (customer-scoped)
        Route::get('/support/tickets',                [SupportTicketController::class, 'index']);
        Route::post('/support/tickets',               [SupportTicketController::class, 'store']);
        Route::get('/support/tickets/{id}',           [SupportTicketController::class, 'show']);
        Route::patch('/support/tickets/{id}',         [SupportTicketController::class, 'update']);
        Route::post('/support/tickets/{id}/replies',  [SupportTicketController::class, 'storeReply']);
    });

    // ── Checkout (guest-friendly) ─────────────────────────────────────────────
    // Guests can place an order by supplying email/phone/name; the controller
    // auto-creates a Customer with must_reset_password=true and emails them a
    // password-setup link. Order management is gated by the checkout_token UUID
    // (treated as a bearer secret) so the same routes serve guests and
    // authenticated users alike — auth is detected inside the controller for
    // the customer-vs-guest branch.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/checkout',                 [CheckoutController::class, 'create']);
        Route::get('/checkout/{id}',             [CheckoutController::class, 'show']);
        Route::post('/checkout/{id}/pay',        [CheckoutController::class, 'pay']);
        Route::post('/checkout/{id}/cancel',     [CheckoutController::class, 'cancel']);
    });
});
