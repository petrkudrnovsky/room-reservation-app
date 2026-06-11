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

        $this->groupManager->addMemberUserGroups($input->memberGroups, $appUser);
        $this->groupManager->addAdminUserGroups($input->adminGroups, $appUser);
        $this->roomManager->addMemberRooms($input->memberRooms, $appUser);
        $this->roomManager->addAdminRooms($input->adminRooms, $appUser);
        $this->reservationManager->addApprovedReservations($input->approvedReservations, $appUser);
        $this->reservationManager->addPendingReservations($input->reservations, $appUser);

        return $appUser;
    }
}
