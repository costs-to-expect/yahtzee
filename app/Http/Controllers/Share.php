<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Game\Score;
use App\Api\Service;
use App\Models\ShareToken;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class Share extends Controller
{
    public function playerScores(Request $request, string $token)
    {
        $parameters = $this->getParameters($token);

        $api = new Service($parameters['owner_bearer']);

        $players_response = $api->getGamePlayers(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id']
        );

        if ($players_response['status'] !== 200) {
            abort(404, 'Unable to find the game players');
        }

        $game_score_sheets_response = $api->getGameScoreSheets(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
        );

        if ($game_score_sheets_response['status'] !== 200) {
            abort(404, 'Unable to fetch the game scores');
        }

        $scores = $this->fetchPlayerScores(
            $game_score_sheets_response['content'],
            $players_response['content']
        );

        return view(
            'player-scores',
            ['scores' => $scores]
        );
    }

    public function scoreSheet(Request $request, string $token)
    {
        $parameters = $this->getParameters($token);

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
                'token' => $token,

                'player_name' => $parameters['player_name'],
                'score_sheet' => $player_score_sheet['content']['value'],
                'turns' => $this->numberOfTurns($player_score_sheet['content']['value']),
                'complete' => $game['complete']
            ]
        );
    }

    public function scoreUpper(Request $request, $token)
    {
        $parameters = $this->getParameters($token);

        $api = new Service($parameters['owner_bearer']);

        $score_sheet = $this->getScoreSheet($api, $parameters);

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

        return $this->score(
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id'],
            $score_sheet
        );
    }

    public function scoreLower(Request $request, $token)
    {
        $parameters = $this->getParameters($token);

        $api = new Service($parameters['owner_bearer']);

        $score_sheet = $this->getScoreSheet($api, $parameters);

        $score_sheet['lower-section'][$request->input('combo')] = $request->input('score');
        $score_lower = 0;
        foreach ($score_sheet['lower-section'] as $value) {
            $score_lower += $value;
        }

        $score_sheet['score']['lower'] = $score_lower;
        $score_sheet['score']['total'] = $score_sheet['score']['upper'] + $score_sheet['score']['bonus'] + $score_lower;

        return $this->score(
            $api,
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id'],
            $score_sheet
        );
    }

    #[ArrayShape([
        'resource_type_id' => "string",
        'resource_id' => "string",
        'game_id' => "string",
        'player_id' => "string",
        'player_name' => 'string',
        'owner_bearer' => "string"
    ])]
    private function getParameters($token): array
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

        if (array_key_exists('player_name', $parameters) === false) {
            $parameters['player_name'] = 'Yahtzee Player';
        }

        return $parameters;
    }

    private function getScoreSheet(Service $api, array $parameters)
    {
        $score_sheet = $api->getPlayerScoreSheet(
            $parameters['resource_type_id'],
            $parameters['resource_id'],
            $parameters['game_id'],
            $parameters['player_id']
        );

        if ($score_sheet['status'] !== 200) {
            return response()->json(['message' => 'Unable to fetch your score sheet'], $score_sheet['status']);
        }

        return $score_sheet['content']['value'];
    }
}
