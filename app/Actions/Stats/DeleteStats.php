<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Models\GameStat;
use App\Models\StatsBackfill;
use Illuminate\Support\Facades\DB;

/**
 * Forgets everything the stats hold about a user, for when their account is deleted, or their Yahtzee account, which
 * has their games in it.
 *
 * The row of the job that collects their older games goes first. A job that is running finds it gone the next time it
 * records progress, stops, and takes the game it was recording with it, and one that is still queued does nothing.
 */
class DeleteStats
{
    public function __invoke(string $user_id): void
    {
        DB::transaction(static function () use ($user_id): void {
            StatsBackfill::query()->where('user_id', $user_id)->delete();
            GameStat::query()->where('user_id', $user_id)->delete();
        });
    }
}
