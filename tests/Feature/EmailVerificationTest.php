<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createUnverifiedUser(): User
    {
        return User::factory()->create([
            'email'             => 'test@example.com',
            'password'          => Hash::make('password123'),
            'google_id'         => null,
            'email_verified_at' => null,
        ]);
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }

    #[Test]
    public function it_verifies_email_successfully()
    {
        Event::fake();

        $user = $this->createUnverifiedUser();
        $url  = $this->verificationUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Email verified successfully');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    #[Test]
    public function it_returns_200_if_email_already_verified()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $url = $this->verificationUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Email already verified');
    }

    #[Test]
    public function it_fails_with_invalid_hash()
    {
        $user = $this->createUnverifiedUser();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => 'invalidhash',
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Invalid verification link');
    }

    #[Test]
    public function it_fails_with_expired_link()
    {
        $user = $this->createUnverifiedUser();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(1), // sudah expired
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_fails_with_invalid_user_id()
    {
        $user = $this->createUnverifiedUser();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => 'non-existent-id',
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($url);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_resends_verification_email_successfully()
    {
        $user = $this->createUnverifiedUser();

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Verification email resent');
    }

    #[Test]
    public function it_returns_200_if_resend_to_already_verified_email()
    {
        $user = User::factory()->create([
            'email'             => 'test@example.com',
            'google_id'         => null,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Email already verified');
    }

    #[Test]
    public function it_returns_404_if_email_not_found_on_resend()
    {
        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'notfound@example.com',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'User not found');
    }

    #[Test]
    public function it_fails_resend_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }
}