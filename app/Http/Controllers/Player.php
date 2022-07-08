<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Player\Create;
use Illuminate\Http\Request;

class Player extends Controller
{
    public function index(Request $request)
    {
        $this->boostrap($request);

        return view(
            'players',
            [
                'players' => $this->getPlayers($this->resource_type_id),
            ]
        );
    }

    public function newPlayer(Request $request)
    {
        $this->boostrap($request);

        return view(
            'new-player',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,

                'errors' => session()->get('validation.errors')
            ]
        );
    }

    public function newPlayerProcess(Request $request)
    {
        $this->boostrap($request);

        $action = new Create();
        $result = $action($this->api, $this->resource_type_id, $request->only(['name', 'description']));

        if ($result === 201) {
            return redirect()->route('players');
        }

        if ($result === 422) {
            return redirect()->route('player.create.view')
                ->withInput()
                ->with('validation.errors',$action->getValidationErrors());
        }

        abort($result, $action->getMessage());
    }
}
