<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\InvalidApiTokenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has(User::TOKEN_HEADER_NAME) && 'login' !== $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get(User::TOKEN_HEADER_NAME);

        if (empty($token) || User::TOKEN_SIZE_HEX !== strlen($token)) {
            throw new InvalidApiTokenException();
        }

        return new SelfValidatingPassport(new UserBadge($token));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->debug($exception);

        return new JsonResponse(null, Response::HTTP_UNAUTHORIZED);
    }
}
