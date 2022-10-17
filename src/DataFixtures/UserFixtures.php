<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const DEFAULT_PASSWORD = 'password';
    public const ADMIN_REFERENCE = 'admin';
    public const USER_REFERENCE = 'user';

    private Generator $faker;

    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as [$reference, $email, $firstname, $lastname, $roles]) {
            $user = new User();
            $user->setPassword($this->userPasswordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
            $user->setFirstname($firstname);
            $user->setEmail($email);
            $user->setLastname($lastname);
            $user->setRoles($roles);

            $manager->persist($user);

            $this->addReference($reference, $user);
        }

        $manager->flush();
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        $data = [
            [
                self::ADMIN_REFERENCE,
                'admin@admin.fr',
                'Admin',
                'Admin',
                ['ROLE_USER'],
            ],
            [
                self::USER_REFERENCE,
                'user@user.fr',
                'John',
                'Doe',
                ['ROLE_USER'],
            ],
        ];

        for ($i = 0; $i <= 10; ++$i) {
            $data[] = [
                "user_$i",
                $this->faker->email(),
                $this->faker->firstName(),
                $this->faker->lastName(),
                ['ROLE_USER'],
            ];
        }

        return $data;
    }
}
