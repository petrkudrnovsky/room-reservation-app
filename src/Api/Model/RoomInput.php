<?php

namespace App\Api\Model;

use App\Entity\Building;
use App\Entity\Room;
use App\Repository\AppUserRepository;
use App\Repository\BuildingRepository;
use App\Repository\GroupRepository;
use App\Service\AppUserManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class RoomInput
{
    public ?int $id;
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 250, minMessage: 'Room name must have at least 1 character', maxMessage: 'Room name must have maximum of 250 characters')]
    public ?string $name = null;
    #[Assert\NotBlank]
    public ?string $code = null;
    public ?bool $isPrivate = true;
    public ?array $owningGroups = null;
    public ?array $members = null;
    public ?array $admins = null;
    public ?int $buildingId = null;

    public function __construct(
    ) {
    }

    public function toEntity(
        ?AppUserRepository $userRepository = null,
        ?GroupRepository $groupRepository = null,
        ?BuildingRepository $buildingRepository = null,
        Room $room = new Room()): Room
    {
        $room->setName($this->name);
        $room->setCode($this->code);
        $room->setIsPrivate($this->isPrivate);
        $room->setBuilding($buildingRepository->find($this->buildingId));
        $room->clearMembers();
        $room->clearAdmins();
        $room->clearOwningGroups();

        foreach ($this->members as $member) {
            $user = $userRepository->find($member);
            if ($user) {
                $room->addMember($user);
            } else {
                throw new \Exception('User not found');
            }
        }
        foreach ($this->admins as $admin) {
            $user = $userRepository->find($admin);
            if ($user) {
                $room->addAdmin($user);
            } else {
                throw new \Exception('User not found');
            }
        }

        foreach ($this->owningGroups as $groupId) {
            $group = $groupRepository->find($groupId);
            if ($group) {
                $room->addOwningGroup($group);
            } else {
                throw new \Exception('Group not found');
            }
        }
        return $room;
    }

}