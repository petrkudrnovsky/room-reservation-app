<?php

namespace App\Service;

use App\Entity\Group;
use App\Filter\GroupFilterCriteria;

interface GroupManagerInterface
{
    public function saveToDatabase(Group $group): Group;

    public function removeFromDatabase(Group $group): void;

    public function findGroupsByFilters(GroupFilterCriteria $criteria): array;

    public function addGroupMembers(?array $members, Group $group): void;

    public function addGroupAdmins(?array $admins, Group $group): void;

    public function addRooms(?array $rooms, Group $group): void;
}
