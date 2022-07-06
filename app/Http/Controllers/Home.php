<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Api\Service;
use Illuminate\Http\Request;

class Home extends Controller
{
    private string $resource_type_id;
    private string $resource_id;

    protected function init(Request $request)
    {
        $api = new Service($request->cookie($this->config['cookie_bearer']));
        $resource_types = $api->getResourceTypes(['item-type' => $this->item_type_id]);

        if ($resource_types['status'] === 200) {
            if (count($resource_types['content']) === 1) {

                $this->resource_type_id = $resource_types['content'][0]['id'];

                $resources = $api->getResources($this->resource_type_id);

                if ($resources['status'] === 200) {

                    dd($resources);

                    if (count($resources['content']) === 1) {

                        dd($resources);
                        //"The requested route is invalid, please visit the index of the API to see all the valid routes https://api.costs-to-expect.com/v2"

                        $this->resource_id = $resources['content'][0]['id'];
                    } else {
                        // Create the game resource type
                        // Refresh the page
                    }
                } else {
                    abort($resources['status'], $resources['content']);
                }
            } else {
                $response = $api->createResourceType();
                if ($response['status'] === 201) {
                    return redirect()->route('home');
                }

                abort($response['status'], $response['content']);
            }
        } else {
            abort($resource_types['status'], $resource_types['content']);
        }
    }

    public function index(Request $request)
    {
        $this->init($request);

        return view(
            'home',
            [
                'resource_type_id' => $this->resource_type_id,
                'resource_id' => $this->resource_id,
            ]
        );
    }
}
