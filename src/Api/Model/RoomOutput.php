<?php

namespace App\Api\Model;

use App\Entity\Room;

class RoomOutput
{
    public int $id;
    public string $name;
    public string $code;
    public bool $isPrivate;
    public array $owningGroups;
    public array $members;
    public array $admins;
    public string $building;

    public function __construct(
        int $id,
        string $name,
        string $code,
        bool $isPrivate,
        array $owningGroups,
        array $members,
        array $admins,
        string $building
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->isPrivate = $isPrivate;
        $this->owningGroups = $owningGroups;
        $this->members = $members;
        $this->admins = $admins;
        $this->building = $building;
    }

    public static function fromEntity(Room $entity): self
    {
        $owningGroups = [];
        foreach ($entity->getOwningGroups() as $owningGroup) {
            $owningGroups[] = $owningGroup->getName();
        }
        $members = [];
        foreach ($entity->getMembers() as $member) {
            $members[] = AppUserOutput::fromEntity($member);
        }
        $admins = [];
        foreach ($entity->getAdmins() as $admin) {
            $admins[] = AppUserOutput::fromEntity($admin);
        }

        return new self(
            $entity->getId(),
            $entity->getName(),
            $entity->getCode(),
            $entity->isIsPrivate(),
            $owningGroups,
            $members,
            $admins,
            $entity->getBuilding()->getName()
        );
    }
}