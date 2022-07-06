<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Home extends Controller
{
    public function index(Request $request)
    {
        $this->boostrap($request);

        $open_games = $this->openGames();
        $closed_games = $this->closedGames();

        return view(
            'home',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'open_games' => $open_games,
                'closed_games' => $closed_games
            ]
        );
    }

    private function closedGames(): array
    {
        $closed_games_response = $this->api->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'complete' => 1,
                'limit' => 5
            ]
        );

        $closed_games = [];
        if ($closed_games_response['status'] === 200 && count($closed_games_response['content']) > 0) {
            foreach ($closed_games_response['content'] as $game) {
                $closed_games[] = [
                    'id' => $game['id'],
                    'created' => $game['created']
                ];
            }
        }

        return $closed_games;
    }

    private function openGames(): array
    {
        $open_games_response = $this->api->getGames(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 0]
        );

        $open_games = [];
        if ($open_games_response['status'] === 200 && count($open_games_response['content']) > 0) {
            foreach ($open_games_response['content'] as $game) {
                $open_games[] = [
                    'id' => $game['id'],
                    'created' => $game['created'],
                    'updated' => $game['updated']
                ];
            }
        }

        return $open_games;
    }
}
