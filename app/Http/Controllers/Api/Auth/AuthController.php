<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    protected string $resourceName = 'User';

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Send verification email
        $user->sendEmailVerificationNotification();

        return $this->created(null, 'Registration successful. Please check your email to verify your account.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $token = auth('api')->attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        \Log::info('Login attempt', [
            'email' => $request->email,
            'token' => $token,
        ]);

        if (!$token) {
            return $this->unauthorized('Invalid email or password');
        }

        $user = auth('api')->user();

        \Log::info('Logged in user', ['user' => $user]);

        if (!$user->hasVerifiedEmail()) {
            auth('api')->logout();
            return $this->forbidden('Please verify your email first');
        }

        return $this->retrieved([
            'token'      => $token,
            'type'       => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user'       => $user,
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return $this->loggedOut();
    }
}