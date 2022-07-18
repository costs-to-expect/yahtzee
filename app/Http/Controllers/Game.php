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
                'games' => $this->getGames($this->resource_type_id, $this->resource_id, ['complete' => 1, 'include-players' => 1]),
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
            return redirect()->route('game.add-players.view', ['game_id' => $game_id])
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }

    public function scoreSheet(Request $request, string $game_id, string $player_id)
    {
        $this->boostrap($request);

        $game = $this->getGame(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            ['include-players' => 1]
        );

        $player_name = '';
        foreach ($game['players']['collection'] as $__player) {
            if ($__player['id'] === $player_id) {
                $player_name = $__player['name'];
                break;
            }
        }

        $player_score_sheet = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $game_id,
            $player_id
        );

        if ($player_score_sheet['status'] === 404) {
            $add_score_sheet_response = $this->api->addScoreSheetForPlayer(
                $this->resource_type_id,
                $this->resource_id,
                $game_id,
                $player_id
            );

            if ($add_score_sheet_response['status'] === 201) {
                return redirect()->route('game.score-sheet', ['game_id' => $game_id, 'player_id' => $player_id]);
            }

            abort($add_score_sheet_response['status'], $add_score_sheet_response['content']);
        }

        if ($player_score_sheet['status'] !== 200) {
            abort($player_score_sheet['status'], $player_score_sheet['content']);
        }

        try {
            $score_sheet = json_decode($player_score_sheet['content']['value'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            abort(500, 'Invalid Score Sheet JSON');
        }

        return view(
            'score-sheet',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
                'game_id' => $game_id,
                'player_id' => $player_id,

                'player_name' => $player_name,

                'score_sheet' => $score_sheet,
                'complete' => $game['complete']
            ]
        );
    }
}
