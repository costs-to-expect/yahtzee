<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\AddPlayersToGame;
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

    public function addPlayersToGame(Request $request, string $game_id)
    {
        $this->boostrap($request);

        $this->getGame($this->resource_type_id, $this->resource_id, $game_id);

        $players = [];
        $game_players = [];
        $all_players = $this->getPlayers($this->resource_type_id);
        $assigned_game_players = $this->getGamePlayers($this->resource_type_id, $this->resource_id, $game_id);

        foreach ($all_players as $player) {
            if (!in_array($player['id'], $assigned_game_players, true)) {
                $players[] = $player;
            } else {
                $game_players[] = $player['name'];
            }
        }

        return view(
            'add-players-to-game',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
                'game_id' => $game_id,

                'players' => $players,
                'game_players' => $game_players,

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function addPlayersToGameProcess(Request $request)
    {
        $this->boostrap($request);

        $game_id = $request->route('game_id');

        $action = new AddPlayersToGame();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            $request->only(['players'])
        );

        if ($result === 201) {
            return redirect()->route('games');
        }

        if ($result === 422) {
            return redirect()->route('add-players-to-game.create.view', ['game_id' => $game_id])
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }
}
