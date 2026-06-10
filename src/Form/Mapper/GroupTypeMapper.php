<?php

namespace App\Form\Mapper;

use App\Entity\Group;
use App\Form\Model\GroupTypeModel;
use Doctrine\Common\Collections\ArrayCollection;

class GroupTypeMapper
{
    public function toEntity(GroupTypeModel $model, ?Group $group = null): Group
    {
        if (!$group) {
            $group = new Group();
        }
        $group->setName($model->name);

        foreach ($group->getMembers() as $member) {
            $group->removeMember($member);
        }
        foreach ($model->members as $member) {
            $group->addMember($member);
        }

        foreach ($group->getAdmins() as $admin) {
            $group->removeAdmin($admin);
        }
        foreach ($model->admins as $admin) {
            $group->addAdmin($admin);
            $group->addMember($admin);
        }

        foreach ($group->getRooms() as $room) {
            $group->removeRoom($room);
        }
        foreach ($model->rooms as $room) {
            $group->addRoom($room);
        }

        return $group;
    }

    public function fromEntity(Group $group): GroupTypeModel
    {
        $model = new GroupTypeModel();
        $model->name = $group->getName();
        $model->members = new ArrayCollection(iterator_to_array($group->getMembers()));
        $model->admins = new ArrayCollection(iterator_to_array($group->getAdmins()));
        $model->rooms = new ArrayCollection(iterator_to_array($group->getRooms()));

        return $model;
    }
}
