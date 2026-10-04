<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The one job, ever, that collects the stats of a user's finished games. A user has at most one row, `user_id` is
 * unique, and a row in the `complete` state is never claimed again.
 *
 * - queued: the row is claimed and the job dispatched
 * - running: the job is working through the games
 * - paused: the job lost its bearer token (the player signed out), it carries on from the same row the next time they
 *   are signed in
 * - failed: the job gave up, it stays failed until a person looks at it (put the row back to paused to let it carry on)
 * - complete: every finished game has been collected or skipped
 *
 * Every change of state is one UPDATE that says which state it moves from, so of any number of requests and jobs at
 * the same moment only one makes it, the database decides, not the queue. A row that says queued or running but has
 * shown no sign of life for a while has lost its job (the queue was emptied, the worker was killed) and is queued again.
 *
 * @property int $id
 * @property string $user_id
 * @property string $state
 * @property int|null $games_total Known once the first page of games has been read
 * @property int $games_seen
 * @property int $games_counted
 * @property int $games_skipped
 * @property array<string, int>|null $skipped Games skipped, by the reason
 * @property string|null $last_error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class StatsBackfill extends Model
{
    public const QUEUED = 'queued';

    public const RUNNING = 'running';

    public const PAUSED = 'paused';

    public const FAILED = 'failed';

    public const COMPLETE = 'complete';

    /** Minutes without a sign of life before a queued or running row is taken to have lost its job */
    public const STUCK_AFTER_MINUTES = 30;

    protected $table = 'stats_backfill';

    protected $attributes = [
        'state' => self::QUEUED,
    ];

    protected $fillable = [
        'user_id',
        'state',
        'games_total',
        'games_seen',
        'games_counted',
        'games_skipped',
        'skipped',
        'last_error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'games_total' => 'integer',
            'games_seen' => 'integer',
            'games_counted' => 'integer',
            'games_skipped' => 'integer',
            'skipped' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Claims the job for a user who has never had one. true for the one request that inserts the row, which is the one
     * that starts the job.
     */
    public static function claim(string $user_id): bool
    {
        return DB::table('stats_backfill')->insertOrIgnore([
            'user_id' => $user_id,
            'state' => self::QUEUED,
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    /**
     * Queues the job again for a user whose job stopped because their token was revoked (paused), or whose row says
     * queued or running and has shown no sign of life for STUCK_AFTER_MINUTES. true for the one request that does it,
     * which is the one that starts the job. A failed row is not queued again, it needs a person.
     */
    public static function requeue(string $user_id): bool
    {
        $stuck = now()->subMinutes(self::STUCK_AFTER_MINUTES);

        return self::row($user_id)
            ->where(static function (Builder $query) use ($stuck): void {
                $query->where('state', self::PAUSED)
                    ->orWhere(static function (Builder $query) use ($stuck): void {
                        $query->whereIn('state', [self::QUEUED, self::RUNNING])->where('updated_at', '<', $stuck);
                    });
            })
            ->update(['state' => self::QUEUED, 'updated_at' => now()]) === 1;
    }

    /**
     * The job starts work. Only a queued row can be started, so a job that finds the row running (a second worker
     * that picked it up), complete, failed, paused or gone does nothing.
     */
    public static function begin(string $user_id): bool
    {
        $began = self::row($user_id)
            ->where('state', self::QUEUED)
            ->update(['state' => self::RUNNING, 'last_error' => null, 'finished_at' => null, 'updated_at' => now()]) === 1;

        if ($began) {
            self::row($user_id)->whereNull('started_at')->update(['started_at' => now()]);
        }

        return $began;
    }

    /**
     * What the job has done so far, this is also the sign of life. false when the job should stop, the row has gone
     * (the account was deleted) or is no longer running (somebody changed it).
     *
     * @param array<string, int> $skipped Games skipped, by the reason
     */
    public static function progress(string $user_id, ?int $total, int $seen, int $counted, array $skipped): bool
    {
        $updated = self::row($user_id)
            ->where('state', self::RUNNING)
            ->update([
                'games_total' => $total,
                'games_seen' => $seen,
                'games_counted' => $counted,
                'games_skipped' => array_sum($skipped),
                'skipped' => json_encode($skipped, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        // MySQL does not count a row it did not change, so nothing changed is not the same as nothing to change
        return $updated === 1 || self::row($user_id)->where('state', self::RUNNING)->exists();
    }

    /**
     * The job stopped for a reason that might pass, the queue tries it again and it has to be queued to start
     */
    public static function release(string $user_id): void
    {
        self::row($user_id)->where('state', self::RUNNING)->update(['state' => self::QUEUED, 'updated_at' => now()]);
    }

    /**
     * The API says the token has been revoked, the player signed out. The next time they are signed in it carries on.
     */
    public static function pause(string $user_id): void
    {
        self::row($user_id)->where('state', self::RUNNING)->update(['state' => self::PAUSED, 'updated_at' => now()]);
    }

    public static function finish(string $user_id): void
    {
        self::row($user_id)->where('state', self::RUNNING)->update(['state' => self::COMPLETE, 'finished_at' => now(), 'updated_at' => now()]);
    }

    /**
     * The job gave up. A complete row is never changed.
     */
    public static function fail(string $user_id, string $error): void
    {
        self::row($user_id)
            ->where('state', '!=', self::COMPLETE)
            ->update(['state' => self::FAILED, 'last_error' => $error, 'updated_at' => now()]);
    }

    private static function row(string $user_id): Builder
    {
        return DB::table('stats_backfill')->where('user_id', $user_id);
    }
}
