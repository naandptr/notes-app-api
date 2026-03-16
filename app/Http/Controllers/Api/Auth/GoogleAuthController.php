<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

class GoogleAuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/google/redirect",
     *     tags={"Google Auth"},
     *     summary="Get Google OAuth redirect URL",
     *     @OA\Response(
     *         response=200,
     *         description="Google redirect URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="https://accounts.google.com/o/oauth2/auth?...")
     *         )
     *     )
     * )
     */

    // Redirect to Google (return URL to frontend)
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google/callback",
     *     tags={"Google Auth"},
     *     summary="Handle callback dari Google setelah login",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Authorization code dari Google",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1Qi..."),
     *             @OA\Property(property="type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="string", example="019cea71-d973-73d3-9c20-d2bea6a6826f"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@gmail.com"),
     *                 @OA\Property(property="google_id", type="string", example="107893421690057997601"),
     *                 @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/...")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Google authentication failed")
     * )
     */

    // Handle callbacks from Google
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Search user by google_id or email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                // Update google_id if logged in via email previously
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            } else {
                // Register new user
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                    'password'  => null,
                    'email_verified_at' => now(), // immediately verify email since it's from Google
                ]);
            }

            $token = auth('api')->login($user);

            return response()->json([
                'message' => 'Login successfully',
                'token'   => $token,
                'type'    => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user'    => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
