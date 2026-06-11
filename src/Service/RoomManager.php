<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Filter\RoomFilterCriteria;
use App\Repository\AppUserRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomManager implements RoomManagerInterface
{
    public function __construct(
        public EntityManagerInterface $em,
        public RoomRepository $roomRepository,
        private readonly AppUserRepository $appUserRepository,
        private readonly GroupRepository $groupRepository,
    ) {}

    public function saveToDatabase(Room $room): Room
    {
        $this->em->persist($room);
        $this->em->flush();
        return $room;
    }

    public function deleteFromDatabase(Room $room): void
    {
        $reservations = $room->getReservations();
        foreach ($reservations as $reservation) {
            $this->em->remove($reservation);
        }
        $this->em->remove($room);
        $this->em->flush();
    }

    public function getRoomById(int $id): ?Room
    {
        $room = $this->roomRepository->find($id);
        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }
        return $room;
    }

    public function findById(?int $room): ?Room
    {
        return $this->roomRepository->find($room);
    }

    public function findRoomsByFilters(RoomFilterCriteria $criteria): array
    {
        $qb = $this->roomRepository->createQueryBuilder('a');

        if ($criteria->name) {
            $qb->andWhere('LOWER(a.name) LIKE :namePattern')
                ->setParameter('namePattern', '%' . strtolower($criteria->name) . '%');
        }

        if ($criteria->code) {
            $qb->andWhere('LOWER(a.code) LIKE :codePattern')
                ->setParameter('codePattern', '%' . strtolower($criteria->code) . '%');
        }

        if ($criteria->buildingCode) {
            $qb->innerJoin('a.building', 'b')
                ->andWhere('b.code = :buildingCode')
                ->setParameter('buildingCode', $criteria->buildingCode);
        }

        if ($criteria->owningGroupIds) {
            $qb->innerJoin('a.owningGroups', 'og')
                ->andWhere('og.id IN (:owningGroups)')
                ->setParameter('owningGroups', $criteria->owningGroupIds);
        }

        if ($criteria->memberIds) {
            $qb->innerJoin('a.members', 'm')
                ->andWhere('m.id IN (:members)')
                ->setParameter('members', $criteria->memberIds);
        }

        if ($criteria->adminIds) {
            $qb->innerJoin('a.admins', 'ad')
                ->andWhere('ad.id IN (:admins)')
                ->setParameter('admins', $criteria->adminIds);
        }

        return $qb->getQuery()->getResult();
    }

    public function unlockRoom(Room $room): void
    {
        $room->setLockState(Room::LOCK_STATE_UNLOCKED);
        $this->em->flush();
    }

    public function lockRoom(Room $room): void
    {
        $room->setLockState(Room::LOCK_STATE_LOCKED);
        $this->em->flush();
    }

    /**
     * @throws Exception
     */
    public function addRoomMembers(?array $members, Room $room): void
    {
        if ($members === null) {
            return;
        }
        foreach ($members as $memberId) {
            if (is_numeric($memberId)) {
                $member = $this->appUserRepository->find($memberId);
                if ($member) {
                    $room->addMember($member);
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
    public function addRoomAdmins(?array $admins, Room $room): void
    {
        if ($admins === null) {
            return;
        }
        foreach ($admins as $adminId) {
            if (is_numeric($adminId)) {
                $admin = $this->appUserRepository->find($adminId);
                if ($admin) {
                    $room->addAdmin($admin);
                    $room->addMember($admin);
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
    public function addOwningGroups(?array $owningGroups, Room $room): void
    {
        if (!$owningGroups) {
            return;
        }
        foreach ($owningGroups as $groupId) {
            if (is_numeric($groupId)) {
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
}
