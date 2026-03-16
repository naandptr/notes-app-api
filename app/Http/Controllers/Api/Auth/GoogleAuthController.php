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
    // Redirect to Google (return URL to frontend)
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

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
