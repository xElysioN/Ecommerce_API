<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidApiTokenException extends AuthenticationException
{
}
