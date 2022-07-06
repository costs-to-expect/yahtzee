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
