<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    /**
     * POST /api/v1/auth/password/forgot
     * Always responds 200 to prevent email enumeration. Real send is enqueued
     * via Customer::sendPasswordResetNotification.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $throttleKey = 'auth-forgot:' . Str::lower($request->input('email')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'code'    => 'RATE_LIMITED',
                'message' => 'Too many requests. Try again in a minute.',
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // Broker returns an enum; we ignore it on purpose (no enumeration).
        Password::broker('customers')->sendResetLink(['email' => $request->email]);

        return response()->json([
            'message' => 'If an account with that email exists, a reset link has been sent.',
        ]);
    }

    /**
     * POST /api/v1/auth/password/reset
     * Consumes the token, sets the new password, and clears any
     * legacy/must_reset_password flags. Returns a fresh Sanctum token so the
     * user is logged in immediately after reset.
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::broker('customers')->reset(
            $data,
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password'            => $password,
                    'must_reset_password' => false,
                    'is_legacy'           => false,
                ])->save();

                // Kill any pre-existing tokens — if attacker had one, it's dead now.
                $customer->tokens()->delete();

                Event::dispatch(new PasswordReset($customer));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $code = match ($status) {
                Password::INVALID_TOKEN => 'RESET_TOKEN_INVALID',
                Password::INVALID_USER  => 'RESET_TOKEN_INVALID',
                default                 => 'RESET_FAILED',
            };

            return response()->json([
                'code'    => $code,
                'message' => __($status),
            ], 422);
        }

        $customer = Customer::where('email', $data['email'])->firstOrFail();
        $customer->trackLogin($request->ip());

        return response()->json([
            'data' => [
                'token' => $customer->createToken('storefront')->plainTextToken,
                'user'  => [
                    'id'                  => $customer->id,
                    'name'                => $customer->full_name,
                    'first_name'          => $customer->first_name,
                    'last_name'           => $customer->last_name,
                    'email'               => $customer->email,
                    'phone'               => $customer->phone,
                    'must_reset_password' => false,
                    'is_legacy'           => false,
                    'created_at'          => $customer->created_at?->toISOString(),
                ],
            ],
        ]);
    }
}
