<?php

namespace App\Api\Mapper;

use App\Api\Model\AppUserInput;
use App\Entity\AppUser;
use App\Service\AppUserManagerInterface;
use App\Service\ReservationManagerInterface;
use Exception;

class AppUserInputMapper
{
    public function __construct(
        private readonly AppUserManagerInterface $appUserManager,
        private readonly ReservationManagerInterface $reservationManager,
    ) {}

    /**
     * @throws Exception
     */
    public function toEntity(AppUserInput $input, AppUser $appUser): AppUser
    {
        $appUser->setUsername($input->username);
        $appUser->setRoles($input->roles);
        $appUser->setPassword($input->password);
        $appUser->setFirstName($input->firstName);
        $appUser->setSecondName($input->secondName);
        $appUser->setEmail($input->email);
        $appUser->setPhone($input->phone);

        $appUser->clearMemberGroups();
        $appUser->clearAdminGroups();
        $appUser->clearMemberRooms();
        $appUser->clearAdminRooms();
        $appUser->clearApprovedReservations();
        $appUser->clearReservations();

        $this->appUserManager->addMemberUserGroups($input->memberGroups, $appUser);
        $this->appUserManager->addAdminUserGroups($input->adminGroups, $appUser);
        $this->appUserManager->addMemberRooms($input->memberRooms, $appUser);
        $this->appUserManager->addAdminRooms($input->adminRooms, $appUser);
        $this->reservationManager->addApprovedReservations($input->approvedReservations, $appUser);
        $this->reservationManager->addPendingReservations($input->reservations, $appUser);

        return $appUser;
    }
}
