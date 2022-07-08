<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;

class Create extends Action
{
    public function __invoke(Service $api, string $resource_type_id, string $resource_id, array $input): int
    {
        if (array_key_exists('players', $input) === false) {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please select your players']];
            return 422;
        }

        $create_game_response = $api->createGame(
            $resource_type_id,
            $resource_id,
            $input['name'],
            $input['description']
        );

        if ($create_game_response['status'] === 201) {

            foreach ($input['players'] as $player) {
                $api->addPlayerToGame(
                    $resource_type_id,
                    $resource_id,
                    $create_game_response['content']['id'],
                    $player
                );
            }

            return 201;
        }

        $this->message = $create_game_response['content'];

        if ($create_game_response['status'] === 422) {
            $this->validation_errors = $create_game_response['fields'];
            return $create_game_response['status'];
        }

        return $create_game_response['status'];
    }
}
