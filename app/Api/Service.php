<?php
declare(strict_types=1);

namespace App\Api;

class Service
{
    private Http $http;

    public function __construct(string $bearer = null)
    {
        $this->http = new Http($bearer);
    }

    public function authSignIn(string $email, string $password): ?array
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

    /*public function get(string $uri, string $name): ?Response
    {
        $response = $this->http::get($uri);

        if ($response !== null) {

            $response_data = [
                'type' => 'get',
                'name' => $name,
                'uri' => $uri,
                'response' => $response
            ];

            return new Response($response_data);
        }

        return null;
    }*/
}
