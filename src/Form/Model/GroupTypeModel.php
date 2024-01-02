<?php

namespace App\Form\Model;

use App\Entity\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class GroupTypeModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?Collection $members = null;
    public ?Collection $admins = null;
    public ?Collection $rooms = null;

    public function toEntity(?Group $group = null): Group
    {
        if(!$group) {
            $group = new Group();
        }
        $group->setName($this->name);

        foreach ($group->getMembers() as $member) {
            $group->removeMember($member);
        }
        foreach ($this->members as $member) {
            $group->addMember($member);
        }

        foreach ($group->getAdmins() as $admin) {
            $group->removeAdmin($admin);
        }
        foreach ($this->admins as $admin) {
            $group->addAdmin($admin);
            $group->addMember($admin);
        }

        foreach ($group->getRooms() as $room) {
            $group->removeRoom($room);
        }
        foreach ($this->rooms as $room) {
            $group->addRoom($room);
        }

        return $group;
    }

    public static function fromEntity(Group $group): self
    {
        $model = new self();
        $model->name = $group->getName();
        $model->members = new ArrayCollection(iterator_to_array($group->getMembers()));
        $model->admins = new ArrayCollection(iterator_to_array($group->getAdmins()));
        $model->rooms = new ArrayCollection(iterator_to_array($group->getRooms()));

        return $model;
    }
}