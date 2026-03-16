<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteTest extends TestCase
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

    private function authHeader(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    #[Test]
    public function it_returns_all_notes_for_authenticated_user()
    {
        $user = $this->createUser();
        Note::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/notes');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_does_not_return_other_users_notes()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        Note::factory()->count(2)->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/notes');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_fails_to_get_notes_without_token()
    {
        $response = $this->getJson('/api/notes');
        $response->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_note_successfully()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/notes', [
                'note_title'   => 'Test Note',
                'note_content' => 'Test Content',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.note_title', 'Test Note');

        $this->assertDatabaseHas('notes', [
            'note_title'  => 'Test Note',
            'created_by'  => $user->id,
        ]);
    }

    #[Test]
    public function it_fails_to_create_note_with_missing_fields()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/notes', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_returns_a_single_note()
    {
        $user = $this->createUser();
        $note = Note::factory()->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/notes/' . $note->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $note->id);
    }

    #[Test]
    public function it_returns_403_when_accessing_other_users_note()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        $note  = Note::factory()->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/notes/' . $note->id);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_when_note_not_found()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/notes/non-existent-id');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_updates_a_note_successfully()
    {
        $user = $this->createUser();
        $note = Note::factory()->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/notes/' . $note->id, [
                'note_title'   => 'Updated Title',
                'note_content' => 'Updated Content',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.note_title', 'Updated Title');
    }

    #[Test]
    public function it_fails_to_update_other_users_note()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        $note  = Note::factory()->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/notes/' . $note->id, [
                'note_title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_deletes_a_note_successfully()
    {
        $user = $this->createUser();
        $note = Note::factory()->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/notes/' . $note->id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Note deleted successfully');

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    #[Test]
    public function it_fails_to_delete_other_users_note()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        $note  = Note::factory()->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/notes/' . $note->id);

        $response->assertStatus(403);
    }
}