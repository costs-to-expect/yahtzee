<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Player extends Controller
{
    public function index(Request $request)
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

    public function newPlayer(Request $request)
    {
        $this->boostrap($request);

        return view(
            'new-player',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'errors' => null
            ]
        );
    }
}
