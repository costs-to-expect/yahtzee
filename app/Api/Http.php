<?php
declare(strict_types=1);

namespace App\Api;

use Illuminate\Support\Facades\Config;
use JetBrains\PhpStorm\ArrayShape;

class Http
{
    private $client;

    public function __construct(?string $bearer = null)
    {
        if ($bearer === null) {
            $this->client = \Illuminate\Support\Facades\Http::withHeaders(
                $this->defaultHeaders()
            );
        } else {
            $this->client = \Illuminate\Support\Facades\Http::withToken($bearer)->withHeaders(
                $this->defaultHeaders()
            );
        }
    }

    public function get(string $uri): array
    {
        $response = $this->client->get($this->baseUri() . $uri);

        return match ($response->status()) {
            200 => [
                'status' => $response->status(),
                'content' => $response->json()
            ],
            404 => [
                'status' => 404,
                'content' => $response->json('message'),
            ],
            default => [
                'status' => $response->status(),
                'content' => 'We encountered an unknown error contacting the API [' . $response->json('message') . ']' ,
            ]
        };
    }

    public function patch(string $uri, array $payload): array
    {
        $response = $this->client->patch(
            $this->baseUri() . $uri,
            $payload
        );

        return match ($response->status()) {
            204 => [
                'status' => 204,
                'content' => null,
            ],
            400, 401, 403, 404, 500, 503,  => [
                'status' => $response->status(),
                'content' => $response->json('message'),
            ],
            422 => [
                'status' => $response->status(),
                'content' => $response->json('message'),
                'fields' => $response->json('fields'),
            ],
            default => [
                'status' => $response->status(),
                'content' => 'We encountered an unknown error contacting the API',
            ],
        };
    }

    public function post(string $uri, array $payload): array
    {
        $response = $this->client->post(
            $this->baseUri() . $uri,
            $payload
        );

        return match ($response->status()) {
            201 => [
                'status' => $response->status(),
                'content' => $response->json()
            ],
            204 => [
                'status' => 204,
                'content' => null,
            ],
            400, 401, 403, 404, 500, 503,  => [
                'status' => $response->status(),
                'content' => $response->json('message'),
            ],
            422 => [
                'status' => $response->status(),
                'content' => $response->json('message'),
                'fields' => $response->json('fields'),
            ],
            default => [
                'status' => $response->status(),
                'content' => 'We encountered an unknown error contacting the API',
            ],
        };
    }

    protected function baseUri(): string
    {
        $config = Config::get('app.config');

        if ($config['dev'] === false) {
            return $config['api_url'];
        }

        return $config['api_url_dev'];
    }

    #[ArrayShape(['Accept' => "string", 'Content-Type' => "string"])]
    private function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }
}
