<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;

abstract class ApiTestCase extends BaseApiTestCase
{
    public static function createAuthenticatedClient(string $token): Client
    {
        return static::createClient([], [
            'headers' => [
                User::TOKEN_HEADER_NAME => $token,
            ],
        ]);
    }
}
