<?php

namespace App\Api\Model;

use App\Entity\Building;
use App\Entity\Room;
use phpDocumentor\Reflection\Types\Collection;

class RoomOutput
{
    public string $name;
    public string $code;
    public bool $isPrivate;
    public array $owningGroups;
    public array $members;
    public array $admins;
    public Building $building;

    public function __construct(
        string $name,
        string $code,
        bool $isPrivate,
        array $owningGroups,
        array $members,
        array $admins,
        Building $building
    ) {
        $this->name = $name;
        $this->code = $code;
        $this->isPrivate = $isPrivate;
        $this->owningGroups = $owningGroups;
        $this->members = $members;
        $this->admins = $admins;
        $this->building = $building;
    }

    public static function fromEntity(Room $room): self
    {
        $owningGroups = [];
        foreach ($room->getOwningGroups() as $owningGroup) {
            $owningGroups[] = GroupOutput::fromEntity($owningGroup);
        }
        $members = [];
        foreach ($room->getMembers() as $member) {
            $members[] = AppUserOutput::fromEntity($member);
        }
        $admins = [];
        foreach ($room->getAdmins() as $admin) {
            $admins[] = AppUserOutput::fromEntity($admin);
        }
        return new self(
            $room->getName(),
            $room->getCode(),
            $room->isIsPrivate(),
            $owningGroups,
            $members,
            $admins,
            $room->getBuilding()
        );
    }
}