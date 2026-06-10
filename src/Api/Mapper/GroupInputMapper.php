<?php

namespace App\Api\Mapper;

use App\Api\Model\GroupInput;
use App\Entity\Group;
use App\Service\AppUserManager;
use App\Service\RoomManager;
use Exception;

class GroupInputMapper
{
    public function __construct(
        private readonly AppUserManager $appUserManager,
        private readonly RoomManager $roomManager,
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

        $this->appUserManager->addMembers($input->members, $group);
        $this->appUserManager->addAdmins($input->admins, $group);
        $this->roomManager->addRooms($input->rooms, $group);

        return $group;
    }
}
