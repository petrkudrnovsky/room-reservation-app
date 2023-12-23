<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

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
}