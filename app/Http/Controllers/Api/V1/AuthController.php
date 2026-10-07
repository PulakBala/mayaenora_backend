<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new customer account.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'phone.required_without' => 'Please provide either a phone number or email address.',
            'email.required_without' => 'Please provide either a phone number or email address.',
            'phone.unique' => 'This phone number is already registered.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customerRoleId = Role::where('slug', 'customer')->value('id') ?? 1;

        $referrerId = null;
        if ($request->filled('referral_code')) {
            $refCode = trim($request->referral_code);
            $referrer = User::where('referral_code', $refCode)->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        $user = User::create([
            'role_id' => $customerRoleId,
            'name' => trim($request->name),
            'phone' => $request->filled('phone') ? trim($request->phone) : null,
            'email' => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'password' => Hash::make($request->password),
            'referred_by_id' => $referrerId,
            'status' => 'active',
        ]);

        $token = $user->createToken('customer-auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account registered successfully.',
            'data' => [
                'user' => new UserResource($user->load('role')),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Authenticate customer with Phone/Email and Password.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your phone number or email address.',
            'password.required' => 'Please enter your password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $login = trim($request->login);

        // Find by email or phone
        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'errors' => [
                    'login' => ['The provided phone/email or password is incorrect.'],
                ],
            ], 422);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended or inactive. Please contact customer support.',
            ], 403);
        }

        $token = $user->createToken('customer-auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user->load(['role', 'customerAddresses'])),
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Revoke current Sanctum token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}

