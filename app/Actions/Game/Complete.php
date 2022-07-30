<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;
use App\Models\ShareToken;

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
        string $game_id
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

        $players_response = $api->getGamePlayers($resource_type_id, $resource_id, $game_id);
        if ($players_response['status'] !== 200) {
            abort(404, 'Unable to find the game players');
        }

        $game_score_sheets_response = $api->getGameScoreSheets(
            $resource_type_id,
            $resource_id,
            $game_id
        );

        $scores = [];
        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        ShareToken::query()->where('game_id', $game_id)->delete();

        foreach ($game_score_sheets_response['content'] as $score_sheet) {

            $player_name = 'Unknown';
            foreach ($game_response['content']['players']['collection'] as $player) {
                if ($player['id'] === $score_sheet['key']) {
                    $player_name = $player['name'];
                }
            }

            $scores[$score_sheet['value']['score']['total']] = [
                'player_id' => $score_sheet['key'],
                'player_name' => $player_name,
                'score' => $score_sheet['value']['score']['total']
            ];
        }

        krsort($scores);
        $winner = $scores[array_key_first($scores)];

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
            return 204;
        }

        return $update_game_response['status'];
    }
}
