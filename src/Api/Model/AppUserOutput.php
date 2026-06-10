<?php

namespace App\Api\Model;

use App\Api\Model\Links\AppUserLinks;
use App\Entity\AppUser;
use Symfony\Component\Serializer\Annotation\Groups;

class AppUserOutput {
    #[Groups(['reservation:read'])]
    public int $id;
    public string $username;
    public array $roles;
    public ?string $firstName;
    public ?string $secondName;
    public ?string $email;
    public ?string $phone;
    public ?array $memberGroups = null;
    public ?array $adminGroups = null;
    public ?array $memberRooms = null;
    public ?array $adminRooms = null;
    public ?array $approvedReservations = null;
    public ?array $reservations = null;

    public function __construct(
        int $id,
        string $username,
        array $roles,
        ?string $firstName,
        ?string $secondName,
        ?string $email,
        ?string $phone,
        ?array $memberGroups = null,
        ?array $adminGroups = null,
        ?array $memberRooms = null,
        ?array $adminRooms = null,
        ?array $approvedReservations = null,
        ?array $reservations = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->roles = $roles;
        $this->firstName = $firstName;
        $this->secondName = $secondName;
        $this->email = $email;
        $this->phone = $phone;
        $this->memberGroups = $memberGroups;
        $this->adminGroups = $adminGroups;
        $this->memberRooms = $memberRooms;
        $this->adminRooms = $adminRooms;
        $this->approvedReservations = $approvedReservations;
        $this->reservations = $reservations;
    }


    public static function fromEntity(AppUser $appUser, AppUserLinks $links): self
    {
        return new self(
            $appUser->getId(),
            $appUser->getUsername(),
            $appUser->getRoles(),
            $appUser->getFirstName(),
            $appUser->getSecondName(),
            $appUser->getEmail(),
            $appUser->getPhone(),
            $links->memberGroups,
            $links->adminGroups,
            $links->memberRooms,
            $links->adminRooms,
            $links->approvedReservations,
            $links->reservations
        );
    }
}