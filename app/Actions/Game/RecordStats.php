<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\GameStat;
use App\Support\GameStatBuilder;
use App\Support\GameStatResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Works the stats of a finished game out (GameStatBuilder) and makes the game_stat table say that about the game, and
 * nothing else: a game that can be counted has a row for each of its players, one that can't has none. Recording a game
 * again replaces what was there, so completing a game and the job that collects the stats of old games can both
 * record the same game, in either order, without counting it twice.
 *
 * It only touches the rows of the one game, and only the user's. It reads nothing from the API, the caller hands over
 * what it has fetched.
 */
class RecordStats
{
    /**
     * What a game recorded for a second time replaces, everything but the columns that say which row it is
     */
    private const REPLACED = [
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

    /**
     * @param array<string, mixed> $game As the API returns a game
     * @param array<int, mixed> $players The players in the game, each one `['id' => ..., 'name' => ...]`
     * @param array<int, mixed> $sheets The game's score sheets as the API returns them
     * @param CarbonInterface|null $completed_at When the game was completed, for a game that has only just been completed
     */
    public function __invoke(
        string $user_id,
        array $game,
        array $players,
        array $sheets,
        ?CarbonInterface $completed_at = null
    ): GameStatResult
    {
        $result = GameStatBuilder::build($user_id, $game, $players, $sheets, $completed_at);

        $game_id = $game['id'] ?? null;
        if (is_string($game_id) === false || $game_id === '') {
            // Nothing says which game this is, so there is nothing to replace
            return $result;
        }

        DB::transaction(static function () use ($result, $user_id, $game_id): void {
            $stored = GameStat::query()->where('user_id', $user_id)->where('game_id', $game_id);

            if ($result->isCounted() === false) {
                $stored->delete();

                return;
            }

            // The sheet is stored as JSON, upsert() goes round the model so its cast does not do that
            $rows = array_map(
                static fn (array $row): array => [...$row, 'sheet' => json_encode($row['sheet'], JSON_THROW_ON_ERROR)],
                $result->rows
            );

            $stored->whereNotIn('player_id', array_column($rows, 'player_id'))->delete();

            GameStat::upsert($rows, ['user_id', 'game_id', 'player_id'], self::REPLACED);
        });

        return $result;
    }
}
