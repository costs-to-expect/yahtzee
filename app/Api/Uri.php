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

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function games(string $resource_type_id, string $resource_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id .
            '/resources/' . $resource_id . '/items';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Games'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function resources(string $resource_type_id, array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types/' . $resource_type_id . '/resources';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Resources'
        ];
    }

    #[ArrayShape(['uri' => "string", 'name' => "string"])]
    public static function resourceTypes(array $parameters = []): array
    {
        $uri = '/' . self::VERSION . '/resource-types';
        if (count($parameters) > 0) {
            $uri .= '?' . http_build_query($parameters);
        }

        return [
            'uri' => $uri,
            'name' => 'Resource types'
        ];
    }
}
