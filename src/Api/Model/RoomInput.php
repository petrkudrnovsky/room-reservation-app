<?php

namespace App\Api\Model;

use App\Entity\Building;
use App\Entity\Room;
use App\Repository\AppUserRepository;
use App\Repository\BuildingRepository;
use App\Repository\GroupRepository;
use App\Service\AppUserManager;
use App\Service\GroupManager;
use App\Service\ReservationManager;
use Doctrine\Common\Collections\Collection;
use Exception;
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

    /**
     * @throws Exception
     */
    public function toEntity(
        ?AppUserManager $appUserManager = null,
        ?GroupManager $groupManager = null,
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

        $appUserManager->addMembers($this->members, null, $room);
        $appUserManager->addAdmins($this->admins, null, $room);
        $groupManager->addOwningGroups($this->owningGroups, $room);

        return $room;
    }
}