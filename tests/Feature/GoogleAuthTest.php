<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(array $attributes = []): SocialiteUser
    {
        $googleUser = Mockery::mock(SocialiteUser::class);

        $googleUser->allows('getId')->andReturn($attributes['id'] ?? '123456789');
        $googleUser->allows('getName')->andReturn($attributes['name'] ?? 'Test User');
        $googleUser->allows('getEmail')->andReturn($attributes['email'] ?? 'test@gmail.com');
        $googleUser->allows('getAvatar')->andReturn($attributes['avatar'] ?? 'https://avatar.url/test.jpg');

        return $googleUser;
    }

    private function mockSocialite(SocialiteUser $googleUser): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->allows('stateless')->andReturnSelf();
        $provider->allows('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($provider);
    }

    /** @test */
    public function it_registers_new_user_via_google()
    {
        $googleUser = $this->mockGoogleUser();
        $this->mockSocialite($googleUser);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'google_id', 'avatar'],
            ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'test@gmail.com',
            'google_id' => '123456789',
        ]);
    }

    /** @test */
    public function it_logs_in_existing_user_via_google()
    {
        // Create an existing user
        $existingUser = User::factory()->create([
            'email'     => 'test@gmail.com',
            'google_id' => '123456789',
        ]);

        $googleUser = $this->mockGoogleUser();
        $this->mockSocialite($googleUser);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'test@gmail.com');

        // Make sure not to create a new user
        $this->assertDatabaseCount('users', 1);
    }

    /** @test */
    public function it_updates_google_id_if_user_registered_with_email()
    {
        // User registers manually, doesn't have a google_id yet
        $existingUser = User::factory()->create([
            'email'     => 'test@gmail.com',
            'google_id' => null,
        ]);

        $googleUser = $this->mockGoogleUser();
        $this->mockSocialite($googleUser);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email'     => 'test@gmail.com',
            'google_id' => '123456789',
        ]);
    }

    /** @test */
    public function it_returns_error_when_google_auth_fails()
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->allows('stateless')->andReturnSelf();
        $provider->allows('user')->andThrow(new \Exception('Google auth failed'));

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($provider);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(500)
            ->assertJsonPath('message', 'Google authentication failed');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}