<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationManager
{
    public function __construct(
        public EntityManagerInterface $em,
        public ReservationRepository $reservationRepository,
    ) {}

    public function saveToDatabase(Reservation $reservation): Reservation
    {
        $this->em->persist($reservation);
        $this->em->flush();
        return $reservation;
    }
}