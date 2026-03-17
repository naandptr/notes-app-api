<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
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
    public function it_returns_all_tags_for_authenticated_user()
    {
        $user = $this->createUser();
        Tag::factory()->count(3)->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_does_not_return_other_users_tags()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        Tag::factory()->count(2)->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_fails_to_get_tags_without_token()
    {
        $response = $this->getJson('/api/tags');
        $response->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_tag_successfully()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/tags', ['tag_name' => 'Laravel']);

        $response->assertStatus(201)
            ->assertJsonPath('data.tag_name', 'Laravel');

        $this->assertDatabaseHas('tags', [
            'tag_name'       => 'Laravel',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_fails_to_create_duplicate_tag()
    {
        $user = $this->createUser();
        Tag::factory()->create(['created_by' => $user->id, 'tag_name' => 'Laravel']);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/tags', ['tag_name' => 'Laravel']);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Tag already exists');
    }

    #[Test]
    public function it_allows_same_tag_name_for_different_users()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        Tag::factory()->create(['created_by' => $other->id, 'tag_name' => 'Laravel']);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/tags', ['tag_name' => 'Laravel']);

        $response->assertStatus(201);
    }

    #[Test]
    public function it_fails_to_create_tag_with_missing_name()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/tags', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_deletes_a_tag_successfully()
    {
        $user = $this->createUser();
        $tag  = Tag::factory()->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/tags/' . $tag->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    #[Test]
    public function it_fails_to_delete_other_users_tag()
    {
        $user  = $this->createUser();
        $other = User::factory()->create();
        $tag   = Tag::factory()->create(['created_by' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/tags/' . $tag->id);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_404_when_tag_not_found()
    {
        $user = $this->createUser();

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson('/api/tags/non-existent-id');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_creates_note_with_tags()
    {
        $user = $this->createUser();
        $tag  = Tag::factory()->create(['created_by' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/notes', [
                'note_title'   => 'Test Note',
                'note_content' => 'Test Content',
                'tag_ids'      => [$tag->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tags.0.id', $tag->id);
    }

    #[Test]
    public function it_syncs_tags_when_updating_note()
    {
        $user = $this->createUser();
        $tag1 = Tag::factory()->create(['created_by' => $user->id]);
        $tag2 = Tag::factory()->create(['created_by' => $user->id]);
        $note = Note::factory()->create(['created_by' => $user->id]);
        $note->tags()->attach($tag1->id);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/notes/' . $note->id, [
                'tag_ids' => [$tag2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.tags')
            ->assertJsonPath('data.tags.0.id', $tag2->id);
    }

    #[Test]
    public function it_removes_all_tags_when_tag_ids_is_empty()
    {
        $user = $this->createUser();
        $tag  = Tag::factory()->create(['created_by' => $user->id]);
        $note = Note::factory()->create(['created_by' => $user->id]);
        $note->tags()->attach($tag->id);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson('/api/notes/' . $note->id, [
                'tag_ids' => [],
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.tags');
    }
}