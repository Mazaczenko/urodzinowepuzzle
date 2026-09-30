<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'intro_text' => '<p>'.fake()->sentence().'</p>',
            'finale_text' => '<p>'.fake()->sentence().'</p>',
        ];
    }

    /**
     * A game with all nine pictures, ready to be played.
     */
    public function ready(): static
    {
        return $this->has(Puzzle::factory()->count(Game::PUZZLES_COUNT));
    }

    /**
     * A game whose every picture has been put together.
     */
    public function completed(): static
    {
        return $this
            ->has(Puzzle::factory()->count(Game::PUZZLES_COUNT)->solved())
            ->state(fn (array $attributes) => [
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);
    }
}
