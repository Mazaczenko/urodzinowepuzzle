<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private const array COLUMNS = ['music_path', 'finale_music_path'];

    /**
     * Move the tracks from storage/app/public/music to public/music,
     * where the path is now relative to the "music" disk.
     */
    public function up(): void
    {
        File::ensureDirectoryExists(public_path('music'));

        foreach (self::COLUMNS as $column) {
            DB::table('games')->where($column, 'like', 'music/%')->orderBy('id')->each(function (object $game) use ($column): void {
                $file = basename($game->{$column});
                $from = storage_path('app/public/music/'.$file);

                if (File::exists($from) && ! File::exists(public_path('music/'.$file))) {
                    File::move($from, public_path('music/'.$file));
                }

                DB::table('games')->where('id', $game->id)->update([$column => $file]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        File::ensureDirectoryExists(storage_path('app/public/music'));

        foreach (self::COLUMNS as $column) {
            DB::table('games')->whereNotNull($column)->where($column, 'not like', 'music/%')->orderBy('id')->each(function (object $game) use ($column): void {
                $from = public_path('music/'.$game->{$column});

                if (File::exists($from)) {
                    File::move($from, storage_path('app/public/music/'.$game->{$column}));
                }

                DB::table('games')->where('id', $game->id)->update([$column => 'music/'.$game->{$column}]);
            });
        }
    }
};
