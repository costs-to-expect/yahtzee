<?php
declare(strict_types=1);

namespace App\Api;

use JetBrains\PhpStorm\ArrayShape;

class Service
{
    private Http $http;

    private string $item_type_id = '2AP1axw6L7';
    private string $item_subtype_id = '3JgkeMkB4q';

    public function __construct(string $bearer = null)
    {
        $this->http = new Http($bearer);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function addPlayerToGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::gamePlayers($resource_type_id, $resource_id, $game_id);

        return $this->http->post(
            $uri['uri'],
            [
                'category_id' => $player_id
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function addScoreSheetForPlayer(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::gamePlayers($resource_type_id, $resource_id, $game_id);

        $score_sheet = json_encode(
            [
                'upper-section' => [],
                'lower-section' => [],
                'score' => [
                    'upper' => 0,
                    'bonus' => 0,
                    'lower' => 0,
                    'total' => 0,
                ]
            ],
            JSON_THROW_ON_ERROR
        );

        return $this->http->post(
            $uri['uri'],
            [
                'key' => $player_id,
                'value' => json_encode($score_sheet, JSON_THROW_ON_ERROR)
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function authSignIn(string $email, string $password): array
    {
        $uri = Uri::authSignIn();

        return $this->http->post(
            $uri['uri'],
            [
                'email' => $email,
                'password' => $password,
                'device_name' => (app()->environment('local') ? 'yahtzee:local:' : 'yahtzee:')
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function authUser(): array
    {
        $uri = Uri::authUser();

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createGame(
        string $resource_type_id,
        string $resource_id,
        string $name,
        string $description,
    ): array
    {
        $uri = Uri::games($resource_type_id, $resource_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => $name,
                'description' => $description
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array", 'fields' => "array"])]
    public function createPlayer(string $resource_type_id, string $name, string $description): array
    {
        $uri = Uri::players($resource_type_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => $name,
                'description' => $description
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function createResource(string $resource_type_id): array
    {
        $uri = Uri::resources($resource_type_id);

        return $this->http->post(
            $uri['uri'],
            [
                'name' => 'Yahtzee game tracker',
                'description' => 'Yahtzee game tracker for Yahtzee app user',
                'item_subtype_id' => $this->item_subtype_id
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function createResourceType(): array
    {
        $uri = Uri::resourceTypes();

        return $this->http->post(
            $uri['uri'],
            [
                'name' => 'Game trackers',
                'description' => 'Game trackers for Yahtzee app user',
                'item_type_id' => $this->item_type_id
            ]
        );
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getGame(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::game($resource_type_id, $resource_id, $game_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getGamePlayers(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::gamePlayers($resource_type_id, $resource_id, $game_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getGames(
        string $resource_type_id,
        string $resource_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::games($resource_type_id, $resource_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getPlayers(
        string $resource_type_id,
        array $parameters = []
    ): array
    {
        $uri = Uri::players($resource_type_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getPlayerScoreSheet(
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        string $player_id
    ): array
    {
        $uri = Uri::playerScoreSheet($resource_type_id, $resource_id, $game_id, $player_id);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getResources(string $resource_type_id, array $parameters = []): array
    {
        $uri = Uri::resources($resource_type_id, $parameters);

        return $this->http->get($uri['uri']);
    }

    #[ArrayShape(['status' => "integer", 'content' => "array"])]
    public function getResourceTypes(array $parameters = []): array
    {
        $uri = Uri::resourceTypes($parameters);

        return $this->http->get($uri['uri']);
    }
}
