<?php

namespace App\DataFixtures\Processor;

use App\Entity\User;
use Fidry\AliceDataFixtures\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public const ADMIN_TOKEN = '0000000000000000000000000000000000000000';
    public const USER_TOKEN = '1000000000000000000000000000000000000000';
    public const TOKENS = [
        'admin@admin.fr' => self::ADMIN_TOKEN,
        'user@user.fr' => self::USER_TOKEN,
    ];

    private UserPasswordHasherInterface $userPasswordHasher;
    private \Redis $redisClient;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, \Redis $userRedisClient)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->redisClient = $userRedisClient;
    }

    public function preProcess(string $id, object $object): void
    {
        if (!$object instanceof User) {
            return;
        }

        $object->setPassword($this->userPasswordHasher->hashPassword($object, $object->getPassword()));
    }

    /**
     * @throws \RedisException
     */
    public function postProcess(string $id, object $object): void
    {
        if (!$object instanceof User || !array_key_exists($object->getEmail(), self::TOKENS)) {
            return;
        }

        $this->redisClient->set(self::TOKENS[$object->getEmail()], $object->getId());
    }
}
