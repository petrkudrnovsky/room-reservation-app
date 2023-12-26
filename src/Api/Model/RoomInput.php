<?php

namespace App\Api\Model;

use App\Entity\Building;
use App\Entity\Room;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class RoomInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 250, minMessage: 'Room name must have at least 1 character', maxMessage: 'Room name must have maximum of 250 characters')]
    public ?string $name = null;
    #[Assert\NotBlank]
    public ?string $code = null;
    public ?bool $isPrivate = true;
    public ?Collection $owningGroups = null;
    public ?Collection $members = null;
    public ?Collection $admins = null;
    #[Assert\NotBlank]
    public ?Building $building = null;

    public function toEntity(?Room $room = null): Room
    {
        if(!$room) {
            $room = new Room();
        }
        $room->setName($this->name);
        $room->setCode($this->code);
        $room->setIsPrivate($this->isPrivate);
        $room->setBuilding($this->building);

        foreach ($this->owningGroups as $owningGroup) {
            $room->addOwningGroup($owningGroup);
        }
        foreach ($this->members as $member) {
            $room->addMember($member);
        }
        foreach ($this->admins as $admin) {
            $room->addAdmin($admin);
        }

        return $room;
    }

}