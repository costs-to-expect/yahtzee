<?php
declare(strict_types=1);

namespace App\Api;

class Service
{
    private ?string $bearer;

    private Http $http;

    public function init(string $bearer = null): Service
    {
        $this->bearer = $bearer;
        $this->http = Http::request($bearer);

        return $this;
    }

    public function authSignIn(string $email, string $password): ?array
    {
        $uri = Uri::signIn();

        return $this->http::post(
            $uri['uri'],
            [
                'email' => $email,
                'password' => $password,
                'device_name' => (app()->environment('local') ? 'app:local:' : 'app:')
            ]
        );
    }

    public function get(string $uri, string $name): ?Response
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
    }
}
