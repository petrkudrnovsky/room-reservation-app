<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Filter\GroupFilterCriteria;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

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

    public function findGroupsByFilters(GroupFilterCriteria $criteria): array {
        $qb = $this->groupRepository->createQueryBuilder('a');

        if ($criteria->name) {
            $qb->andWhere('LOWER(a.name) LIKE :pattern')
                ->setParameter('pattern', '%' . strtolower($criteria->name) . '%');
        }

        if ($criteria->memberIds) {
            $qb->innerJoin('a.members', 'm')
                ->andWhere('m.id IN (:memberIds)')
                ->setParameter('memberIds', $criteria->memberIds);
        }

        if ($criteria->adminIds) {
            $qb->innerJoin('a.admins', 'ad')
                ->andWhere('ad.id IN (:adminIds)')
                ->setParameter('adminIds', $criteria->adminIds);
        }

        if ($criteria->roomIds) {
            $qb->innerJoin('a.rooms', 'r')
                ->andWhere('r.id IN (:roomIds)')
                ->setParameter('roomIds', $criteria->roomIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addOwningGroups(?array $owningGroups, Room $room): void {
        if (!$owningGroups) {
            return;
        }
        foreach ($owningGroups as $groupId) {
            if (is_numeric($groupId)){
                $group = $this->groupRepository->find($groupId);
                if ($group) {
                    $room->addOwningGroup($group);
                } else {
                    throw new Exception('Group not found');
                }
            } else {
                throw new Exception('Group ID must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addMemberUserGroups(?array $groups, AppUser $appUser): void
    {
        if (!$groups) {
            return;
        }
        foreach ($groups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                $appUser->addMemberGroup($group);
            } else {
                throw new Exception('Group not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addAdminUserGroups(?array $groups, AppUser $appUser): void
    {
        if (!$groups) {
            return;
        }
        foreach ($groups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                $appUser->addAdminGroup($group);
            } else {
                throw new Exception('Group not found');
            }
        }
    }
}