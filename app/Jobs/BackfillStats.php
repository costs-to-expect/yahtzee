<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Game\RecordStats;
use App\Api\Service;
use App\Models\GameStat;
use App\Models\StatsBackfill;
use App\Notifications\ApiError;
use App\Support\GameStatBuilder;
use App\Support\GameStatResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Throwable;

/**
 * Collects the stats of the games a player finished before the stats existed: every finished game, with its players
 * (include-players) in the order they were created, and for each one that is not already in the stats, its score
 * sheets, all recorded the way a game is recorded when it is completed (RecordStats).
 *
 * There is one job, ever, for each user. The row in stats_backfill is what makes it so (see StatsBackfill): a job only
 * does anything if it moves its row from queued to running, whatever else is happening, and the unique lock stops a
 * second copy being queued. It carries the player's bearer token, as the account deletion jobs do, so it can read
 * the games the way the player can, the app has no other way to.
 *
 * It starts from the first game every time it runs, a game that is already in the stats costs nothing but a query, so a
 * run that stopped half way carries on quickly and the counts are always the counts of one run. It asks the API for
 * the games at a gentle pace, the player is using the API too.
 *
 * If the API says the token has been revoked (the player signed out) it stops and the row is paused, the next time
 * the player is signed in it carries on. Any other problem is tried again by the queue and, if it keeps happening,
 * the row is failed and the error is emailed.
 */
class BackfillStats implements ShouldQueue, ShouldBeEncrypted, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PAGE_SIZE = 100;

    /** Rate limited, wait and ask again this many times before giving up until the queue tries again */
    private const RATE_LIMIT_ATTEMPTS = 3;

    public $timeout = 900;

    public $tries = 3;

    /**
     * How long the unique lock holds, it has to run out before a row is taken for lost (StatsBackfill::STUCK_AFTER_MINUTES),
     * otherwise a job queued for a row that lost its job could be turned away by the lock of the one that was lost
     */
    public $uniqueFor = 1500;

    public function __construct(
        private string $bearer_token,
        private string $user_id,
        private string $resource_type_id,
        private string $resource_id
    ) {
    }

    public function uniqueId(): string
    {
        return $this->user_id;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        if (StatsBackfill::begin($this->user_id) === false) {
            return;
        }

        try {
            $this->backfill(new Service($this->bearer_token));
        } catch (Throwable $e) {
            // So the queue can try again, only a queued row can be started
            StatsBackfill::release($this->user_id);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        StatsBackfill::fail($this->user_id, $exception->getMessage());

        Notification::route('mail', Config::get('app.config')['error_email'])
            ->notify(new ApiError(
                'Unable to collect the stats of the finished games of user ' . $this->user_id,
                $exception->getMessage()
            ));
    }

    private function backfill(Service $api): void
    {
        $total = null;
        $seen = 0;
        $counted = 0;
        $skipped = [];
        $offset = 0;

        do {
            $response = $this->request(fn (): array => $api->getGames(
                $this->resource_type_id,
                $this->resource_id,
                ['complete' => 1, 'include-players' => 1, 'sort' => 'created:asc', 'offset' => $offset, 'limit' => self::PAGE_SIZE]
            ));

            if ($response === null) {
                return;
            }

            $total ??= (int) ($response['headers']['X-Total-Count'][0] ?? count($response['content']));
            $games = $response['content'];

            foreach ($games as $game) {
                $seen++;

                if ($this->isRecorded((string) $game['id'])) {
                    $counted++;
                } else {
                    $result = $this->record($api, $game);

                    if ($result === null) {
                        return;
                    }

                    if ($result->isCounted()) {
                        $counted++;
                    } else {
                        $skipped[$result->reason] = ($skipped[$result->reason] ?? 0) + 1;
                    }
                }

                if (StatsBackfill::progress($this->user_id, $total, $seen, $counted, $skipped) === false) {
                    // The row has gone or somebody has changed it, either way there is nothing to carry on for
                    if (StatsBackfill::query()->where('user_id', $this->user_id)->doesntExist()) {
                        // The account was deleted while a game was being recorded, the game goes with it
                        GameStat::query()->where('user_id', $this->user_id)->delete();
                    }

                    return;
                }
            }

            $offset += self::PAGE_SIZE;
        } while (count($games) === self::PAGE_SIZE);

        // A player with no finished games has had no progress written yet, a finished row always has its counts
        if (StatsBackfill::progress($this->user_id, $total, $seen, $counted, $skipped) === true) {
            StatsBackfill::finish($this->user_id);
        }
    }

    private function isRecorded(string $game_id): bool
    {
        return GameStat::query()->where('user_id', $this->user_id)->where('game_id', $game_id)->exists();
    }

    /**
     * Reads the game's score sheets and records the game, null when the token has been revoked
     *
     * @param array<string, mixed> $game
     */
    private function record(Service $api, array $game): ?GameStatResult
    {
        $response = $this->request(
            fn (): array => $api->getGameScoreSheets($this->resource_type_id, $this->resource_id, (string) $game['id']),
            true
        );

        if ($response === null) {
            return null;
        }

        // The game was deleted since the list was read, there is nothing to count
        if ($response['status'] === 404) {
            return GameStatResult::skipped(GameStatBuilder::UNREADABLE);
        }

        return (new RecordStats())($this->user_id, $game, $game['players']['collection'] ?? [], $response['content']);
    }

    /**
     * Makes a request to the API, after a pause. null when the token has been revoked, which pauses the row. A response
     * that is not a success is an exception, the queue tries again, except for a rate limit, which is waited out here.
     *
     * @param callable(): array<string, mixed> $request
     * @param bool $missing_is_fine A 404 is a result, not an error
     * @return array<string, mixed>|null
     */
    private function request(callable $request, bool $missing_is_fine = false): ?array
    {
        $limited = 0;

        while (true) {
            usleep(max(0, (int) Config::get('app.config.stats_backfill_pause_ms')) * 1000);

            $response = $request();

            if ($response['status'] === 200 || ($missing_is_fine && $response['status'] === 404)) {
                return $response;
            }

            if ($response['status'] === 401) {
                StatsBackfill::pause($this->user_id);

                return null;
            }

            if ($response['status'] === 429 && ++$limited < self::RATE_LIMIT_ATTEMPTS) {
                sleep(max(0, (int) Config::get('app.config.stats_backfill_backoff_seconds')));

                continue;
            }

            throw new RuntimeException(
                'The API answered ' . $response['status'] . ' while collecting the stats: ' . json_encode($response['content'])
            );
        }
    }
}
