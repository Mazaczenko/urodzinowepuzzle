<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Move the pictures from storage/app/public/puzzles to public/puzzles,
     * where the path is now relative to the "puzzles" disk.
     */
    public function up(): void
    {
        File::ensureDirectoryExists(public_path('puzzles'));

        DB::table('puzzles')->where('image_path', 'like', 'puzzles/%')->orderBy('id')->each(function (object $puzzle): void {
            $file = basename($puzzle->image_path);
            $from = storage_path('app/public/puzzles/'.$file);

            if (File::exists($from) && ! File::exists(public_path('puzzles/'.$file))) {
                File::move($from, public_path('puzzles/'.$file));
            }

            DB::table('puzzles')->where('id', $puzzle->id)->update(['image_path' => $file]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        File::ensureDirectoryExists(storage_path('app/public/puzzles'));

        DB::table('puzzles')->where('image_path', 'not like', 'puzzles/%')->orderBy('id')->each(function (object $puzzle): void {
            $from = public_path('puzzles/'.$puzzle->image_path);

            if (File::exists($from)) {
                File::move($from, storage_path('app/public/puzzles/'.$puzzle->image_path));
            }

            DB::table('puzzles')->where('id', $puzzle->id)->update(['image_path' => 'puzzles/'.$puzzle->image_path]);
        });
    }
};
