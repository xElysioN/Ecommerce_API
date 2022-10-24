<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use App\Dto\UserRegistration;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @param array<string> $uriVariables
     * @param array<string> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        assert($data instanceof UserRegistration, 'The class must be UserRegistration');

        $user = new User();
        $user->setEmail($data->email);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $data->password));
        $user->setFirstname('test');
        $user->setLastname('test');
        $user->setRoles([User::DEFAULT_ROLE]);

        $this->validator->validate($user);

        return $this->decorated->process($user, $operation, $uriVariables, $context);
    }
}
