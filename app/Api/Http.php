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

    public function post(string $uri, array $payload): array
    {
        $response = $this->client->post(
            $this->baseUri() . $uri,
            $payload
        );

        return match ($response->status()) {
            201, 400, 401, 422, 500, 503 => [
                'status' => $response->status(),
                'content' => $response->json()
            ],
            204 => [
                'status' => 204,
                'content' => null,
            ],
            default => [
                'status' => $response->status(),
                'content' => 'There was an error contacting the API',
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
