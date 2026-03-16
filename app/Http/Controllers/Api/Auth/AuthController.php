<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

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
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->retrieved(null, 'Password reset link sent to your email');
        }

        return $this->badRequest(__($status));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = \DB::table('password_reset_tokens')->get()->first(function ($item) use ($request) {
            return \Hash::check($request->token, $item->token);
        });

        if (!$record) {
            return $this->badRequest('Invalid or expired reset token');
        }

        $request->merge(['email' => $record->email]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->retrieved(null, 'Password reset successfully');
        }

        return $this->badRequest(__($status));
    }
    public function logout()
    {
        auth('api')->logout();
        return $this->loggedOut();
    }
}