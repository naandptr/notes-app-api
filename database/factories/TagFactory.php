<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'tag_name'   => fake()->unique()->word(),
        ];
    }
}