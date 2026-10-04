<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\StatsBackfill;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The row that makes sure the job collecting a user's stats is only ever started once.
 */
class StatsBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_row_is_queued_with_nothing_counted(): void
    {
        $backfill = StatsBackfill::create(['user_id' => 'u-1']);

        self::assertSame(StatsBackfill::QUEUED, $backfill->state);

        $backfill = StatsBackfill::query()->findOrFail($backfill->id);

        self::assertSame(StatsBackfill::QUEUED, $backfill->state);
        self::assertNull($backfill->games_total);
        self::assertSame(0, $backfill->games_seen);
        self::assertSame(0, $backfill->games_counted);
        self::assertSame(0, $backfill->games_skipped);
        self::assertNull($backfill->skipped);
        self::assertNull($backfill->last_error);
        self::assertNull($backfill->started_at);
        self::assertNull($backfill->finished_at);
    }

    public function test_a_user_can_only_have_one_row(): void
    {
        StatsBackfill::create(['user_id' => 'u-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        StatsBackfill::create(['user_id' => 'u-1']);
    }

    public function test_only_the_first_claim_of_a_user_inserts_a_row(): void
    {
        // How the job is claimed: whoever inserts the row dispatches the job, so the second insert has to do nothing
        self::assertSame(1, DB::table('stats_backfill')->insertOrIgnore(['user_id' => 'u-1']));
        self::assertSame(0, DB::table('stats_backfill')->insertOrIgnore(['user_id' => 'u-1']));
        self::assertSame(1, DB::table('stats_backfill')->insertOrIgnore(['user_id' => 'u-2']));

        self::assertSame(2, StatsBackfill::query()->count());
    }

    public function test_user_ids_that_only_differ_in_case_are_different_users(): void
    {
        // See GameStatTest, only a real check on MySQL
        StatsBackfill::create(['user_id' => 'aBc']);
        StatsBackfill::create(['user_id' => 'abc']);

        self::assertSame(2, StatsBackfill::query()->count());
    }

    public function test_the_progress_and_the_reasons_for_skipping_come_back_as_they_went_in(): void
    {
        $id = StatsBackfill::create([
            'user_id' => 'u-1',
            'state' => StatsBackfill::COMPLETE,
            'games_total' => 160,
            'games_seen' => 160,
            'games_counted' => 142,
            'games_skipped' => 18,
            'skipped' => ['unfinished' => 12, 'mismatch' => 6],
            'last_error' => null,
            'started_at' => '2026-10-04 09:00:00',
            'finished_at' => '2026-10-04 09:03:20',
        ])->id;

        $backfill = StatsBackfill::query()->findOrFail($id);

        self::assertSame(StatsBackfill::COMPLETE, $backfill->state);
        self::assertSame(160, $backfill->games_total);
        self::assertSame(142, $backfill->games_counted);
        self::assertSame(['unfinished' => 12, 'mismatch' => 6], $backfill->skipped);
        self::assertSame('2026-10-04 09:00:00', $backfill->started_at?->toDateTimeString());
        self::assertSame('2026-10-04 09:03:20', $backfill->finished_at?->toDateTimeString());
    }

    // The changes of state

    private function state(string $user_id = 'u-1'): string
    {
        return (string) StatsBackfill::query()->where('user_id', $user_id)->value('state');
    }

    public function test_only_a_queued_row_can_be_started_and_only_once(): void
    {
        StatsBackfill::claim('u-1');

        self::assertTrue(StatsBackfill::begin('u-1'));
        self::assertSame(StatsBackfill::RUNNING, $this->state());
        self::assertFalse(StatsBackfill::begin('u-1'), 'A second job finds it running');

        foreach ([StatsBackfill::COMPLETE, StatsBackfill::FAILED, StatsBackfill::PAUSED] as $state) {
            StatsBackfill::query()->update(['state' => $state]);

            self::assertFalse(StatsBackfill::begin('u-1'), $state);
            self::assertSame($state, $this->state());
        }

        self::assertFalse(StatsBackfill::begin('nobody'));
    }

    public function test_the_time_a_row_was_first_started_survives_the_job_being_started_again(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-04 09:00:00', 'UTC'));
        StatsBackfill::claim('u-1');
        StatsBackfill::begin('u-1');
        StatsBackfill::pause('u-1');

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 09:00:00', 'UTC'));
        StatsBackfill::requeue('u-1');
        StatsBackfill::begin('u-1');

        $backfill = StatsBackfill::query()->where('user_id', 'u-1')->firstOrFail();
        self::assertSame('2026-10-04 09:00:00', $backfill->started_at?->toDateTimeString());
        self::assertNull($backfill->finished_at);
    }

    public function test_a_job_that_is_started_again_forgets_the_last_error_and_finish_time(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::QUEUED, 'last_error' => 'Down', 'finished_at' => '2026-10-04 09:00:00']);

        StatsBackfill::begin('u-1');

        $backfill = StatsBackfill::query()->where('user_id', 'u-1')->firstOrFail();
        self::assertNull($backfill->last_error);
        self::assertNull($backfill->finished_at);
    }

    public function test_progress_is_only_written_to_a_running_row_and_says_whether_to_carry_on(): void
    {
        StatsBackfill::claim('u-1');

        self::assertFalse(StatsBackfill::progress('u-1', 10, 1, 1, []), 'Not started');

        StatsBackfill::begin('u-1');

        self::assertTrue(StatsBackfill::progress('u-1', 10, 4, 3, ['unfinished' => 1]));
        $backfill = StatsBackfill::query()->where('user_id', 'u-1')->firstOrFail();
        self::assertSame([10, 4, 3, 1], [$backfill->games_total, $backfill->games_seen, $backfill->games_counted, $backfill->games_skipped]);
        self::assertSame(['unfinished' => 1], $backfill->skipped);

        self::assertTrue(StatsBackfill::progress('u-1', 10, 4, 3, ['unfinished' => 1]), 'Nothing changed is not a reason to stop');

        StatsBackfill::query()->update(['state' => StatsBackfill::FAILED]);
        self::assertFalse(StatsBackfill::progress('u-1', 10, 5, 4, ['unfinished' => 1]), 'Changed by somebody');
        self::assertSame(4, StatsBackfill::query()->where('user_id', 'u-1')->value('games_seen'), 'And it did not write');

        StatsBackfill::query()->delete();
        self::assertFalse(StatsBackfill::progress('u-1', 10, 6, 5, []), 'Deleted');
    }

    public function test_progress_adds_up_the_games_skipped_whatever_the_reasons(): void
    {
        StatsBackfill::claim('u-1');
        StatsBackfill::begin('u-1');

        StatsBackfill::progress('u-1', 9, 9, 3, ['unfinished' => 2, 'mismatch' => 3, 'unreadable' => 1]);

        self::assertSame(6, StatsBackfill::query()->where('user_id', 'u-1')->value('games_skipped'));
    }

    public function test_a_job_stopping_queues_it_pauses_it_or_finishes_it_but_only_from_running(): void
    {
        foreach (['release' => StatsBackfill::QUEUED, 'pause' => StatsBackfill::PAUSED, 'finish' => StatsBackfill::COMPLETE] as $change => $becomes) {
            StatsBackfill::query()->delete();
            StatsBackfill::claim('u-1');

            StatsBackfill::$change('u-1');
            self::assertSame(StatsBackfill::QUEUED, $this->state(), $change . ' does nothing to a queued row');

            StatsBackfill::begin('u-1');
            StatsBackfill::$change('u-1');
            self::assertSame($becomes, $this->state(), $change);
        }
    }

    public function test_finishing_says_when(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-04 09:03:20', 'UTC'));
        StatsBackfill::claim('u-1');
        StatsBackfill::begin('u-1');

        StatsBackfill::finish('u-1');

        self::assertSame('2026-10-04 09:03:20', StatsBackfill::query()->where('user_id', 'u-1')->firstOrFail()->finished_at?->toDateTimeString());
    }

    public function test_failing_keeps_the_error_and_never_undoes_a_complete_row(): void
    {
        StatsBackfill::claim('u-1');
        StatsBackfill::begin('u-1');

        StatsBackfill::fail('u-1', 'The API answered 503');

        $backfill = StatsBackfill::query()->where('user_id', 'u-1')->firstOrFail();
        self::assertSame(StatsBackfill::FAILED, $backfill->state);
        self::assertSame('The API answered 503', $backfill->last_error);

        StatsBackfill::query()->update(['state' => StatsBackfill::COMPLETE, 'last_error' => null]);
        StatsBackfill::fail('u-1', 'Late');

        self::assertSame(StatsBackfill::COMPLETE, $this->state());
    }

    public function test_only_a_paused_row_or_one_that_has_lost_its_job_can_be_queued_again(): void
    {
        $fresh = \Illuminate\Support\Carbon::now();
        $old = \Illuminate\Support\Carbon::now()->subMinutes(StatsBackfill::STUCK_AFTER_MINUTES + 5);

        $cases = [
            'paused' => [StatsBackfill::PAUSED, $fresh, true],
            'queued and fresh' => [StatsBackfill::QUEUED, $fresh, false],
            'queued and old' => [StatsBackfill::QUEUED, $old, true],
            'running and fresh' => [StatsBackfill::RUNNING, $fresh, false],
            'running and old' => [StatsBackfill::RUNNING, $old, true],
            'failed and old' => [StatsBackfill::FAILED, $old, false],
            'complete and old' => [StatsBackfill::COMPLETE, $old, false],
        ];

        foreach ($cases as $name => [$state, $updated, $queued]) {
            StatsBackfill::query()->delete();
            StatsBackfill::create(['user_id' => 'u-1', 'state' => $state]);
            StatsBackfill::query()->update(['updated_at' => $updated]);

            self::assertSame($queued, StatsBackfill::requeue('u-1'), $name);
            self::assertSame($queued ? StatsBackfill::QUEUED : $state, $this->state(), $name);
        }

        self::assertFalse(StatsBackfill::requeue('nobody'));
    }

    public function test_a_row_that_is_queued_again_shows_a_sign_of_life_so_it_is_not_queued_twice(): void
    {
        StatsBackfill::create(['user_id' => 'u-1', 'state' => StatsBackfill::QUEUED]);
        StatsBackfill::query()->update(['updated_at' => \Illuminate\Support\Carbon::now()->subMinutes(StatsBackfill::STUCK_AFTER_MINUTES + 5)]);

        self::assertTrue(StatsBackfill::requeue('u-1'));
        self::assertFalse(StatsBackfill::requeue('u-1'), 'The second request finds it fresh');
    }
}
