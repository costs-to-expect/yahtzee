<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Stats\StartBackfill;
use App\Jobs\BackfillStats;
use App\Models\StatsBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\Feature\Concerns\FakesTheApi;
use Tests\TestCase;

/**
 * The job that collects a player's older games is started when it has to be, the first time they are seen and again if
 * it stopped, and never when it has run, whatever number of requests there are.
 */
class StartBackfillTest extends TestCase
{
    use FakesTheApi;
    use RefreshDatabase;

    private function start(string $user_id = 'u-1', ?string $bearer = 'bearer-1'): void
    {
        (new StartBackfill())($user_id, $bearer, 'rt-1', 'r-1');
    }

    private function row(string $user_id = 'u-1'): StatsBackfill
    {
        return StatsBackfill::query()->where('user_id', $user_id)->firstOrFail();
    }

    private function property(BackfillStats $job, string $name): mixed
    {
        return (new ReflectionProperty($job, $name))->getValue($job);
    }

    private function backdate(string $user_id, int $minutes): void
    {
        StatsBackfill::query()->where('user_id', $user_id)->update(['updated_at' => Carbon::now()->subMinutes($minutes)]);
    }

    public function test_the_first_time_a_player_is_seen_the_job_is_started_with_what_it_needs(): void
    {
        $this->start();

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
        Bus::assertDispatched(BackfillStats::class, function (BackfillStats $job): bool {
            return $this->property($job, 'bearer_token') === 'bearer-1'
                && $this->property($job, 'user_id') === 'u-1'
                && $this->property($job, 'resource_type_id') === 'rt-1'
                && $this->property($job, 'resource_id') === 'r-1';
        });
        self::assertSame(StatsBackfill::QUEUED, $this->row()->state);
    }

    public function test_the_same_player_seen_again_does_not_start_it_again(): void
    {
        $this->start();
        $this->start();
        $this->start();

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
    }

    public function test_every_player_has_their_own_job(): void
    {
        $this->start('u-1', 'bearer-1');
        $this->start('u-2', 'bearer-2');

        Bus::assertDispatchedTimes(BackfillStats::class, 2);
        self::assertSame(2, StatsBackfill::query()->count());
    }

    public function test_a_job_that_is_running_is_left_alone(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::RUNNING]);

        $this->start();

        Bus::assertNotDispatched(BackfillStats::class);
        self::assertSame(StatsBackfill::RUNNING, $this->row()->state);
    }

    public function test_a_job_that_has_finished_never_starts_again(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::COMPLETE]);
        $this->backdate('u-1', 60 * 24 * 30);

        $this->start();

        Bus::assertNotDispatched(BackfillStats::class);
        self::assertSame(StatsBackfill::COMPLETE, $this->row()->state);
    }

    public function test_the_common_case_is_one_read_and_no_writes(): void
    {
        // Every visit to the home page asks, almost always about a player whose games have been collected
        foreach ([StatsBackfill::COMPLETE, StatsBackfill::FAILED] as $state) {
            StatsBackfill::query()->delete();
            StatsBackfill::create(['user_id' => 'u-1', 'state' => $state]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->start();

            $queries = array_column(DB::getQueryLog(), 'query');
            DB::disableQueryLog();

            self::assertCount(1, $queries, $state);
            self::assertStringStartsWith('select', strtolower($queries[0]), $state);
        }
    }

    public function test_a_job_that_gave_up_does_not_start_again_whatever_the_age_of_the_row(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::FAILED]);
        $this->backdate('u-1', 60 * 24);

        $this->start();

        Bus::assertNotDispatched(BackfillStats::class);
        self::assertSame(StatsBackfill::FAILED, $this->row()->state);
    }

    public function test_a_job_that_stopped_because_the_player_signed_out_carries_on_with_their_new_token(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::PAUSED, 'games_seen' => 40]);

        $this->start('u-1', 'a-new-bearer');

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
        Bus::assertDispatched(BackfillStats::class, fn (BackfillStats $job): bool => $this->property($job, 'bearer_token') === 'a-new-bearer');
        self::assertSame(StatsBackfill::QUEUED, $this->row()->state);
        self::assertSame(1, StatsBackfill::query()->count(), 'The same row, not a second job');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statesThatLostTheirJob(): array
    {
        return ['queued' => [StatsBackfill::QUEUED], 'running' => [StatsBackfill::RUNNING]];
    }

    #[DataProvider('statesThatLostTheirJob')]
    public function test_a_job_that_has_lost_its_job_is_queued_again_when_nothing_has_happened_for_a_while(string $state): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => $state]);
        $this->backdate('u-1', StatsBackfill::STUCK_AFTER_MINUTES + 1);

        $this->start('u-1', 'a-new-bearer');

        self::assertSame(StatsBackfill::QUEUED, $this->row()->state);
        Bus::assertDispatchedTimes(BackfillStats::class, 1);
        Bus::assertDispatched(BackfillStats::class, fn (BackfillStats $job): bool => $this->property($job, 'bearer_token') === 'a-new-bearer');
        self::assertTrue($this->row()->updated_at->greaterThan(Carbon::now()->subMinute()), 'It shows a sign of life again, so a second request does not do it too');
    }

    public function test_a_queued_or_running_job_that_has_shown_a_sign_of_life_is_not_taken_for_lost(): void
    {
        foreach ([StatsBackfill::QUEUED, StatsBackfill::RUNNING] as $state) {
            StatsBackfill::query()->delete();
            StatsBackfill::create(['user_id' => 'u-1', 'state' => $state]);
            $this->backdate('u-1', StatsBackfill::STUCK_AFTER_MINUTES - 1);

            $this->start();

            self::assertSame($state, $this->row()->state);
        }

        Bus::assertNotDispatched(BackfillStats::class);
    }

    public function test_without_a_bearer_token_nothing_is_started(): void
    {
        $this->start('u-1', null);
        $this->start('u-1', '');

        Bus::assertNotDispatched(BackfillStats::class);
        self::assertSame(0, StatsBackfill::query()->count());
    }

    public function test_the_row_is_claimed_before_the_job_is_started_so_the_first_request_is_the_only_one(): void
    {
        // Another request claimed the row between this one looking and claiming
        StatsBackfill::claim('u-1');

        $this->start();

        Bus::assertNotDispatched(BackfillStats::class);
    }

    public function test_the_job_is_unique_for_the_user_and_carries_the_token_encrypted(): void
    {
        $job = new BackfillStats('bearer-1', 'u-1', 'rt-1', 'r-1');

        self::assertSame('u-1', $job->uniqueId());
        self::assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeUnique::class, $job);
        self::assertInstanceOf(\Illuminate\Contracts\Queue\ShouldBeEncrypted::class, $job);
    }

    public function test_the_unique_lock_runs_out_before_a_row_is_taken_for_lost(): void
    {
        // Otherwise the job queued for a row that lost its job could be turned away by the lock of the one that was lost
        self::assertLessThan(StatsBackfill::STUCK_AFTER_MINUTES * 60, (new BackfillStats('b', 'u', 'rt', 'r'))->uniqueFor);
    }

    public function test_the_longest_a_job_may_run_is_less_than_the_time_the_queue_waits_before_trying_it_again(): void
    {
        self::assertLessThan(
            (int) config('queue.connections.database.retry_after'),
            (new BackfillStats('b', 'u', 'rt', 'r'))->timeout + 1,
            'A second worker would pick up a job that is still running'
        );
    }

    // The pages that start it

    public function test_the_home_page_starts_it(): void
    {
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response([], 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response([], 200),
        ]);

        $this->signedIn()->get('/home')->assertOk();
        $this->signedIn()->get('/home')->assertOk();

        Bus::assertDispatchedTimes(BackfillStats::class, 1);
        Bus::assertDispatched(BackfillStats::class, function (BackfillStats $job): bool {
            return $this->property($job, 'user_id') === self::USER_ID
                && $this->property($job, 'bearer_token') === self::BEARER
                && $this->property($job, 'resource_type_id') === 'rt-1'
                && $this->property($job, 'resource_id') === 'r-1';
        });
    }

    public function test_a_problem_starting_it_never_stops_the_home_page(): void
    {
        $this->fakeApi([
            $this->items('?complete=0&include-players=1') => Http::response([], 200),
            $this->items('?complete=1&limit=5&include-players=1') => Http::response([], 200),
        ]);

        Schema::drop('stats_backfill');

        $this->signedIn()->get('/home')->assertOk();

        Bus::assertNotDispatched(BackfillStats::class);
    }
}
