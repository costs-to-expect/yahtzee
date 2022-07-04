<?php

namespace App\Auth\Guard\Api;

//use App\Service;
use Illuminate\Contracts\Auth\Authenticatable;

class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        /*$api = new Service();
        $api->init(
            request()->cookie($this->config['cookie_user']),
            request()->cookie($this->config['cookie_bearer'])
        );

        $response = $api->getAuthUser();

        if ($response !== null && $response->hasResponse()) {
            $user = new User();
            $user->id = $response->body()['id'];
            $user->name = $response->body()['name'];
            $user->email = $response->body()['email'];

            return $user;
        }*/

        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return $this->retrieveById($identifier);
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // Not necessary
    }

    public function retrieveByCredentials(array $credentials): void
    {
        // Not necessary
    }

    public function validateCredentials(Authenticatable $user, array $credentials): void
    {
        // Not necessary
    }
}
