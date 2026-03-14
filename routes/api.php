<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\GoogleAuthController;

Route::prefix('auth/google')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

if (app()->environment('local')) {
    Route::get('/auth/test-token/{id}', function ($id) {
        $user = \App\Models\User::findOrFail($id);
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
        return response()->json(['message' => 'Logout berhasil']);
    });

    Route::post('/refresh', function () {
        return response()->json([
            'token' => auth('api')->refresh(),
            'type'  => 'bearer',
        ]);
    });
});
