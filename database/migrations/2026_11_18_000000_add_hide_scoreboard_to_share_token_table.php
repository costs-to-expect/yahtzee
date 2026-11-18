<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the person running a game switch off the public scoreboard (the "Everyone" panel) on its public score sheets.
 *
 * The flag is written on every token of a game, the tokens are deleted when the game is completed or deleted so the
 * flag needs no clean up of its own. It defaults to false, so every token that exists today, and so every game in
 * progress, keeps showing the scoreboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_token', static function (Blueprint $table) {
            $table->boolean('hide_scoreboard')->default(false)->after('parameters');
        });
    }

    public function down(): void
    {
        Schema::table('share_token', static function (Blueprint $table) {
            $table->dropColumn('hide_scoreboard');
        });
    }
};
