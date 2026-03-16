<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\NoteController;
use App\Models\User;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::get('auth/reset-password/{token}', function (Request $request, string $token) {
        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Token valid',
            'data'    => [
                'token' => $token,
                'email' => $request->query('email'),
            ],
        ]);
    })->name('password.reset');

    // Email verification 
    Route::get('verify-email/{id}/{hash}', function (Request $request, $id, $hash) {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->email))) {
            return response()->json([
                'success' => false,
                'code'    => 403,
                'message' => 'Invalid verification link'
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Email already verified'
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Email verified successfully'
        ]);
    })->middleware('signed')->name('verification.verify');

    // Resend email verification link
    Route::post('resend-verification', function (Request $request) {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code'    => 404,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Email already verified'
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Verification email resent'
        ]);
    })->middleware('throttle:6,1');

    Route::prefix('google')->group(function () {
        Route::get('redirect', [GoogleAuthController::class, 'redirectToGoogle']);
        Route::get('callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    });
});

if (app()->environment('local')) {
    Route::get('/auth/test-token/{index?}', function ($index = 0) {
        $user = \App\Models\User::offset($index)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found at index ' . $index,
            ], 404);
        }

        $token = auth('api')->login($user);
        return response()->json(['token' => $token]);
    });
}

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function () {
        return response()->json(auth('api')->user());
    });

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    Route::apiResource('notes', NoteController::class);
});
