<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The one job, ever, that collects the stats of a user's finished games. A user has at most one row, `user_id` is
 * unique, and a row in the `complete` state is never claimed again.
 *
 * - queued: the row is claimed and the job dispatched
 * - running: the job is working through the games
 * - paused: the job lost its bearer token (the player signed out), it carries on from the same row the next time they
 *   are signed in
 * - failed: the job gave up, it can carry on from the same row
 * - complete: every finished game has been collected or skipped
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
}
