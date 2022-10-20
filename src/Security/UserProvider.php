<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\RedisTokenNotFoundException;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private readonly \Redis $userRedisClient, private readonly UserRepository $userRepository)
    {
    }

    /**
     * @throws \RedisException
     * @throws RedisTokenNotFoundException
     * @throws UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var string|bool $data */
        $data = $this->userRedisClient->get($identifier);

        if (false === $data) {
            throw new RedisTokenNotFoundException();
        }

        if (!$user = $this->userRepository->find($data)) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new \RuntimeException('REFRESH USER, stateless : true');
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
