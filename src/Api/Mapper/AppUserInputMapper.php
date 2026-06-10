<?php

namespace App\Api\Mapper;

use App\Api\Model\AppUserInput;
use App\Entity\AppUser;
use App\Service\GroupManager;
use App\Service\ReservationManager;
use App\Service\RoomManager;
use Exception;

class AppUserInputMapper
{
    public function __construct(
        private readonly GroupManager $groupManager,
        private readonly RoomManager $roomManager,
        private readonly ReservationManager $reservationManager,
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

        $this->groupManager->addUserGroups($input->memberGroups, $appUser, false);
        $this->groupManager->addUserGroups($input->adminGroups, $appUser, true);
        $this->roomManager->addUserRooms($input->memberRooms, $appUser, false);
        $this->roomManager->addUserRooms($input->adminRooms, $appUser, true);
        $this->reservationManager->addReservations($input->approvedReservations, $appUser, true);
        $this->reservationManager->addReservations($input->reservations, $appUser, false);

        return $appUser;
    }
}
