<?php

namespace App\Api\Model;

use App\Entity\AppUser;
use Symfony\Component\Validator\Constraints as Assert;

class AppUserInput {
    #[Assert\NotBlank]
    private ?string $username;

    private array $roles;

    private ?string $firstName;

    private ?string $secondName;

    #[Assert\Email(message: 'Email musí byť platný')]
    private ?string $email;

    private ?string $phone;


    public function toEntity(AppUser $appUser = new AppUser()): AppUser {
        $appUser->setUsername($this->username);
        $appUser->setRoles($this->roles);
        $appUser->setFirstName($this->firstName);
        $appUser->setSecondName($this->secondName);
        $appUser->setEmail($this->email);
        $appUser->setPhone($this->phone);

        return $appUser;
    }
}