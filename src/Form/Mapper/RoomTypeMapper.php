<?php

namespace App\Form\Mapper;

use App\Entity\Room;
use App\Form\Model\RoomTypeModel;
use Doctrine\Common\Collections\ArrayCollection;

class RoomTypeMapper
{
    public function toEntity(RoomTypeModel $model, ?Room $room = null): Room
    {
        if (!$room) {
            $room = new Room();
        }
        $room->setName($model->name);
        $room->setCode($model->code);
        $room->setIsPrivate($model->isPrivate);
        $room->setBuilding($model->building);

        foreach ($room->getOwningGroups() as $owningGroup) {
            $room->removeOwningGroup($owningGroup);
        }
        foreach ($model->owningGroups as $owningGroup) {
            $room->addOwningGroup($owningGroup);
        }

        foreach ($room->getMembers() as $member) {
            $room->removeMember($member);
        }
        foreach ($model->members as $member) {
            $room->addMember($member);
        }

        foreach ($room->getAdmins() as $admin) {
            $room->removeAdmin($admin);
        }
        foreach ($model->admins as $admin) {
            $room->addAdmin($admin);
            $room->addMember($admin);
        }

        return $room;
    }

    public function fromEntity(Room $room): RoomTypeModel
    {
        $model = new RoomTypeModel();
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
