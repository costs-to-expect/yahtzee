<?php
declare(strict_types=1);

namespace App\Api;

class Response
{
    private array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function body(): array
    {
        return $this->response['response']['body'];
    }

    public function headers(): array
    {
        return $this->response['response']['headers'];
    }

    public function hasResponse(): bool
    {
        return array_key_exists('response', $this->response) === true;
    }
}
