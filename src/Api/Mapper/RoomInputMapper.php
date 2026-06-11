<?php

namespace App\Api\Mapper;

use App\Api\Model\RoomInput;
use App\Entity\Room;
use App\Repository\BuildingRepository;
use App\Service\AppUserManager;
use App\Service\GroupManager;
use Exception;

class RoomInputMapper
{
    public function __construct(
        private readonly AppUserManager $appUserManager,
        private readonly GroupManager $groupManager,
        private readonly BuildingRepository $buildingRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function toEntity(RoomInput $input, Room $room): Room
    {
        $room->setName($input->name);
        $room->setCode($input->code);
        $room->setIsPrivate($input->isPrivate);
        $room->setBuilding($this->buildingRepository->find($input->buildingId));

        $room->clearMembers();
        $room->clearAdmins();
        $room->clearOwningGroups();

        $this->appUserManager->addRoomMembers($input->members, $room);
        $this->appUserManager->addRoomAdmins($input->admins, $room);
        $this->groupManager->addOwningGroups($input->owningGroups, $room);

        return $room;
    }
}
