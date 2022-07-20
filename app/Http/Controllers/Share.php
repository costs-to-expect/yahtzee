<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\Score;
use App\Api\Service;
use App\Models\ShareToken;
use Illuminate\Http\Request;

class Share extends Controller
{
    public function scoreSheet(Request $request, string $token)
    {
        $parameters = ShareToken::query()->where('token', $token)->first();
        if ($parameters === null) {
            abort(404, 'The game page for the token does not exist');
        }

        try {
            $parameters = json_decode($parameters->parameters, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            abort(500, 'Failed to decode the parameters for the token');
        }

        $api = new Service($parameters['owner_bearer']);

        $game = $api->getGame(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id']
        );

        if ($game['status'] !== 200) {
            abort(404, 'Game not found');
        }

        $game = $game['content'];

        $player_score_sheet = $api->getPlayerScoreSheet(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id']
        );

        if ($player_score_sheet['status'] === 404) {
            $add_score_sheet_response = $api->addScoreSheetForPlayer(
                $parameters['resource_type_id'],
                $parameters['resource_id'],
                $parameters['game_id'],
                $parameters['player_id']
            );

            if ($add_score_sheet_response['status'] === 201) {
                return redirect()->route('public.score-sheet', ['token' => $token]);
            }

            abort($add_score_sheet_response['status'], $add_score_sheet_response['content']);
        }

        if ($player_score_sheet['status'] !== 200) {
            abort($player_score_sheet['status'], $player_score_sheet['content']);
        }

        return view(
            'public-score-sheet',
            [
                'resource_type_id' => $parameters['resource_type_id'],
                'resource_id' => $parameters['resource_id'],
                'game_id' => $parameters['game_id'],
                'player_id' => $parameters['player_id'],

                'score_sheet' => $player_score_sheet['content']['value'],
                'complete' => $game['complete']
            ]
        );
    }

    public function scoreUpper(Request $request)
    {
        $this->boostrap($request);

        // We need share actions and we need the owner bearer

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

        $action = new Score();
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

    public function scoreLower(Request $request)
    {
        $this->boostrap($request);

        // We need share actions and we need the owner bearer

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

        $score_sheet['lower-section'][$request->input('combo')] = $request->input('score');
        $score_lower = 0;
        foreach ($score_sheet['lower-section'] as $value) {
            $score_lower += $value;
        }

        $score_sheet['score']['lower'] = $score_lower;
        $score_sheet['score']['total'] = $score_sheet['score']['upper'] + $score_sheet['score']['bonus'] + $score_lower;

        $action = new Score();
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
