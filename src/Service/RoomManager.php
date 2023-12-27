<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

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
        return $this->roomRepository->find($id);
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
    public function addUserRooms(?array $memberRooms, \App\Entity\AppUser $appUser, bool $false)
    {
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
}