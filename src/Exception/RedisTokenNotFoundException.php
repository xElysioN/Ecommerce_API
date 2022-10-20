<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class RedisTokenNotFoundException extends AuthenticationException
{
}
