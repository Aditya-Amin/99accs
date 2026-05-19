<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Frontend sends { name, email, password, password_confirmation }
        // We also accept { first_name, last_name } for direct API callers
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'first_name'  => 'sometimes|string|max:255',
            'last_name'   => 'sometimes|string|max:255',
            'email'       => 'required|email|unique:customers,email',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'nullable|string|max:30',
        ]);

        // Split single `name` field if first_name/last_name not provided
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
            'password'   => Hash::make($data['password']),
        ]);

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user'  => $this->customerShape($customer),
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $customer->createToken('storefront')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user'  => $this->customerShape($customer),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->customerShape($request->user())]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Password reset emails will be implemented when mail is configured.
        // Returns 200 either way to avoid email enumeration.
        return response()->json([
            'message' => 'If an account with that email exists, a reset link has been sent.',
        ]);
    }

    private function customerShape(Customer $customer): array
    {
        return [
            'id'         => $customer->id,
            'name'       => $customer->full_name,   // frontend AuthUser.name
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'email'      => $customer->email,
            'phone'      => $customer->phone,
            'created_at' => $customer->created_at?->toISOString(),
        ];
    }
}
