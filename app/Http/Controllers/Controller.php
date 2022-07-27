<?php

namespace App\Http\Controllers;

use App\Actions\Game\Score;
use App\Api\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected array $config;

    protected string $item_type_id;
    protected string $item_subtype_id;

    protected ?string $resource_type_id = null;
    protected ?string $resource_id = null;

    protected Service $api;

    public function __construct()
    {
        $this->config = Config::get('app.config');
        $this->item_type_id = $this->config['item_type_id'];
        $this->item_subtype_id = $this->config['item_subtype_id'];
    }

    protected function boostrap(Request $request)
    {
        $this->api = new Service($request->cookie($this->config['cookie_bearer']));

        $resource_types = $this->api->getResourceTypes(['item-type' => $this->item_type_id]);

        if ($resource_types['status'] === 200) {

            if (count($resource_types['content']) === 1) {
                $resource_type_id = $resource_types['content'][0]['id'];
                $resources = $this->api->getResources($resource_type_id, ['item-subtype' => $this->item_subtype_id]);
                if ($resources['status'] === 200) {
                    if (count($resources['content']) === 1) {

                        $this->resource_type_id = $resource_type_id;
                        $this->resource_id = $resources['content'][0]['id'];

                        return true;
                    }
                    $create_resource_response = $this->api->createResource($resource_type_id);
                    if ($create_resource_response['status'] === 201) {

                        $this->resource_type_id = $resource_type_id;
                        $this->resource_id = $create_resource_response['content']['id'];

                        return redirect()->route('home');
                    }
                    abort($create_resource_response['status'], $create_resource_response['content']);
                } else {
                    abort($resources['status'], $resources['content']);
                }
            } else {
                $create_resource_type_response = $this->api->createResourceType();
                if ($create_resource_type_response['status'] === 201) {
                    return redirect()->route('home');
                }
                abort($create_resource_type_response['status'], $create_resource_type_response['content']);
            }
        } else {
            abort($resource_types['status'], $resource_types['content']);
        }
    }

    protected function getGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $parameters = []
    )
    {
        $game_response = $this->api->getGame($resource_type_id, $resource_id, $game_id, $parameters);

        if ($game_response['status'] === 200) {
            return $game_response['content'];
        }

        abort($game_response['status'], $game_response['content']);
    }

    protected function getGamePlayers(string $resource_type_id, string $resource_id, string $game_id)
    {
        $game_players_response = $this->api->getGamePlayers($resource_type_id, $resource_id, $game_id);

        $players = [];
        if ($game_players_response['status'] === 200 && count($game_players_response['content']) > 0) {
            foreach ($game_players_response['content'] as $player) {
                $players[] = $player['category']['id'];
            }
        }

        return $players;
    }

    protected function getGames(string $resource_type_id, string $resource_id, array $parameters = []): array
    {
        $games_response = $this->api->getGames(
            $resource_type_id,
            $resource_id,
            $parameters
        );

        $games = [];
        if ($games_response['status'] === 200 && count($games_response['content']) > 0) {
            return $games_response['content'];
        }

        return $games;
    }

    protected function getPlayers(string $resource_type_id, array $parameters = []): array
    {
        $players_response = $this->api->getPlayers($resource_type_id, $parameters);

        $players = [];
        if ($players_response['status'] === 200 && count($players_response['content']) > 0) {
            foreach ($players_response['content'] as $player) {
                $players[] = [
                    'id' => $player['id'],
                    'name' => $player['name']
                ];
            }
        }

        return $players;
    }

    protected function score(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id,
        array $score_sheet
    )
    {
        $action = new Score();
        $result = $action(
            $api,
            $resource_type_id,
            $resource_id,
            $game_id,
            $player_id,
            $score_sheet
        );

        if ($result === 204) {
            return response()->json([
                'message' => 'Score updated',
                'score' => $score_sheet['score'],
                'turns' => $this->numberOfTurns($score_sheet)
            ]);
        }

        return response()->json(['message' => 'Failed to update your score sheet'], $result);
    }

    protected function numberOfTurns(array $score_sheet): int
    {
        $turns = 0;
        if (array_key_exists('upper-section', $score_sheet)) {
            $turns += count($score_sheet['upper-section']);
        }
        if (array_key_exists('lower-section', $score_sheet)) {
            foreach ($score_sheet['lower-section'] as $combo => $score) {
                if (
                    $combo !== 'yahtzee_bonus_one' &&
                    $combo !== 'yahtzee_bonus_two' &&
                    $combo !== 'yahtzee_bonus_three') {
                    $turns++;
                }
            }
        }

        return $turns;
    }

    protected function fetchPlayerScores(
        array $game_score_sheets,
        array $players
    ): array
    {
        $scores = [];
        foreach ($players as $player) {
            $scores[$player['category']['id']] = [
                'name' => $player['category']['name'],
                'upper' => 0,
                'lower' => 0,
                'total' => 0,
                'turns' => 0,
            ];
        }

        foreach ($game_score_sheets as $score_sheet) {
            $scores[$score_sheet['key']]['upper'] = $score_sheet['value']['score']['upper'] + $score_sheet['value']['score']['bonus'];
            $scores[$score_sheet['key']]['lower'] = $score_sheet['value']['score']['lower'];
            $scores[$score_sheet['key']]['total'] = $score_sheet['value']['score']['total'];
            $scores[$score_sheet['key']]['turns'] = $this->numberOfTurns($score_sheet['value']);
        }

        return $scores;
    }
}
