<?php

namespace App\Service;

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
}