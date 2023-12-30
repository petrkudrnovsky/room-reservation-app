<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
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

    public function findGroupsByFilters(?string $name, $filters): array {
        $qb = $this->groupRepository->createQueryBuilder('a');
        foreach ($filters as $key => $value) {
            if ($value) {
                $filters[$key] = explode(',', $value);
            }
        }

        if ($name) {
            $pattern = '%' . strtolower($name) . '%';
            $qb->andWhere('LOWER(a.name) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        if ($filters['members']) {
            $qb->innerJoin('a.members', 'm')
                ->andWhere('m.id IN (:membersUsernames)')
                ->setParameter('membersUsernames', $filters['members']);
        }

        if ($filters['admins']) {
            $qb->innerJoin('a.admins', 'ad')
                ->andWhere('ad.id IN (:admins)')
                ->setParameter('admins', $filters['admins']);
        }

        if ($filters['rooms']) {
            $qb->innerJoin('a.rooms', 'r')
                ->andWhere('r.id IN (:rooms)')
                ->setParameter('rooms', $filters['rooms']);
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
    public function addUserGroups(?array $memberGroups, AppUser $appUser, bool $isAdmin): void
    {
        if (!$memberGroups) {
            return;
        }
        foreach ($memberGroups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                if ($isAdmin) {
                    $appUser->addAdminGroup($group);
                } else {
                    $appUser->addMemberGroup($group);
                }
            } else {
                throw new Exception('Group not found');
            }
        }
    }
}