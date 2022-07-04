<?php
declare(strict_types=1);

namespace App\Api;

class Service
{
    private ?string $bearer;

    private ?string $user_id;

    private Http $http;

    public function isActiveLocalSession(): bool
    {
        return !($this->bearer === null || $this->user_id === null);
    }

    public function getRequestsAsync(
        array $requests,
        int $concurrency = 4
    ): array
    {
        $return = [];

        $results = $this->http::getAsyncRequest(
            $requests,
            $concurrency
        );

        foreach ($results as $result) {
            $return[] = new Response($result);
        }

        return $return;
    }

    public function addAysncGetRequestToRequest(
        string $type,
        array $ids,
        string $name,
        string $uri
    ): array
    {
        return [
            'type' => $type,
            'ids' => $ids,
            'name' => $name,
            'uri' => $uri
        ];
    }

    public function getRequest(array $uri): ?Response
    {
        return $this->get(
            $uri['uri'],
            $uri['name']
        );
    }

    public function init(?string $user_id = null, string $bearer = null): Service
    {
        $this->bearer = $bearer;
        $this->user_id = $user_id;
        $this->http = Http::request($bearer);

        return $this;
    }

    public function isApiOnline(): bool
    {
        $uri = Uri::api();

        $response = $this->http::get($uri['uri']);

        $return = true;

        if (
            $response === null &&
            $this->http::getError()['status_code'] === 503
        ) {
            $return = false;
        }

        return $return;
    }

    public function authCheck(): ?Response
    {
        $uri = Uri::authCheck();

        return $this->get($uri['uri'], $uri['name']);
    }

    public function authUser(): ?Response
    {
        $uri = Uri::authUser();

        return $this->get($uri['uri'], $uri['name']);
    }

    public function authSignIn(string $email, string $password): ?array
    {
        $uri = Uri::signIn();

        $result = $this->http::post(
            $uri['uri'],
            [
                'email' => $email,
                'password' => $password,
                'device_name' => (app()->environment('local') ? 'app:local:' : 'app:')
            ]
        );

        if ($result !== null && $result['status'] === 201) {
            return $result;
        }

        return $result;
    }

    private function get(string $uri, string $name): ?Response
    {
        $response = $this->http::get($uri);

        if ($response !== null) {

            $response_data = [
                'type' => 'get',
                'name' => $name,
                'uri' => $uri,
                'expires' => $response['expires'],
                'response' => $response
            ];

            return new Response($response_data);
        }

        return null;
    }
}
