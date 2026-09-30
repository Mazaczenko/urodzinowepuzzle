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
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['blik_code', 'blik_password']);
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->renameColumn('caption', 'message');
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->string('message')->nullable()->change();
        });

        Schema::table('puzzles', function (Blueprint $table) {
            $table->renameColumn('message', 'caption');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->text('blik_code')->nullable()->after('user_id');
            $table->text('blik_password')->nullable()->after('blik_code');
        });
    }
};
