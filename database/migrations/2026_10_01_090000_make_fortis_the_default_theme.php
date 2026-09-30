<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fortis becomes the default look, also for games that were only on the old default.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('theme')->default('fortis')->change();
        });

        DB::table('games')->where('theme', 'classic')->update(['theme' => 'fortis']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('theme')->default('classic')->change();
        });
    }
};
