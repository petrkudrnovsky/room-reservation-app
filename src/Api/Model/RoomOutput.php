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
    public array $reservations;
    public string $buildingName;
    public string $buildingCode;

    public function __construct(
        int $id,
        string $name,
        string $code,
        bool $isPrivate,
        array $owningGroups,
        array $members,
        array $admins,
        array $reservations,
        string $buildingName,
        string $buildingCode
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->isPrivate = $isPrivate;
        $this->owningGroups = $owningGroups;
        $this->members = $members;
        $this->admins = $admins;
        $this->reservations = $reservations;
        $this->buildingName = $buildingName;
        $this->buildingCode = $buildingCode;
    }

    public static function fromEntity(
        Room $entity,
        array $membersUrls,
        array $adminsUrls,
        array $owningGroupsUrls,
        array $reservationUrls ): self
    {
        return new self(
            $entity->getId(),
            $entity->getName(),
            $entity->getCode(),
            $entity->isIsPrivate(),
            $owningGroupsUrls,
            $membersUrls,
            $adminsUrls,
            $reservationUrls,
            $entity->getBuilding()->getName(),
            $entity->getBuilding()->getCode()
        );
    }
}