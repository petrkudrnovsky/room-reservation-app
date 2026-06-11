<?php

namespace App\Service;

use App\Entity\Group;
use App\Filter\GroupFilterCriteria;
use App\Repository\AppUserRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class GroupManager implements GroupManagerInterface
{
    public function __construct(
        public EntityManagerInterface $em,
        public GroupRepository $groupRepository,
        private readonly AppUserRepository $appUserRepository,
        private readonly RoomRepository $roomRepository,
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

    public function findGroupsByFilters(GroupFilterCriteria $criteria): array
    {
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
    public function addGroupMembers(?array $members, Group $group): void
    {
        if ($members === null) {
            return;
        }
        foreach ($members as $memberId) {
            if (is_numeric($memberId)) {
                $member = $this->appUserRepository->find($memberId);
                if ($member) {
                    $group->addMember($member);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Member must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addGroupAdmins(?array $admins, Group $group): void
    {
        if ($admins === null) {
            return;
        }
        foreach ($admins as $adminId) {
            if (is_numeric($adminId)) {
                $admin = $this->appUserRepository->find($adminId);
                if ($admin) {
                    $group->addAdmin($admin);
                    $group->addMember($admin);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Admin must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addRooms(array $rooms, Group $group): void
    {
        foreach ($rooms as $roomId) {
            if (is_numeric($roomId)) {
                $room = $this->roomRepository->find($roomId);
                if ($room) {
                    $group->addRoom($room);
                } else {
                    throw new Exception('Room not found');
                }
            } else {
                throw new Exception('Room ID must be an integer value');
            }
        }
    }
}
