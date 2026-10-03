<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the stats page reads: one row for each player in each finished game, the facts about how they did. Wins, ranks
 * and streaks are not stored, they are worked out when the page is read so a rule (how a tie is decided, whether a
 * solo game counts) can change without the data being collected again. The whole sheet is kept for the same reason.
 *
 * A game is only recorded when every player has played all thirteen turns, `user_id` is the Costs to Expect user, the
 * app has no users of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_stat', static function (Blueprint $table) {

            // The ids are case sensitive hashes, a case insensitive collation treats two ids that only differ in
            // case as the same one, which would break the unique index
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_bin';

            $table->id();
            $table->string('user_id', 64);
            $table->string('game_id', 64);
            $table->string('player_id', 64);
            $table->string('player_name');
            $table->unsignedTinyInteger('players_in_game');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('upper');
            $table->unsignedTinyInteger('upper_bonus');
            $table->unsignedSmallInteger('lower');
            $table->unsignedTinyInteger('yahtzees');
            $table->json('sheet');
            $table->dateTime('game_created_at');
            $table->dateTime('game_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_id', 'player_id']);
            $table->index(['user_id', 'game_created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_stat');
    }
};
