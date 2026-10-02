<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\View\Component;

class Footer extends Component
{
    public function __construct()
    {
        //
    }

    public function render()
    {
        $version = Config::get('app.version');

        return view(
            'components.footer',
            [
                'version' => $version['app'],
                'release_date' => $version['date'],
            ]
        );
    }
}
