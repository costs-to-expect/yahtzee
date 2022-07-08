<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\Create;
use Illuminate\Http\Request;

class Game extends Controller
{
    public function index(Request $request)
    {
        $this->boostrap($request);

        return view(
            'games',
            [
                'games' => $this->getGames($this->resource_type_id, $this->resource_id),
            ]
        );
    }

    public function newGame(Request $request)
    {
        $this->boostrap($request);

        return view(
            'new-game',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'players' => $this->getPlayers($this->resource_type_id),

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function newGameProcess(Request $request)
    {
        $this->boostrap($request);

        $action = new Create();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->only(['name', 'description', 'players'])
        );

        if ($result === 201) {
            return redirect()->route('games');
        }

        if ($result === 422) {
            return redirect()->route('game.create.view')
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }
}
