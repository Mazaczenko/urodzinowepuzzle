<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Puzzle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Puzzle>
 */
class PuzzleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The position is left out on purpose: the model appends new puzzles at the end.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'image_path' => fake()->uuid().'.webp',
            'message' => fake()->sentences(2, true),
        ];
    }

    public function solved(): static
    {
        return $this->state(fn (array $attributes) => [
            'solved_at' => now(),
            'solve_seconds' => fake()->numberBetween(20, 300),
        ]);
    }
}
