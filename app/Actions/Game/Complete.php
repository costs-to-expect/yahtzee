<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;

class Complete extends Action
{
    public function __invoke(Service $api, string $resource_type_id, string $resource_id, string $game_id): bool
    {
        $game_response = $api->getGame($resource_type_id, $resource_id, $game_id);
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

        $max = 0;
        $winner = null;
        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        foreach ($game_score_sheets_response['content'] as $score_sheet) {
            if ($score_sheet['value']['score']['total'] > $max) {
                $max = $score_sheet['value']['score']['total'];
                $winner = $score_sheet['key'];
            }
        }

        if ($winner === null) {
            abort(500, 'Unable to determine the winner');
        }

        return true;
    }
}
