<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    #[Test]
    public function it_registers_a_new_user_successfully()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'data',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    #[Test]
    public function it_fails_register_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_register_with_mismatched_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_register_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_logs_in_successfully_with_correct_credentials()
    {
        User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'code',
                'message',
                'data' => ['token', 'type', 'expires_in', 'user'],
            ]);
    }

    #[Test]
    public function it_fails_login_with_wrong_password()
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => Hash::make('password123'),
            'google_id' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password');
    }

    #[Test]
    public function it_fails_login_with_unregistered_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'notfound@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_fails_login_if_email_not_verified()
    {
        User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => null, 
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Please verify your email first');
    }

    #[Test]
    public function it_changes_password_successfully()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/auth/change-password', [
                'current_password'      => 'password123',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password changed successfully');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    #[Test]
    public function it_fails_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/auth/change-password', [
                'current_password'      => 'wrongpassword',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Current password is incorrect');
    }

    #[Test]
    public function it_fails_change_password_with_mismatched_password()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/auth/change-password', [
                'current_password'      => 'password123',
                'password'              => 'newpassword123',
                'password_confirmation' => 'wrongpassword',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_fails_change_password_for_google_oauth_user()
    {
        $user = User::factory()->create([
            'email'             => 'test@gmail.com',
            'password'          => null,
            'google_id'         => '123456789',
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/auth/change-password', [
                'current_password'      => 'somepassword',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Google OAuth users cannot change password');
    }

    #[Test]
    public function it_allows_change_password_for_user_with_both_google_and_password()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => '123456789',
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/auth/change-password', [
                'current_password'      => 'password123',
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password changed successfully');
    }

    #[Test]
    public function it_fails_change_password_without_token()
    {
        $response = $this->postJson('/api/auth/change-password', [
            'current_password'      => 'password123',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_logs_out_successfully()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully');
    }

    #[Test]
    public function it_fails_logout_without_token()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}