<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Filter\RoomFilterCriteria;

interface RoomManagerInterface
{
    public function saveToDatabase(Room $room): Room;

    public function deleteFromDatabase(Room $room): void;

    public function getRoomById(int $id): ?Room;

    public function findById(?int $room): ?Room;

    public function findRoomsByFilters(RoomFilterCriteria $criteria): array;

    public function unlockRoom(Room $room): void;

    public function lockRoom(Room $room): void;

    public function addRoomMembers(?array $members, Room $room): void;

    public function addRoomAdmins(?array $admins, Room $room): void;

    public function addOwningGroups(?array $owningGroups, Room $room): void;
}
