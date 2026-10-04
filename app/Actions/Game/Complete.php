<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;
use App\Models\ShareToken;
use App\Notifications\ApiError;
use App\Support\GameStatBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class Complete extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $user_id
    ): int
    {
        $game_response = $api->getGame(
            $resource_type_id,
            $resource_id,
            $game_id,
            ['include-players' => true]
        );
        if ($game_response['status'] !== 200) {
            abort(404, 'Unable to find the game');
        }

        $assigned_players_response = $api->getAssignedGamePlayers($resource_type_id, $resource_id, $game_id);
        if ($assigned_players_response['status'] !== 200) {
            abort(404, 'Unable to find the game players');
        }

        $scores = [];
        foreach ($assigned_players_response['content'] as $player) {
            $scores[$player['category']['id']] = [
                'player_id' => $player['category']['id'],
                'player_name' => $player['category']['name'],
                'score' => 0
            ];
        }

        $game_score_sheets_response = $api->getGameScoreSheets(
            $resource_type_id,
            $resource_id,
            $game_id
        );

        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        foreach ($game_score_sheets_response['content'] as $score_sheet) {
            $scores[$score_sheet['key']]['score'] = $score_sheet['value']['score']['total'];
        }

        usort($scores, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $winner = $scores[array_key_first($scores)];

        ShareToken::query()->where('game_id', $game_id)->delete();

        $update_game_response = $api->updateGame(
            $resource_type_id,
            $resource_id,
            $game_id,
            [
                'game' => json_encode(['scores' => $scores,'winner' => $winner], JSON_THROW_ON_ERROR),
                'winner_id' => $winner['player_id'],
                'score' => $winner['score'],
                'complete' => 1
            ]
        );

        if ($update_game_response['status'] === 204) {
            $this->recordStats(
                $user_id,
                $game_response['content'],
                $assigned_players_response['content'],
                $game_score_sheets_response['content']
            );

            return 204;
        }

        return $update_game_response['status'];
    }

    /**
     * Records the stats of the game from the players and score sheets that have just been fetched. The game is
     * complete by now and the stats are never allowed to change that, a failure is reported and the game carries on.
     *
     * A game that was not played to the end is not counted, that is normal and nobody is told. A game that
     * should have been counted and was not is reported.
     *
     * @param array<string, mixed> $game
     * @param array<int, array<string, mixed>> $assigned_players
     * @param array<int, mixed> $score_sheets
     */
    private function recordStats(string $user_id, array $game, array $assigned_players, array $score_sheets): void
    {
        try {
            $players = array_map(
                static fn (array $player): array => [
                    'id' => $player['category']['id'],
                    'name' => $player['category']['name'],
                ],
                $assigned_players
            );

            $result = (new RecordStats())($user_id, $game, $players, $score_sheets, now());

            if ($result->reason === GameStatBuilder::UNREADABLE || $result->reason === GameStatBuilder::MISMATCH) {
                $this->reportStatsProblem(
                    'The stats for game ' . ($game['id'] ?? 'with no id') . ' were not recorded',
                    'The game was completed but its score sheets are ' . $result->reason . ', see GameStatBuilder'
                );
            }
        } catch (Throwable $e) {
            $this->reportStatsProblem(
                'Unable to record the stats for game ' . ($game['id'] ?? 'with no id'),
                $e->getMessage()
            );
        }
    }

    private function reportStatsProblem(string $error, string $message): void
    {
        Notification::route('mail', Config::get('app.config')['error_email'])
            ->notify(new ApiError($error, $message));
    }
}
