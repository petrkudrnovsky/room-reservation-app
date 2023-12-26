<?php

namespace App\Api\Model;

use App\Entity\Group;
use App\Entity\Room;

class GroupOutput {
    public int $id;
    public string $name;
    public array $members;
    public array $admins;
    public array $rooms;

    public function __construct(
        int $id,
        string $name,
        array $members,
        array $admins,
        array $rooms
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->members = $members;
        $this->admins = $admins;
        $this->rooms = $rooms;
    }

    public static function fromEntity(Group $entity): self
    {
        $members = [];
        foreach ($entity->getMembers() as $member) {
            $members[] = AppUserOutput::fromEntity($member);
        }
        $admins = [];
        foreach ($entity->getAdmins() as $admin) {
            $admins[] = AppUserOutput::fromEntity($admin);
        }

        $rooms = $entity->getRooms()->map(fn (Room $room) => $room->getName())->toArray();

        return new self(
            $entity->getId(),
            $entity->getName(),
            $members,
            $admins,
            $rooms
        );
    }
}

