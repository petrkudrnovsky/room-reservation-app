<?php

namespace App\Api\Mapper;

use App\Api\Model\GroupInput;
use App\Entity\Group;
use App\Service\GroupManagerInterface;
use Exception;

class GroupInputMapper
{
    public function __construct(
        private readonly GroupManagerInterface $groupManager,
    ) {}

    /**
     * @throws Exception
     */
    public function toEntity(GroupInput $input, Group $group): Group
    {
        $group->setName($input->name);

        $group->clearMembers();
        $group->clearAdmins();
        $group->clearRooms();

        $this->groupManager->addGroupMembers($input->members, $group);
        $this->groupManager->addGroupAdmins($input->admins, $group);
        $this->groupManager->addRooms($input->rooms, $group);

        return $group;
    }
}
