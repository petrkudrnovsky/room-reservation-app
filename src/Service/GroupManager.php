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

    public function removeFromDatabase(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }

    public function findGroupsByName(?string $name): array {
        $qb = $this->groupRepository->createQueryBuilder('a');

        if ($name) {
            $pattern = '%' . strtolower($name) . '%';
            $qb->andWhere('LOWER(a.name) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        return $qb->getQuery()->getResult();
    }
}