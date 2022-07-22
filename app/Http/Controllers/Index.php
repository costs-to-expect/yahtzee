<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShareToken;
use Illuminate\Http\Request;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class Index extends Controller
{
    public function home(Request $request)
    {
        $this->boostrap($request);

        $open_games = $this->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'complete' => 0,
                'include-players' => 1
            ]
        );

        $closed_games = $this->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'complete' => 1,
                'limit' => 5,
                'include-players' => 1
            ]
        );
        $players = $this->getPlayers($this->resource_type_id, ['limit' => 5]);

        $game_scores = [];
        foreach ($open_games as $game) {
            $game_score_sheets_response = $this->api->getGameScoreSheets(
                $this->resource_type_id,
                $this->resource_id,
                $game['id']
            );

            if ($game_score_sheets_response['status'] === 200) {
                foreach ($game_score_sheets_response['content'] as $score_sheet) {
                    $game_scores[$game['id']][$score_sheet['key']] = $score_sheet['value']['score']['total'];
                }
            }
        }

        return view(
            'home',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'open_games' => $open_games,
                'closed_games' => $closed_games,
                'players' => $players,

                'share_tokens' => (new ShareToken())->getShareTokens(),

                'game_scores' => $game_scores
            ]
        );
    }
}
