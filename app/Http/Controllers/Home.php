<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Home extends Controller
{
    public function index(Request $request)
    {
        $this->boostrap($request);

        $open_games = $this->games(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 0]
        );
        $closed_games = $this->games(
            $this->resource_type_id,
            $this->resource_id,
            ['complete' => 1, 'limit' => 5]
        );

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
}
