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
}
