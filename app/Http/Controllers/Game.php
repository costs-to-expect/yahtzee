<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\AddPlayersToGame;
use App\Actions\Game\Create;
use App\Actions\Game\ScoreUpper;
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

        return view(
            'score-sheet',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
                'game_id' => $game_id,
                'player_id' => $player_id,

                'player_name' => $player_name,

                'score_sheet' => $player_score_sheet['content']['value'],
                'complete' => $game['complete']
            ]
        );
    }

    public function scoreUpper(Request $request)
    {
        $this->boostrap($request);

        $score_sheet = $this->api->getPlayerScoreSheet(
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id')
        );

        if ($score_sheet['status'] !== 200) {
            return response()->json(['message' => 'Unable to fetch your score sheet'], $score_sheet['status']);
        }

        $score_sheet = $score_sheet['content']['value'];

        $score_sheet['upper-section'][$request->input('dice')] = $request->input('score');
        $score_upper = 0;
        $score_bonus = 0;
        foreach ($score_sheet['upper-section'] as $value) {
            $score_upper += $value;
        }
        if ($score_upper >= 63) {
            $score_bonus = 35;
        }

        $score_sheet['score']['upper'] = $score_upper;
        $score_sheet['score']['bonus'] = $score_bonus;
        $score_sheet['score']['total'] = $score_sheet['score']['lower'] + $score_upper + $score_bonus;

        $action = new ScoreUpper();
        $result = $action(
            $this->api,
            $this->resource_type_id,
            $this->resource_id,
            $request->input('game_id'),
            $request->input('player_id'),
            $score_sheet
        );

        if ($result === 204) {
            return response()->json([
                'message' => 'Score updated',
                'score' => $score_sheet['score']
            ]);
        }

        return response()->json(['message' => 'Failed to update your score sheet'], $result);
    }
}
