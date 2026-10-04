<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\BackfillStats;
use App\Models\GameStat;
use App\Models\StatsBackfill;
use App\Notifications\ApiError;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * The job that collects the stats of the games a player finished before the stats existed.
 */
class BackfillStatsTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private const USER = 'u-1';

    protected function setUp(): void
    {
        parent::setUp();

        StatsBackfill::claim(self::USER);
    }

    /**
     * A game as the API returns it in a list with its players
     *
     * @param array<string, string> $players player id => name
     * @return array<string, mixed>
     */
    private function game(string $id, int $day = 1, array $players = ['p-1' => 'Ada', 'p-2' => 'Ben']): array
    {
        return [
            'id' => $id,
            'name' => 'Yahtzee game',
            'created' => sprintf('2026-09-%02d 18:00:00', $day),
            'updated' => sprintf('2026-09-%02d 19:00:00', $day),
            'complete' => 1,
            'players' => ['collection' => $this->playerCollection($players)],
        ];
    }

    /**
     * Ada 285 with a Yahtzee, Ben 105
     *
     * @return array<int, mixed>
     */
    private function finishedSheets(): array
    {
        return $this->scoreSheets([
            'p-1' => $this->finishedScoreSheet(),
            'p-2' => $this->finishedScoreSheet(
                ['ones' => 2, 'twos' => 4, 'threes' => 6, 'fours' => 8, 'fives' => 10, 'sixes' => 12],
                ['three_of_a_kind' => 18, 'full_house' => 0, 'large_straight' => 0, 'yahtzee' => 0, 'chance' => 15]
            ),
        ]);
    }

    /**
     * Fakes the list of finished games, in pages as the API does, and each game's score sheets
     *
     * @param list<array<string, mixed>> $games
     * @param array<string, array<int, mixed>>|Closure $sheets game id => score sheets, a game with none is not found
     * @param array<string, mixed> $overrides
     */
    private function fakeGames(array $games, array|Closure $sheets, array $overrides = []): void
    {
        Http::fake($overrides + [
            $this->items('?*') => function (Request $request) use ($games) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                return Http::response(
                    array_slice($games, (int) $query['offset'], (int) $query['limit']),
                    200,
                    ['X-Total-Count' => (string) count($games)]
                );
            },
            $this->items('/*/data') => function (Request $request) use ($sheets) {
                preg_match('#/items/([^/]+)/data#', $request->url(), $matches);

                $found = $sheets instanceof Closure ? $sheets($matches[1]) : ($sheets[$matches[1]] ?? null);

                return $found === null ? Http::response(['message' => 'Not found'], 404) : Http::response($found, 200);
            },
        ]);
    }

    private function job(string $user_id = self::USER): BackfillStats
    {
        return new BackfillStats(self::BEARER, $user_id, 'rt-1', 'r-1');
    }

    private function runJob(string $user_id = self::USER): void
    {
        $this->job($user_id)->handle();
    }

    private function backfill(): StatsBackfill
    {
        return StatsBackfill::query()->where('user_id', self::USER)->firstOrFail();
    }

    private function dataRequests(string $game_id): int
    {
        return count($this->sent('GET', "/items/{$game_id}/data"));
    }

    // The work

    public function test_every_finished_game_is_recorded_and_the_row_is_complete(): void
    {
        $unfinished = $this->scoreSheets(['p-1' => $this->finishedScoreSheet()]);

        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2), $this->game('g-3', 3)],
            ['g-1' => $this->finishedSheets(), 'g-2' => $this->finishedSheets(), 'g-3' => $unfinished]
        );

        $this->runJob();

        self::assertSame(4, GameStat::query()->count());
        self::assertSame(['g-1', 'g-1', 'g-2', 'g-2'], GameStat::query()->orderBy('id')->pluck('game_id')->all());

        $backfill = $this->backfill();
        self::assertSame(StatsBackfill::COMPLETE, $backfill->state);
        self::assertSame(3, $backfill->games_total);
        self::assertSame(3, $backfill->games_seen);
        self::assertSame(2, $backfill->games_counted);
        self::assertSame(1, $backfill->games_skipped);
        self::assertSame(['unfinished' => 1], $backfill->skipped);
        self::assertNull($backfill->last_error);
        self::assertNotNull($backfill->started_at);
        self::assertNotNull($backfill->finished_at);
    }

    public function test_a_game_is_recorded_for_the_user_as_it_is_when_it_is_completed(): void
    {
        $this->fakeGames([$this->game('g-1', 4)], ['g-1' => $this->finishedSheets()]);

        $this->runJob();

        $ada = GameStat::query()->where('player_id', 'p-1')->firstOrFail();
        self::assertSame(self::USER, $ada->user_id);
        self::assertSame('Ada', $ada->player_name);
        self::assertSame(285, $ada->score);
        self::assertSame(1, $ada->yahtzees);
        self::assertSame(2, $ada->players_in_game);
        self::assertSame('2026-09-04 18:00:00', $ada->game_created_at->toDateTimeString());
        self::assertSame('2026-09-04 19:00:00', $ada->game_completed_at?->toDateTimeString(), 'When the game was updated, that was when it was completed');
    }

    public function test_it_asks_for_the_finished_games_oldest_first_with_their_players_as_the_player(): void
    {
        $this->fakeGames([$this->game('g-1')], ['g-1' => $this->finishedSheets()]);

        $this->runJob();

        $requests = $this->sent('GET', '/items?');
        self::assertCount(1, $requests);
        self::assertStringContainsString('complete=1', $requests[0]->url());
        self::assertStringContainsString('include-players=1', $requests[0]->url());
        self::assertStringContainsString('sort=created%3Aasc', $requests[0]->url());
        self::assertStringContainsString('offset=0', $requests[0]->url());
        self::assertStringContainsString('limit=100', $requests[0]->url());
        self::assertSame(['Bearer ' . self::BEARER], $requests[0]->header('Authorization'));
        self::assertSame(['Bearer ' . self::BEARER], $this->sent('GET', '/g-1/data')[0]->header('Authorization'));
    }

    public function test_a_player_with_no_finished_games_is_complete_with_nothing_counted(): void
    {
        $this->fakeGames([], []);

        $this->runJob();

        $backfill = $this->backfill();
        self::assertSame(StatsBackfill::COMPLETE, $backfill->state);
        self::assertSame(0, $backfill->games_total);
        self::assertSame(0, $backfill->games_seen);
        self::assertSame(0, GameStat::query()->count());
    }

    public function test_more_than_a_page_of_games_is_read_a_page_at_a_time(): void
    {
        $games = array_map(fn (int $number): array => $this->game('g-' . $number, 1 + $number % 27), range(1, 101));

        $this->fakeGames($games, fn (string $id): array => $this->finishedSheets());

        $this->runJob();

        $offsets = array_map(function (Request $request): string {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['offset'];
        }, $this->sent('GET', '/items?'));

        self::assertSame(['0', '100'], $offsets);
        self::assertSame(202, GameStat::query()->count());
        self::assertSame(101, $this->backfill()->games_seen);
        self::assertSame(101, $this->backfill()->games_total);
        self::assertSame(101, $this->backfill()->games_counted);
    }

    public function test_exactly_a_page_of_games_reads_the_next_page_and_finds_it_empty(): void
    {
        $games = array_map(fn (int $number): array => $this->game('g-' . $number), range(1, 100));

        $this->fakeGames($games, fn (string $id): array => $this->finishedSheets());

        $this->runJob();

        self::assertCount(2, $this->sent('GET', '/items?'));
        self::assertSame(100, $this->backfill()->games_seen);
        self::assertSame(StatsBackfill::COMPLETE, $this->backfill()->state);
    }

    public function test_the_reasons_games_are_skipped_are_counted(): void
    {
        $short = $this->finishedScoreSheet();
        unset($short['lower-section']['chance']);
        $wrong = $this->finishedScoreSheet();
        $wrong['score']['total']++;

        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2), $this->game('g-3', 3), $this->game('g-4', 4), $this->game('g-5', 5), $this->game('g-6', 6)],
            [
                'g-1' => $this->finishedSheets(),
                'g-2' => $this->scoreSheets(['p-1' => $short, 'p-2' => $this->finishedScoreSheet()]),
                'g-3' => $this->scoreSheets(['p-1' => $short, 'p-2' => $this->finishedScoreSheet()]),
                'g-4' => $this->scoreSheets(['p-1' => $wrong, 'p-2' => $this->finishedScoreSheet()]),
                'g-5' => $this->scoreSheets(['p-1' => ['upper-section' => ['sevens' => 7]], 'p-2' => $this->finishedScoreSheet()]),
                // g-6 has been deleted since the list was read
            ]
        );

        $this->runJob();

        $backfill = $this->backfill();
        self::assertSame(6, $backfill->games_seen);
        self::assertSame(1, $backfill->games_counted);
        self::assertSame(5, $backfill->games_skipped);
        self::assertSame(['unfinished' => 2, 'mismatch' => 1, 'unreadable' => 2], $backfill->skipped);
        self::assertSame(2, GameStat::query()->count());
    }

    public function test_a_game_without_its_players_is_skipped_as_unreadable(): void
    {
        $game = $this->game('g-1');
        unset($game['players']);

        $this->fakeGames([$game], ['g-1' => $this->finishedSheets()]);

        $this->runJob();

        self::assertSame(['unreadable' => 1], $this->backfill()->skipped);
        self::assertSame(0, GameStat::query()->count());
    }

    public function test_the_progress_is_written_as_it_goes(): void
    {
        $seen = [];

        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2), $this->game('g-3', 3)],
            function (string $id) use (&$seen): array {
                $seen[$id] = [$this->backfill()->state, $this->backfill()->games_seen];

                return $this->finishedSheets();
            }
        );

        $this->runJob();

        // Running all the time, the games before it have been counted when it asks for the next one
        self::assertSame(['g-1' => ['running', 0], 'g-2' => ['running', 1], 'g-3' => ['running', 2]], $seen);
    }

    public function test_a_game_that_is_already_in_the_stats_is_counted_and_not_read_again(): void
    {
        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2)],
            ['g-1' => $this->finishedSheets(), 'g-2' => $this->finishedSheets()]
        );

        // The player completed g-1 since the stats started, it was recorded when it was completed
        GameStat::create([
            'user_id' => self::USER, 'game_id' => 'g-1', 'player_id' => 'p-1', 'player_name' => 'Ada', 'players_in_game' => 2,
            'score' => 285, 'upper' => 63, 'upper_bonus' => 35, 'lower' => 187, 'yahtzees' => 1, 'sheet' => [],
            'game_created_at' => '2026-09-01 18:00:00',
        ]);

        $this->runJob();

        self::assertSame(0, $this->dataRequests('g-1'));
        self::assertSame(1, $this->dataRequests('g-2'));
        self::assertSame(2, $this->backfill()->games_counted);
        self::assertSame(1, GameStat::query()->where('game_id', 'g-1')->count(), 'It is not recorded a second time');
    }

    public function test_the_stats_of_other_players_are_not_mistaken_for_theirs(): void
    {
        $this->fakeGames([$this->game('g-1')], ['g-1' => $this->finishedSheets()]);

        GameStat::create([
            'user_id' => 'someone-else', 'game_id' => 'g-1', 'player_id' => 'p-1', 'player_name' => 'Ada', 'players_in_game' => 2,
            'score' => 1, 'upper' => 0, 'upper_bonus' => 0, 'lower' => 1, 'yahtzees' => 0, 'sheet' => [],
            'game_created_at' => '2026-09-01 18:00:00',
        ]);

        $this->runJob();

        self::assertSame(1, $this->dataRequests('g-1'));
        self::assertSame(2, GameStat::query()->where('user_id', self::USER)->count());
    }

    // Whether it should run at all

    public function test_a_job_that_finds_the_row_running_does_nothing(): void
    {
        StatsBackfill::query()->update(['state' => StatsBackfill::RUNNING]);
        $this->fakeGames([$this->game('g-1')], ['g-1' => $this->finishedSheets()]);

        $this->runJob();

        Http::assertNothingSent();
        self::assertSame(StatsBackfill::RUNNING, $this->backfill()->state);
    }

    public function test_a_job_that_finds_the_row_finished_or_failed_or_paused_does_nothing(): void
    {
        $this->fakeGames([$this->game('g-1')], ['g-1' => $this->finishedSheets()]);

        foreach ([StatsBackfill::COMPLETE, StatsBackfill::FAILED, StatsBackfill::PAUSED] as $state) {
            StatsBackfill::query()->update(['state' => $state]);

            $this->runJob();

            self::assertSame($state, $this->backfill()->state);
        }

        Http::assertNothingSent();
        self::assertSame(0, GameStat::query()->count());
    }

    public function test_a_job_for_a_player_with_no_row_does_nothing(): void
    {
        $this->fakeGames([$this->game('g-1')], ['g-1' => $this->finishedSheets()]);

        $this->runJob('someone-with-no-row');

        Http::assertNothingSent();
        self::assertSame(0, StatsBackfill::query()->where('user_id', 'someone-with-no-row')->count());
    }

    public function test_it_stops_when_the_account_is_deleted_and_takes_the_game_it_was_recording_with_it(): void
    {
        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2)],
            function (string $id): array {
                // The player deletes their account while the first game is being read
                StatsBackfill::query()->delete();

                return $this->finishedSheets();
            }
        );

        $this->runJob();

        self::assertSame(0, $this->dataRequests('g-2'), 'It did not carry on');
        self::assertSame(0, GameStat::query()->count(), 'Nothing of the deleted account is left behind');
        self::assertSame(0, StatsBackfill::query()->count());
    }

    public function test_it_stops_when_somebody_changes_the_row_and_leaves_what_it_recorded(): void
    {
        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2)],
            function (string $id): array {
                StatsBackfill::query()->update(['state' => StatsBackfill::FAILED]);

                return $this->finishedSheets();
            }
        );

        $this->runJob();

        self::assertSame(0, $this->dataRequests('g-2'));
        self::assertSame(2, GameStat::query()->count(), 'The player is still there, so is what was recorded for them');
        self::assertSame(StatsBackfill::FAILED, $this->backfill()->state);
    }

    // A token that has been revoked

    public function test_a_token_that_is_revoked_before_the_games_are_read_pauses_the_row(): void
    {
        Http::fake([$this->items('?*') => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->runJob();

        self::assertSame(StatsBackfill::PAUSED, $this->backfill()->state);
        self::assertSame(0, GameStat::query()->count());
        self::assertCount(1, Http::recorded(), 'It does not keep asking');
    }

    public function test_a_token_that_is_revoked_part_way_pauses_the_row_and_the_next_run_carries_on(): void
    {
        $games = [$this->game('g-1', 1), $this->game('g-2', 2), $this->game('g-3', 3)];
        $sheets = ['g-1' => $this->finishedSheets(), 'g-2' => $this->finishedSheets(), 'g-3' => $this->finishedSheets()];

        // The player signs out while the second game is being read
        $signed_out = true;
        $this->fakeGames($games, $sheets, [
            $this->items('/g-2/data') => function () use (&$signed_out) {
                return $signed_out
                    ? Http::response(['message' => 'Unauthenticated.'], 401)
                    : Http::response($this->finishedSheets(), 200);
            },
        ]);

        $this->runJob();

        self::assertSame(StatsBackfill::PAUSED, $this->backfill()->state);
        self::assertSame(2, GameStat::query()->count(), 'The first game is recorded');
        self::assertSame(1, $this->backfill()->games_seen);

        // They sign in, the row is queued again with their new token
        $signed_out = false;
        StatsBackfill::query()->update(['state' => StatsBackfill::QUEUED]);

        $this->runJob();

        $backfill = $this->backfill();
        self::assertSame(StatsBackfill::COMPLETE, $backfill->state);
        self::assertSame(3, $backfill->games_seen);
        self::assertSame(3, $backfill->games_counted, 'The counts are the counts of the one run that finished');
        self::assertSame(6, GameStat::query()->count());
        self::assertSame(1, $this->dataRequests('g-1'), 'The game it had recorded is not read again, whatever the run');
        self::assertSame(2, $this->dataRequests('g-2'), 'Asked for in both runs');
    }

    // Problems

    public function test_a_server_error_is_an_exception_and_the_row_is_queued_so_the_queue_can_try_again(): void
    {
        $this->fakeGames(
            [$this->game('g-1', 1), $this->game('g-2', 2)],
            ['g-1' => $this->finishedSheets(), 'g-2' => $this->finishedSheets()],
            [$this->items('/g-2/data') => Http::response(['message' => 'Down'], 503)]
        );

        try {
            $this->runJob();
            self::fail('The job should have thrown');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('503', $e->getMessage());
        }

        self::assertSame(StatsBackfill::QUEUED, $this->backfill()->state);
        self::assertSame(2, GameStat::query()->count(), 'What was recorded stays recorded');
    }

    public function test_the_next_attempt_after_a_server_error_carries_on_and_finishes(): void
    {
        $games = [$this->game('g-1', 1), $this->game('g-2', 2)];
        $sheets = ['g-1' => $this->finishedSheets(), 'g-2' => $this->finishedSheets()];

        $down = true;
        $this->fakeGames($games, $sheets, [
            $this->items('/g-2/data') => function () use (&$down) {
                return $down
                    ? Http::response(['message' => 'Down'], 503)
                    : Http::response($this->finishedSheets(), 200);
            },
        ]);

        try {
            $this->runJob();
        } catch (RuntimeException) {
        }

        $down = false;

        $this->runJob();

        self::assertSame(StatsBackfill::COMPLETE, $this->backfill()->state);
        self::assertSame(2, $this->backfill()->games_counted);
        self::assertSame(4, GameStat::query()->count());
    }

    public function test_a_job_that_runs_out_of_tries_fails_the_row_and_emails_the_error(): void
    {
        Notification::fake();

        $this->job()->failed(new RuntimeException('The API answered 503 while collecting the stats'));

        $backfill = $this->backfill();
        self::assertSame(StatsBackfill::FAILED, $backfill->state);
        self::assertSame('The API answered 503 while collecting the stats', $backfill->last_error);

        Notification::assertSentOnDemand(
            ApiError::class,
            fn (ApiError $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'errors@yahtzee.test'
                && $notification->toArray($notifiable)['error'] === 'Unable to collect the stats of the finished games of user ' . self::USER
                && $notification->toArray($notifiable)['message'] === 'The API answered 503 while collecting the stats'
        );
    }

    public function test_a_row_that_is_complete_is_not_failed_by_a_late_failure(): void
    {
        Notification::fake();
        StatsBackfill::query()->update(['state' => StatsBackfill::COMPLETE]);

        $this->job()->failed(new RuntimeException('Late'));

        self::assertSame(StatsBackfill::COMPLETE, $this->backfill()->state);
    }

    public function test_a_rate_limit_is_waited_out_and_the_request_made_again(): void
    {
        $this->fakeGames(
            [$this->game('g-1')],
            ['g-1' => $this->finishedSheets()],
            [$this->items('/g-1/data') => Http::sequence()->push(['message' => 'Too Many Requests'], 429)->push($this->finishedSheets(), 200)]
        );

        $this->runJob();

        self::assertSame(2, $this->dataRequests('g-1'));
        self::assertSame(StatsBackfill::COMPLETE, $this->backfill()->state);
        self::assertSame(2, GameStat::query()->count());
    }

    public function test_a_rate_limit_that_does_not_go_away_is_an_exception_for_the_queue_to_try_again_later(): void
    {
        $this->fakeGames(
            [$this->game('g-1')],
            ['g-1' => $this->finishedSheets()],
            [$this->items('/g-1/data') => Http::response(['message' => 'Too Many Requests'], 429)]
        );

        try {
            $this->runJob();
            self::fail('The job should have thrown');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('429', $e->getMessage());
        }

        self::assertSame(3, $this->dataRequests('g-1'), 'It asked three times');
        self::assertSame(StatsBackfill::QUEUED, $this->backfill()->state);
    }

    public function test_the_list_of_games_being_unavailable_is_an_exception(): void
    {
        Http::fake([$this->items('?*') => Http::response(['message' => 'Down'], 500)]);

        $this->expectException(RuntimeException::class);

        try {
            $this->runJob();
        } finally {
            self::assertSame(StatsBackfill::QUEUED, $this->backfill()->state);
        }
    }
}
