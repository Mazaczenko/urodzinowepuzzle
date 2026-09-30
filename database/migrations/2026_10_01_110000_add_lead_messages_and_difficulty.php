<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->text('lead_message')->nullable()->after('image_path');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('difficulty')->default('growing')->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn('lead_message');
        });
    }
};
