<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The wishes read like an invitation to play, so they move to the intro
     * and the finale gets a text of its own.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->text('intro_text')->nullable()->after('user_id');
            $table->renameColumn('wishes', 'finale_text');
        });

        DB::table('games')->update([
            'intro_text' => DB::raw('finale_text'),
            'finale_text' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('games')->whereNull('finale_text')->update(['finale_text' => DB::raw('intro_text')]);

        Schema::table('games', function (Blueprint $table) {
            $table->renameColumn('finale_text', 'wishes');
            $table->dropColumn('intro_text');
        });
    }
};
