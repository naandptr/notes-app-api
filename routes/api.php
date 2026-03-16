<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\NoteController;

Route::prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('callback', [GoogleAuthController::class, 'handleGoogleCallback']);
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

    Route::post('/logout', function () {
        auth('api')->logout();
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    });

    Route::post('/refresh', function () {
        return response()->json([
            'token' => auth('api')->refresh(),
            'type'  => 'bearer',
        ]);
    });

    Route::apiResource('notes', NoteController::class);
});
