<?php

namespace App\Api\Model;

use App\Entity\AppUser;
use App\Form\Constraints\UniqueUsername;
use App\Service\GroupManager;
use App\Service\ReservationManager;
use App\Service\RoomManager;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AppUserInput {
    public ?int $id;
    #[Assert\NotBlank(message: 'Username cannot be blank')]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Username must have at least 3 characters', maxMessage: 'Username must have maximum of 250 characters')]
    public ?string $username = null;
    public array $roles = [];
    #[Assert\NotBlank(message: 'Password cannot be blank')]
    public ?string $password;
    #[Assert\NotBlank(message: 'First name cannot be blank')]
    public ?string $firstName;
    #[Assert\NotBlank(message: 'Second name cannot be blank')]
    public ?string $secondName;
    #[Assert\Email(message: 'Email is not valid')]
    public ?string $email = null;
    public ?string $phone = null;
    public ?array $memberGroups = null;
    public ?array $adminGroups = null;
    public ?array $memberRooms = null;
    public ?array $adminRooms = null;
    public ?array $approvedReservations = null;
    public ?array $reservations = null;

    /**
     * @throws Exception
     */
    public function toEntity(
        AppUser $appUser = new AppUser(),
        ?GroupManager $groupManager = null,
        ?RoomManager $roomManager = null,
        ?ReservationManager $reservationManager = null
    ): AppUser {
        $appUser->setUsername($this->username);
        $appUser->setRoles($this->roles);
        $appUser->setPassword($this->password);
        $appUser->setFirstName($this->firstName);
        $appUser->setSecondName($this->secondName);
        $appUser->setEmail($this->email);
        $appUser->setPhone($this->phone);

        $appUser->clearMemberGroups();
        $appUser->clearAdminGroups();
        $appUser->clearMemberRooms();
        $appUser->clearAdminRooms();
        $appUser->clearApprovedReservations();
        $appUser->clearReservations();

        $groupManager->addUserGroups($this->memberGroups, $appUser, false);
        $groupManager->addUserGroups($this->adminGroups, $appUser, true);
        $roomManager->addUserRooms($this->memberRooms, $appUser, false);
        $roomManager->addUserRooms($this->adminRooms, $appUser, true);
        $reservationManager->addReservations($this->approvedReservations, $appUser, true);
        $reservationManager->addReservations($this->reservations, $appUser, false);

        return $appUser;
    }

    public function getPlainPassword(): ?string
    {
        return $this->password;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('username', new Assert\NotBlank());
        $metadata->addConstraint(new UniqueUsername());
    }
}