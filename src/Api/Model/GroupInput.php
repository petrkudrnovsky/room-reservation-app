<?php

namespace App\Api\Model;

use App\Entity\Group;
use App\Repository\AppUserRepository;
use App\Repository\RoomRepository;
use Symfony\Component\Validator\Constraints as Assert;

class GroupInput {
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?array $members = null;
    public ?array $admins = null;
    public ?array $rooms = null;

    public function toEntity(?Group $group = null, ?AppUserRepository $userRepository = null, ?RoomRepository $roomRepository = null): Group
    {
        if(!$group) {
            $group = new Group();
        }
        $group->setName($this->name);
        $group->clearMembers();
        $group->clearAdmins();
        $group->clearRooms();

        foreach ($this->members as $member) {
            $user = $userRepository->find($member);
            if ($user) {
                $group->addMember($user);
            } else {
                throw new \Exception('User not found');
            }
        }
        foreach ($this->admins as $admin) {
            $user = $userRepository->find($admin);
            if ($user) {
                $group->addAdmin($user);
            } else {
                throw new \Exception('User not found');
            }
        }
        foreach ($this->rooms as $roomId) {
            $room = $roomRepository->find($roomId);
            if ($room) {
                $group->addRoom($room);
            } else {
                throw new \Exception('Room not found');
            }
        }
        return $group;
    }
}
