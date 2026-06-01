<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'email'      => 'required|email|unique:customers,email',
            'password'   => ['required', 'string', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
            'phone'      => 'nullable|string|max:30',
        ]);

        if (isset($data['name']) && ! isset($data['first_name'])) {
            $parts              = explode(' ', trim($data['name']), 2);
            $data['first_name'] = $parts[0];
            $data['last_name']  = $parts[1] ?? '';
        }

        $customer = Customer::create([
            'first_name' => $data['first_name'] ?? '',
            'last_name'  => $data['last_name']  ?? '',
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => $data['password'],
        ]);

        $customer->trackLogin($request->ip());

        return response()->json([
            'data' => [
                'token' => $customer->createToken('storefront')->plainTextToken,
                'user'  => $this->customerShape($customer),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'auth-login:' . Str::lower($request->input('email')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'code'    => 'RATE_LIMITED',
                'message' => 'Too many login attempts. Try again in a minute.',
            ], 429);
        }

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            RateLimiter::hit($throttleKey, 60);
            return $this->invalidCredentials();
        }

        if ($customer->is_blocked) {
            return response()->json([
                'code'    => 'ACCOUNT_BLOCKED',
                'message' => 'This account has been blocked. Contact support.',
            ], 403);
        }

        // Legacy users: must_reset_password=true means we don't trust the password
        // they typed (it may be a placeholder set during import). Send a fresh
        // reset email and tell the frontend to show the migration notice.
        if ($customer->must_reset_password) {
            Password::broker('customers')->sendResetLink(['email' => $customer->email]);

            return response()->json([
                'code'    => 'LEGACY_PASSWORD_RESET_REQUIRED',
                'message' => 'Your account was migrated from our previous platform. We just emailed you a secure link to set a new password.',
                'email'   => $customer->email,
            ], 409);
        }

        // Hash::check throws if the stored hash isn't valid bcrypt (e.g. a
        // corrupted import). Treat that as a failed login rather than a 500.
        try {
            $passwordMatches = $customer->password && Hash::check($request->password, $customer->password);
        } catch (\RuntimeException) {
            $passwordMatches = false;
        }

        if (! $passwordMatches) {
            RateLimiter::hit($throttleKey, 60);
            return $this->invalidCredentials();
        }

        RateLimiter::clear($throttleKey);
        $customer->trackLogin($request->ip());

        return response()->json([
            'data' => [
                'token' => $customer->createToken('storefront')->plainTextToken,
                'user'  => $this->customerShape($customer),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->customerShape($request->user())]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'string', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $customer = $request->user();

        if (! Hash::check($data['current_password'], $customer->password)) {
            return response()->json([
                'code'    => 'INVALID_CREDENTIALS',
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $customer->forceFill(['password' => $data['password']])->save();
        // Revoke all other tokens so any compromised session is killed.
        $customer->tokens()->where('id', '!=', $customer->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    private function invalidCredentials(): JsonResponse
    {
        return response()->json([
            'code'    => 'INVALID_CREDENTIALS',
            'message' => 'The email or password you entered is incorrect.',
        ], 401);
    }

    private function customerShape(Customer $customer): array
    {
        return [
            'id'                  => $customer->id,
            'name'                => $customer->full_name,
            'first_name'          => $customer->first_name,
            'last_name'           => $customer->last_name,
            'email'               => $customer->email,
            'phone'               => $customer->phone,
            'must_reset_password' => (bool) $customer->must_reset_password,
            'is_legacy'           => (bool) $customer->is_legacy,
            'created_at'          => $customer->created_at?->toISOString(),
        ];
    }
}
