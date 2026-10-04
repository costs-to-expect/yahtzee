<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Jobs\BackfillStats;
use App\Models\StatsBackfill;

/**
 * Starts the job that collects the stats of a player's finished games when it has to be started: the first time we
 * see the player, and again if it stopped because they signed out or its job was lost. Most of the time it is one
 * query that finds nothing to do.
 *
 * The row in stats_backfill decides who starts the job, whoever moves it to queued, so any number of requests at the
 * same moment start it once. A row that is complete never starts again, and one that failed needs a person.
 */
class StartBackfill
{
    public function __invoke(string $user_id, ?string $bearer_token, string $resource_type_id, string $resource_id): void
    {
        if ($bearer_token === null || $bearer_token === '') {
            return;
        }

        $state = StatsBackfill::query()->where('user_id', $user_id)->value('state');

        if ($state === StatsBackfill::COMPLETE || $state === StatsBackfill::FAILED) {
            return;
        }

        $start = $state === null ? StatsBackfill::claim($user_id) : StatsBackfill::requeue($user_id);

        if ($start === true) {
            BackfillStats::dispatch($bearer_token, $user_id, $resource_type_id, $resource_id);
        }
    }
}
