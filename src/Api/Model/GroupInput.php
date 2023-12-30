<?php

namespace App\Api\Model;

use App\Entity\Group;
use App\Service\AppUserManager;
use App\Service\RoomManager;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

class GroupInput {
    #[Assert\NotBlank(message: 'Group name cannot be blank')]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?array $members = null;
    public ?array $admins = null;
    public ?array $rooms = null;

    /**
     * @throws Exception
     */
    public function toEntity(
        ?AppUserManager $appUserManager = null,
        ?RoomManager $roomManager = null,
        Group $group = new Group()
    ): Group
    {
        $group->setName($this->name);

        $group->clearMembers();
        $group->clearAdmins();
        $group->clearRooms();

        $appUserManager->addMembers($this->members, $group);
        $appUserManager->addAdmins($this->admins, $group);
        $roomManager->addRooms($this->rooms, $group);

        return $group;
    }
}
