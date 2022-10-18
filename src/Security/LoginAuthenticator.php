<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\InvalidEmailFormatException;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly UserRepository $userRepository, private readonly \Redis $userRedisClient, private readonly bool $debug)
    {
    }

    public function supports(Request $request): ?bool
    {
        return 'login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    /**
     * @throws \JsonException
     */
    public function authenticate(Request $request): Passport
    {
        $content = $request->getContent();
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $validator = Validation::createValidator();

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            throw new BadRequestException("Fields must be 'email' & 'password'");
        }

        $violations = $validator->validate($email, [new Email(['mode' => 'strict'])]);
        if (0 !== count($violations)) {
            throw new InvalidEmailFormatException();
        }

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            }),
            new PasswordCredentials($password)
        );
    }

    /**
     * @throws \RedisException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $token = bin2hex(random_bytes(User::TOKEN_SIZE));
        $this->userRedisClient->set($token, $user->getId(), User::TOKEN_DURATION);

        return new JsonResponse([
            'access_token' => $token,
            'token_type' => User::TOKEN_HEADER_NAME,
            'expires_in' => User::TOKEN_DURATION,
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'type' => 'https://datatracker.ietf.org/doc/html/rfc2616#section-10.4.2',
            'title' => 'An error occurred',
            'detail' => 'Bad credentials.',
            'status' => Response::HTTP_UNAUTHORIZED,
        ];

        if ($this->debug) {
            $data['exception'] = $exception->getMessage();
            $data['trace'] = $exception->getTrace();
        }

        return new JsonResponse(
            $data,
            Response::HTTP_UNAUTHORIZED
        );
    }
}
