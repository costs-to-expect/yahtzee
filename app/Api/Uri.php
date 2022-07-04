<?php
declare(strict_types=1);

namespace App\Api;

use JetBrains\PhpStorm\ArrayShape;

class Uri
{
    private const VERSION = 'v2';

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function api(): array
    {
        return [
            'uri' => '/' . self::VERSION,
            'name' => 'API'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function authCheck(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/check',
            'name' => 'API Authentication check'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function authUser(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/user',
            'name' => 'API user details'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function signIn(): array
    {
        return [
            'uri' => '/' . self::VERSION . '/auth/login',
            'name' => 'Sign-in'
        ];
    }
}
