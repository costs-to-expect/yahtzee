<?php

namespace App\Jobs;

use App\Actions\Game\Delete;
use App\Api\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteYahtzeeAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    private string $bearer_token;
    private string $resource_type_id;
    private string $resource_id;
    private string $user_id;

    private Service $service;

    public function __construct(
        string $bearer_token,
        string $resource_type_id,
        string $resource_id,
        string $user_id
    )
    {
        $this->bearer_token = $bearer_token;
        $this->resource_type_id = $resource_type_id;
        $this->resource_id = $resource_id;
        $this->user_id = $user_id;
    }

    public function handle()
    {
        $this->apiService();

        while($this->getGames() !== null) {

            foreach ($this->getGames() as $game) {
                $action = new Delete();
                try {
                    $result = $action(
                        $this->service,
                        $this->resource_type_id,
                        $this->resource_id,
                        $game['id']
                    );

                    if ($result !== 204) {
                        $this->fail((new \Exception('Unable to delete game with id ' . $game['id'])));
                    }
                } catch (\Exception $e) {
                    $this->fail($e);
                }
            }
        }

        $response = $this->service->deleteResource(
            $this->resource_type_id,
            $this->resource_id
        );

        if ($response['status'] !== 204) {
            $this->fail((new \Exception('Unable to delete the Yahtzee resource with id  ' . $this->resource_id)));
        }

        DB::table('session')
            ->where('user_id', '=', $this->user_id)
            ->delete();
    }

    public function failed(Throwable $exception)
    {
        // Send user notification of failure, etc...
    }

    private function apiService()
    {
        $this->service = new Service($this->bearer_token);
    }

    private function getGames()
    {
        $result = $this->service->getGames(
            $this->resource_type_id,
            $this->resource_id,
            [
                'limit' => 25
            ]
        );

        if ($result['status'] === 200 && count($result['content']) > 0) {
            return $result['content'];
        }

        if ($result['status'] !== 200) {
            $this->fail((new \Exception($result['content'])));
        }

        return null;
    }
}
