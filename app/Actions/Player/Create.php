<?php
declare(strict_types=1);

namespace App\Actions\Player;

use App\Api\Service;

class Create
{
    private string $message;

    private array $validation_errors = [];

    public function __invoke(Service $api, string $resource_type_id, array $input): int
    {
        $create_player_response = $api->createPlayer(
            $resource_type_id,
            $input['name'],
            $input['description']
        );

        if ($create_player_response['status'] === 201) {
            return $create_player_response['status'];
        }

        if ($create_player_response['status'] === 422) {
            $this->message = $create_player_response['content'];
            $this->validation_errors = $create_player_response['fields'];
            return $create_player_response['status'];
        }

        $this->message = $create_player_response['message'];

        return $create_player_response['status'];
    }

    public function getValidationErrors(): array
    {
        return $this->validation_errors;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
