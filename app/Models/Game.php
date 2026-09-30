<?php

namespace App\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['user_id', 'intro_text', 'finale_text', 'music_path', 'finale_music_path', 'completion_sound_path', 'started_at', 'completed_at'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * How many pictures the player puts together, each followed by its message.
     */
    public const int PUZZLES_COUNT = 9;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Puzzle, $this>
     */
    public function puzzles(): HasMany
    {
        return $this->hasMany(Puzzle::class)->orderBy('position');
    }

    /**
     * The first puzzle the player has not put together yet.
     */
    public function currentPuzzle(): ?Puzzle
    {
        return $this->puzzles()->whereNull('solved_at')->first();
    }

    public function solvedPuzzlesCount(): int
    {
        return $this->puzzles()->whereNotNull('solved_at')->count();
    }

    /**
     * A game can be played once it has all pictures and each of them has its message.
     */
    public function isReady(): bool
    {
        return $this->puzzles()->count() === self::PUZZLES_COUNT && $this->puzzlesWithoutMessageCount() === 0;
    }

    public function puzzlesWithoutMessageCount(): int
    {
        return $this->puzzles()->where(fn ($query) => $query->whereNull('message')->orWhere('message', ''))->count();
    }

    /**
     * Wipe the player's progress so the game can be played from the start.
     */
    public function resetProgress(): void
    {
        DB::transaction(function (): void {
            $this->puzzles()->update(['solved_at' => null, 'solve_seconds' => null]);
            $this->update(['started_at' => null, 'completed_at' => null]);
        });
    }

    /**
     * Close gaps in puzzle positions, keeping their current order.
     */
    public function resequencePuzzles(): void
    {
        $this->puzzles()->get()->each(function (Puzzle $puzzle, int $index): void {
            if ($puzzle->position !== $index + 1) {
                $puzzle->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Put the puzzles in the given order.
     *
     * Runs in two passes because (game_id, position) is unique, so puzzles
     * cannot simply swap places one row at a time.
     *
     * @param  list<int|string>  $orderedPuzzleIds
     */
    public function reorderPuzzles(array $orderedPuzzleIds): void
    {
        DB::transaction(function () use ($orderedPuzzleIds): void {
            $puzzles = $this->puzzles()->whereKey($orderedPuzzleIds)->get()->keyBy('id');
            $orderedPuzzleIds = array_values(array_filter($orderedPuzzleIds, fn ($id) => $puzzles->has($id)));

            foreach ($orderedPuzzleIds as $index => $id) {
                $puzzles[$id]->update(['position' => 100 + $index]);
            }

            foreach ($orderedPuzzleIds as $index => $id) {
                $puzzles[$id]->update(['position' => $index + 1]);
            }
        });
    }

    /**
     * Same-origin URL, so the browser can fetch and decode the track whatever host the game is opened on.
     */
    public function introHtml(): string
    {
        return (string) str($this->intro_text ?? '')->sanitizeHtml();
    }

    public function finaleHtml(): string
    {
        return (string) str($this->finale_text ?? '')->sanitizeHtml();
    }

    public function musicUrl(): ?string
    {
        return $this->music_path ? asset('music/'.$this->music_path) : null;
    }

    public function finaleMusicUrl(): ?string
    {
        return $this->finale_music_path ? asset('music/'.$this->finale_music_path) : null;
    }

    public function completionSoundUrl(): ?string
    {
        return $this->completion_sound_path ? asset('music/'.$this->completion_sound_path) : null;
    }
}
