<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
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

    public function findRoomsByFilters(?string $name, ?string $code, ?int $buildingId): array
    {
        $qb = $this->roomRepository->createQueryBuilder('a');

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

        if ($buildingId) {
            $qb->andWhere('a.building = :buildingId')
                ->setParameter('buildingId', $buildingId);
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
            if ($reservation->getStatus() === 'approved' && $reservation->getReservedFor() === $user) {
                return true;
            }
        }
        return false;
    }

    public function isRoomFree(Room $room): bool
    {
        $reservations = $room->getReservations();
        if ($reservations->count() === 0) {
            return true;
        }
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if ($reservation->getEndDatetime() >= $today) {
                return true;
            }
        }
        return false;
    }
}