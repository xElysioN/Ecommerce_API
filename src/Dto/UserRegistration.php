<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class UserRegistration
{
    #[Assert\Email(mode: 'strict')]
    #[Assert\NotBlank]
    public mixed $email;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(
        min: 6,
        max: 50
    )]
    #[Assert\Regex(
        pattern: "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*$/",
        message: 'Use 1 upper case letter, 1 lower case letter, and 1 number'
    )]
    public mixed $password;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public mixed $firstname;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public mixed $lastname;
}
