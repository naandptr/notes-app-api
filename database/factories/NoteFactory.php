<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by'   => User::factory(),
            'note_title'   => fake()->sentence(),
            'note_content' => fake()->paragraph(),
        ];
    }
}
