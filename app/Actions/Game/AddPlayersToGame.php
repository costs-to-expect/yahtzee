<?php
declare(strict_types=1);

namespace App\Actions\Game;

use App\Actions\Action;
use App\Api\Service;

class AddPlayersToGame extends Action
{
    public function __invoke(
        Service $api,
        string $resource_type_id,
        string $resource_id,
        string $game_id,
        array $input
    ): int
    {
        if (array_key_exists('players', $input) === false) {
            $this->message = 'Missing players';
            $this->validation_errors['players'] = ['errors' => ['Please select the additional players']];
            return 422;
        }

        foreach ($input['players'] as $player) {
            $response = $api->addPlayerToGame(
                $resource_type_id,
                $resource_id,
                $game_id,
                $player
            );

            if ($response['status'] === 201) {
                continue;
            }

            $this->message = $response['content'];

            if ($response['status'] === 422) {
                $this->validation_errors = $response['fields'];
                $this->message = $response['content'];
            }

            return $response['status'];
        }

        return 201;
    }
}
