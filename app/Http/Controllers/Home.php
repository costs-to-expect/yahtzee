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
                $resources = $api->getResources($this->resource_type_id, ['item-subtype' => $this->item_subtype_id]);

                if ($resources['status'] === 200) {
                    if (count($resources['content']) === 1) {
                        $this->resource_id = $resources['content'][0]['id'];
                    } else {
                        $create_resource_response = $api->createResource($this->resource_type_id);
                        if ($create_resource_response['status'] === 201) {
                            return redirect()->route('home');
                        }
                        abort($create_resource_response['status'], $create_resource_response['content']);
                    }
                } else {
                    abort($resources['status'], $resources['content']);
                }
            } else {
                $create_resource_type_response = $api->createResourceType();
                if ($create_resource_type_response['status'] === 201) {
                    return redirect()->route('home');
                }
                abort($create_resource_type_response['status'], $create_resource_type_response['content']);
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
