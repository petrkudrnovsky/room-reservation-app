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

    public static function fromEntity(Group $group, array $membersUrls, array $adminsUrls, array $roomsUrls): self
    {
        return new self(
            $group->getId(),
            $group->getName(),
            $membersUrls,
            $adminsUrls,
            $roomsUrls
        );
    }
}

