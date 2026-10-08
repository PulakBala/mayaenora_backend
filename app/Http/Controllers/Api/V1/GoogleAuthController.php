<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirect customer to Google's OAuth 2.0 authorization screen.
     */
    public function redirect(Request $request): RedirectResponse|JsonResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect');
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');

        $returnPath = $request->query('redirect', '/account');
        $referralCode = $request->query('ref');

        if (! $clientId || ! $clientSecret) {
            $msg = 'Google login is not yet configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in backend .env.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 500);
            }

            return redirect("{$frontendUrl}/login?error=" . urlencode('Google authentication is temporarily unavailable. Please use phone or email login.'));
        }

        // Store state with target path and referral code
        $statePayload = json_encode([
            'redirect' => $returnPath,
            'ref' => $referralCode,
            'time' => time(),
        ]);
        $state = rtrim(strtr(base64_encode($statePayload), '+/', '-_'), '=');

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        $googleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'url' => $googleUrl,
            ]);
        }

        return redirect()->away($googleUrl);
    }

    /**
     * Handle incoming OAuth callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');

        // Check if user rejected or Google returned an error
        if ($request->has('error')) {
            $errorDesc = $request->query('error_description', 'Google sign-in was cancelled or encountered an error.');
            return redirect("{$frontendUrl}/login?error=" . urlencode($errorDesc));
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect("{$frontendUrl}/login?error=" . urlencode('Authorization code missing from Google callback.'));
        }

        // Decode state
        $redirectTarget = '/account';
        $referralCode = null;
        if ($request->filled('state')) {
            try {
                $rawState = base64_decode(strtr($request->state, '-_', '+/'));
                $stateData = json_decode($rawState, true);
                if (is_array($stateData)) {
                    $redirectTarget = $stateData['redirect'] ?? '/account';
                    $referralCode = $stateData['ref'] ?? null;
                }
            } catch (\Throwable $e) {
                // Ignore state decode errors
            }
        }

        try {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');

            // 1. Exchange code for Google Access Token
            $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenRes->successful()) {
                Log::error('Google OAuth token exchange failed', [
                    'status' => $tokenRes->status(),
                    'body' => $tokenRes->body(),
                ]);
                return redirect("{$frontendUrl}/login?error=" . urlencode('Unable to verify Google credentials with Google servers.'));
            }

            $accessToken = $tokenRes->json('access_token');
            if (! $accessToken) {
                return redirect("{$frontendUrl}/login?error=" . urlencode('Google did not provide an access token.'));
            }

            // 2. Fetch User Profile from Google userinfo API
            $userRes = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            if (! $userRes->successful()) {
                return redirect("{$frontendUrl}/login?error=" . urlencode('Failed to fetch user profile details from Google.'));
            }

            $googleData = $userRes->json();
            $email = strtolower(trim($googleData['email'] ?? ''));
            $googleId = $googleData['sub'] ?? null;
            $name = trim($googleData['name'] ?? explode('@', $email)[0]);
            $avatar = $googleData['picture'] ?? null;

            if (! $email) {
                return redirect("{$frontendUrl}/login?error=" . urlencode('Google account must have an associated email address.'));
            }

            // 3. Find or Create User
            $user = $this->resolveOrCreateGoogleUser($email, $name, $googleId, $avatar, $referralCode);

            // 4. Generate Sanctum Token
            $token = $user->createToken('google-auth')->plainTextToken;

            // 5. Redirect back to Frontend Callback Handler
            $callbackUrl = "{$frontendUrl}/auth/callback?token=" . urlencode($token) . '&redirect=' . urlencode($redirectTarget);
            return redirect()->away($callbackUrl);

        } catch (\Throwable $e) {
            Log::error('Google OAuth callback exception', ['exception' => $e->getMessage()]);
            return redirect("{$frontendUrl}/login?error=" . urlencode('An unexpected error occurred during Google sign-in.'));
        }
    }

    /**
     * Direct token exchange endpoint for client-side Google tokens (ID token or access token).
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $token = $request->input('id_token') ?? $request->input('credential') ?? $request->input('token') ?? $request->input('access_token');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Google credential/token is required.',
            ], 422);
        }

        try {
            // First attempt verifying as ID Token via Google tokeninfo
            $googleRes = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$token}");

            if (! $googleRes->successful()) {
                // Fallback attempt: verify as OAuth2 access token
                $googleRes = Http::withToken($token)->get('https://www.googleapis.com/oauth2/v3/userinfo');
            }

            if (! $googleRes->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired Google token.',
                ], 401);
            }

            $googleData = $googleRes->json();
            $email = strtolower(trim($googleData['email'] ?? ''));
            $googleId = $googleData['sub'] ?? null;
            $name = trim($googleData['name'] ?? explode('@', $email)[0]);
            $avatar = $googleData['picture'] ?? null;

            if (! $email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google account must have an associated email.',
                ], 422);
            }

            $user = $this->resolveOrCreateGoogleUser($email, $name, $googleId, $avatar, $request->input('referral_code'));
            $sanctumToken = $user->createToken('google-auth')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful.',
                'data' => [
                    'user' => new UserResource($user->load('role')),
                    'token' => $sanctumToken,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Google direct token verification exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify Google login at this time.',
            ], 500);
        }
    }

    /**
     * Resolve existing user or register a new customer account.
     */
    protected function resolveOrCreateGoogleUser(
        string $email,
        string $name,
        ?string $googleId,
        ?string $avatar,
        ?string $referralCode = null
    ): User {
        $user = User::where('email', $email)->first();

        if ($user) {
            $metadata = $user->metadata ?? [];
            $metadata['google_id'] = $googleId;
            $metadata['provider'] = 'google';

            $user->update([
                'avatar' => $user->avatar ?: $avatar,
                'metadata' => $metadata,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);

            return $user;
        }

        // New customer user registration
        $customerRoleId = Role::where('slug', 'customer')->value('id') ?? 1;

        $referrerId = null;
        if (! empty($referralCode)) {
            $refUser = User::where('referral_code', trim(strtoupper($referralCode)))->first();
            if ($refUser) {
                $referrerId = $refUser->id;
            }
        }

        return User::create([
            'role_id' => $customerRoleId,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'password' => Hash::make(Str::random(32)),
            'referred_by_id' => $referrerId,
            'email_verified_at' => now(),
            'status' => 'active',
            'metadata' => [
                'google_id' => $googleId,
                'provider' => 'google',
            ],
        ]);
    }
}

