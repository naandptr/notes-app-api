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

/**
 * @OA\Info(
 *     title="Notes App API",
 *     version="1.0.0",
 *     description="API documentation for Notes App"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class AuthController extends Controller
{
    use ApiResponse;

    protected string $resourceName = 'User';

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Register user baru",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Registration successful. Please check your email to verify your account."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Login manual",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1Qi..."),
     *                 @OA\Property(property="type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600),
     *                 @OA\Property(property="user", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Email not verified")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     tags={"Auth"},
     *     summary="Kirim link reset password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reset link sent"),
     *     @OA\Response(response=400, description="Email not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     tags={"Auth"},
     *     summary="Reset password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","password","password_confirmation"},
     *             @OA\Property(property="token", type="string", example="abc123..."),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully"),
     *     @OA\Response(response=400, description="Invalid or expired token"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout()
    {
        auth('api')->logout();
        return $this->loggedOut();
    }
}