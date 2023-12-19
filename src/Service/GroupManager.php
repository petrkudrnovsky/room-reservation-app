<?php

namespace App\Service;

use App\Entity\Group;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;

class GroupManager
{
    public function __construct(
        public EntityManagerInterface $em,
        public GroupRepository $groupRepository,
    ) {}

    public function saveToDatabase(Group $group): Group
    {
        $this->em->persist($group);
        $this->em->flush();
        return $group;
    }
}