<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomManager
{
    public function __construct(
        public EntityManagerInterface $em,
        public RoomRepository $roomRepository,
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
        if(!$room) {
            throw new NotFoundHttpException('Room not found');
        }
        return $room;
    }

    public function hasUserCurrentOrFutureReservations(Room $room, AppUser $user): bool
    {
        $reservations = $room->getReservations();
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if ($reservation->getEndDatetime() >= $today && $reservation->getReservedFor() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Room $room
     * @return Room[]
     */
    public function getOrderedReservations(Room $room, string $status): array
    {
        $reservations = $room->getReservations();
        $orderedReservations = [];
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if ($reservation->getEndDatetime() >= $today && $reservation->getStatus() === $status) {
                $orderedReservations[] = $reservation;
            }
        }
        usort($orderedReservations, function ($a, $b) {
            return $a->getStartDatetime() <=> $b->getStartDatetime();
        });
        return $orderedReservations;
    }

    public function findRoomsByFilters(?string $name, ?string $code, ?string $buildingCode, $filter): array
    {
        $qb = $this->roomRepository->createQueryBuilder('a');

        foreach ($filter as $key => $value) {
            if ($value) {
                $filter[$key] = explode(',', $value);
            }
        }

        if ($name) {
            $pattern = '%' . strtolower($name) . '%';
            $qb->andWhere('LOWER(a.name) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        if ($code) {
            $pattern = '%' . strtolower($code) . '%';
            $qb->andWhere('LOWER(a.code) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        if ($buildingCode) {
            $qb->innerJoin('a.building', 'b')
                ->andWhere('b.code = :buildingCode')
                ->setParameter('buildingCode', $buildingCode);
        }

        if ($filter['owningGroups']) {
            $qb->innerJoin('a.owningGroups', 'og')
                ->andWhere('og.id IN (:owningGroups)')
                ->setParameter('owningGroups', $filter['owningGroups']);
        }

        if ($filter['members']) {
            $qb->innerJoin('a.members', 'm')
                ->andWhere('m.id IN (:members)')
                ->setParameter('members', $filter['members']);
        }

        if ($filter['admins']) {
            $qb->innerJoin('a.admins', 'ad')
                ->andWhere('ad.id IN (:admins)')
                ->setParameter('admins', $filter['admins']);
        }

        return $qb->getQuery()->getResult();
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

    /**
     * @throws Exception
     */
    public function addUserRooms(?array $memberRooms, AppUser $appUser, bool $false): void
    {
        if (!$memberRooms) {
            return;
        }
        foreach ($memberRooms as $roomId) {
            $room = $this->roomRepository->find($roomId);
            if ($room) {
                if ($false) {
                    $appUser->addAdminRoom($room);
                } else {
                    $appUser->addMemberRoom($room);
                }
            } else {
                throw new Exception('Room not found');
            }
        }
    }

    public function findById(?int $room): ?Room
    {
        return $this->roomRepository->find($room);
    }


//    public function hasAccessToRoom(Room $room): bool
//    {
//        if ($this->isOccupied($room)) {
//            return false;
//        }
//        return true;
//    }
//
//    public function isOccupied(Room $room): bool
//    {
//        $reservations = $room->getReservations();
//        $today = new \DateTime();
//        foreach ($reservations as $reservation) {
//            if ($reservation->getEndDatetime() >= $today) {
//                return true;
//            }
//        }
//        return false;
//    }

    public function hasApprovedReservation(Room $room, AppUser $user): bool
    {
        $reservations = $room->getReservations();
        foreach ($reservations as $reservation) {
            if ($reservation->getStatus() === Reservation::STATUS_APPROVED && $reservation->getReservedFor() === $user) {
                return true;
            }
        }
        return false;
    }

    public function unlockRoom(Room $room): void
    {
        $room->setIsLocked(false);
        $this->em->flush();
    }

    public function lockRoom(Room $room): void
    {
        $room->setIsLocked(true);
        $this->em->flush();
    }

    public function getOngoingReservation(Room $room): ?Reservation
    {
        $reservations = $room->getReservations();
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if ($reservation->getEndDatetime() >= $today && $reservation->getStatus() === Reservation::STATUS_APPROVED && $reservation->getStartDatetime() <= $today) {
                return $reservation;
            }
        }
        return null;
    }

    public function isRoomFree(Room $room): bool
    {
        $reservations = $room->getReservations();
        if ($reservations->count() === 0) {
            return true;
        }
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if ($reservation->getEndDatetime() >= $today && $reservation->getStatus() === Reservation::STATUS_APPROVED && $reservation->getStartDatetime() <= $today) {
                return true;
            }
        }
        return false;
    }
}