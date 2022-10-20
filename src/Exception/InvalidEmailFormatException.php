<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class InvalidEmailFormatException extends BadRequestException
{
    /** @var string */
    protected $message = 'The email field is not in a valid format';
}
