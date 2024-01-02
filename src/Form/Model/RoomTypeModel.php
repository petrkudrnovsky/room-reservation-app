<?php

namespace App\Form\Model;

use App\Entity\Building;
use App\Entity\Room;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class RoomTypeModel
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

        foreach ($room->getOwningGroups() as $owningGroup) {
            $room->removeOwningGroup($owningGroup);
        }
        foreach ($this->owningGroups as $owningGroup) {
            $room->addOwningGroup($owningGroup);
        }

        foreach ($room->getMembers() as $member) {
            $room->removeMember($member);
        }
        foreach ($this->members as $member) {
            $room->addMember($member);
        }

        foreach ($room->getAdmins() as $admin) {
            $room->removeAdmin($admin);
        }
        foreach ($this->admins as $admin) {
            $room->addAdmin($admin);
            $room->addMember($admin);
        }

        return $room;
    }

    public static function fromEntity(Room $room): self
    {
        $model = new self();
        $model->name = $room->getName();
        $model->code = $room->getCode();
        $model->isPrivate = $room->isIsPrivate();
        $model->owningGroups = new ArrayCollection(iterator_to_array($room->getOwningGroups()));
        $model->members = new ArrayCollection(iterator_to_array($room->getMembers()));
        $model->admins = new ArrayCollection(iterator_to_array($room->getAdmins()));
        $model->building = $room->getBuilding();

        return $model;
    }
}