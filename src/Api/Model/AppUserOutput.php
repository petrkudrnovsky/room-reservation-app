<?php

namespace App\Api\Model;

use App\Entity\AppUser;

class AppUserOutput {
    public int $id;
    public string $username;
    public array $roles;
    public ?string $firstName;
    public ?string $secondName;
    public ?string $email;
    public ?string $phone;

    public function __construct(
        int $id,
        string $username,
        array $roles,
        ?string $firstName,
        ?string $secondName,
        ?string $email,
        ?string $phone,
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->roles = $roles;
        $this->firstName = $firstName;
        $this->secondName = $secondName;
        $this->email = $email;
        $this->phone = $phone;
    }


    public static function fromEntity(AppUser $entity): self
    {
        return new self(
            $entity->getId(),
            $entity->getUsername(),
            $entity->getRoles(),
            $entity->getFirstName(),
            $entity->getSecondName(),
            $entity->getEmail(),
            $entity->getPhone()
        );
    }
}