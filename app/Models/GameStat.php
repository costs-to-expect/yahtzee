<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * How one player did in one finished game, only recorded when every player in the game played all thirteen turns.
 *
 * @property int $id
 * @property string $user_id The Costs to Expect user the game belongs to
 * @property string $game_id
 * @property string $player_id
 * @property string $player_name
 * @property int $players_in_game
 * @property int $score The total
 * @property int $upper
 * @property int $upper_bonus 35 when the upper section earned it, otherwise 0
 * @property int $lower
 * @property int $yahtzees Yahtzees scored, the Yahtzee and its bonuses, see ScoreRules::yahtzees()
 * @property array<string, mixed> $sheet The score sheet as the API stores it
 * @property Carbon $game_created_at
 * @property Carbon|null $game_completed_at
 */
class GameStat extends Model
{
    protected $table = 'game_stat';

    protected $fillable = [
        'user_id',
        'game_id',
        'player_id',
        'player_name',
        'players_in_game',
        'score',
        'upper',
        'upper_bonus',
        'lower',
        'yahtzees',
        'sheet',
        'game_created_at',
        'game_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'players_in_game' => 'integer',
            'score' => 'integer',
            'upper' => 'integer',
            'upper_bonus' => 'integer',
            'lower' => 'integer',
            'yahtzees' => 'integer',
            'sheet' => 'array',
            'game_created_at' => 'datetime',
            'game_completed_at' => 'datetime',
        ];
    }
}
