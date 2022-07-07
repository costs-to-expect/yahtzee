<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Index extends Controller
{
    public function home(Request $request)
    {
        $this->boostrap($request);

        $open_games = $this->getGames(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 0]
        );
        $closed_games = $this->getGames(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 1, 'limit' => 5]
        );
        $players = $this->getPlayers($this->resource_type_id);

        return view(
            'home',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'open_games' => $open_games,
                'closed_games' => $closed_games,
                'players' => $players,
            ]
        );
    }

    public function players(Request $request)
    {
        $this->boostrap($request);

        $players = $this->getPlayers($this->resource_type_id);

        return view(
            'players',
            [
                'players' => $players,
            ]
        );
    }
}
