<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForgotResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);
    }

    #[Test]
    public function it_sends_reset_password_link_successfully()
    {
        Notification::fake();

        $user = $this->createUser();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password reset link sent to your email');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function it_fails_forgot_password_with_unregistered_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'notfound@example.com',
        ]);

        $response->assertStatus(400);
    }

    #[Test]
    public function it_fails_forgot_password_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_forgot_password_with_missing_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_resets_password_successfully()
    {
        $user  = $this->createUser();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password reset successfully');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    #[Test]
    public function it_fails_reset_password_with_invalid_token()
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/reset-password', [
            'token'                 => 'invalidtoken',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired reset token');
    }

    #[Test]
    public function it_fails_reset_password_with_mismatched_password()
    {
        $user  = $this->createUser();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'password'              => 'newpassword123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_reset_password_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_reset_password_with_short_password()
    {
        $user  = $this->createUser();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token'                 => $token,
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }
}