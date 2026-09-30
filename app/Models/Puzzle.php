<?php

namespace App\Models;

use Database\Factories\PuzzleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

#[Fillable(['game_id', 'position', 'image_path', 'caption', 'solve_seconds', 'solved_at'])]
class Puzzle extends Model
{
    /** @use HasFactory<PuzzleFactory> */
    use HasFactory;

    /**
     * Every picture is cut into a 5×2 grid of square pieces, so it has to be 5:2.
     */
    public const int IMAGE_WIDTH = 1600;

    public const int IMAGE_HEIGHT = 640;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'solve_seconds' => 'integer',
            'solved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Puzzle $puzzle): void {
            $puzzle->position ??= (int) static::query()->where('game_id', $puzzle->game_id)->max('position') + 1;
        });

        static::updated(function (Puzzle $puzzle): void {
            if ($puzzle->wasChanged('image_path')) {
                Storage::disk('public')->delete($puzzle->getOriginal('image_path'));
            }
        });

        static::deleted(function (Puzzle $puzzle): void {
            Storage::disk('public')->delete($puzzle->image_path);
            $puzzle->game?->resequencePuzzles();
        });
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }

    public function isSolved(): bool
    {
        return $this->solved_at !== null;
    }

    /**
     * Crop an uploaded picture to 5:2, compress it to WebP and store it on the public disk.
     */
    public static function storeImage(UploadedFile $file): string
    {
        $driver = extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class;

        $encoded = ImageManager::usingDriver($driver)
            ->decodeBinary($file->get())
            ->cover(self::IMAGE_WIDTH, self::IMAGE_HEIGHT)
            ->encode(new WebpEncoder(quality: 82));

        $path = 'puzzles/'.Str::ulid().'.webp';

        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
