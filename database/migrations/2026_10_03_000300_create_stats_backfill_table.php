<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row for each user, ever. The row is what makes sure the job that collects the stats of a user's finished games
 * is only started once: the unique `user_id` means only one request can claim it, and a `complete` row is never
 * claimed again. The counts are what the stats page shows while the job runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats_backfill', static function (Blueprint $table) {

            // The user ids are case sensitive hashes, see game_stat
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_bin';

            $table->id();
            $table->string('user_id', 64)->unique();
            $table->string('state', 16)->default('queued');
            $table->unsignedInteger('games_total')->nullable();
            $table->unsignedInteger('games_seen')->default(0);
            $table->unsignedInteger('games_counted')->default(0);
            $table->unsignedInteger('games_skipped')->default(0);
            $table->json('skipped')->nullable();
            $table->text('last_error')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_backfill');
    }
};
