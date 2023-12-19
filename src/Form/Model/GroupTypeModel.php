<?php

namespace App\Form\Model;

use App\Entity\Group;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class GroupTypeModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?Collection $members = null;
    public ?Collection $admins = null;

    public function toEntity(?Group $group = null): Group
    {
        if(!$group) {
            $group = new Group();
        }
        $group->setName($this->name);

        foreach ($this->members as $member) {
            $group->addMember($member);
        }
        foreach ($this->admins as $admin) {
            $group->addAdmin($admin);
        }

        return $group;
    }

    public static function fromEntity(Group $group): self
    {
        $model = new self();
        $model->name = $group->getName();
        $model->members = $group->getMembers();
        $model->admins = $group->getAdmins();

        return $model;
    }
}