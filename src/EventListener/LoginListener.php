<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Exception threw when there is incorrect fields in login.
 */
class LoginListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!($exception instanceof BadRequestException) &&
            !($exception instanceof \JsonException) &&
            'login' !== $event->getRequest()->get('_route')
        ) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'type' => 'https://datatracker.ietf.org/doc/html/rfc2616#section-10.4.1',
                'title' => 'An error occurred',
                'detail' => $exception->getMessage(),
                'status' => Response::HTTP_BAD_REQUEST,
            ],
            Response::HTTP_BAD_REQUEST
        ));
    }
}
