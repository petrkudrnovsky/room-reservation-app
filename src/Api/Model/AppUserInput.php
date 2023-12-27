<?php

namespace App\Api\Model;

use App\Entity\AppUser;
use Symfony\Component\Validator\Constraints as Assert;

class AppUserInput {
    public ?int $id;
    #[Assert\NotBlank]
    public ?string $username;

    public array $roles = [];

    public ?string $password;

    public ?string $firstName;

    public ?string $secondName;

    #[Assert\Email(message: 'Email musí byť platný')]
    public ?string $email;

    public ?string $phone;



    public function toEntity(AppUser $appUser = new AppUser()): AppUser {
        $appUser->setUsername($this->username);
        $appUser->setRoles($this->roles);
        $appUser->setPassword($this->password);
        $appUser->setFirstName($this->firstName);
        $appUser->setSecondName($this->secondName);
        $appUser->setEmail($this->email);
        $appUser->setPhone($this->phone);

        return $appUser;
    }

    public function getPlainPassword(): ?string
    {
        return $this->password;
    }
}