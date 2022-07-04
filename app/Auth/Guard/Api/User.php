<?php

declare(strict_types=1);

namespace App\Auth\Guard\Api;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Config;

class User extends Authenticatable
{
    protected $keyType = 'string';

    public function getAuthIdentifier()
    {
        $config = Config::get('app.config');
        return request()?->cookie($config['cookie_user']);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
