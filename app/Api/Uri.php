<?php
declare(strict_types=1);

namespace App\Api;

use JetBrains\PhpStorm\ArrayShape;

class Uri
{
    private const VERSION = 'v2';

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function authSignIn(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/login',
            'name' => 'Sign-in'
        ];
    }
}
