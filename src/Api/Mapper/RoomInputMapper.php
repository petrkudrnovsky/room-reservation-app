<?php

namespace App\Api\Mapper;

use App\Api\Model\RoomInput;
use App\Entity\Room;
use App\Repository\BuildingRepository;
use App\Service\RoomManagerInterface;
use Exception;

class RoomInputMapper
{
    public function __construct(
        private readonly RoomManagerInterface $roomManager,
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

        $this->roomManager->addRoomMembers($input->members, $room);
        $this->roomManager->addRoomAdmins($input->admins, $room);
        $this->roomManager->addOwningGroups($input->owningGroups, $room);

        return $room;
    }
}
