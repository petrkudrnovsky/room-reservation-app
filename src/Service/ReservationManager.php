<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function deleteFromDatabase(Reservation $reservation): void
    {
        $this->em->remove($reservation);
        $this->em->flush();
    }

    public function prepareNewReservation(Reservation $reservation): Reservation
    {
        $reservation->setStatus(Reservation::STATUS_PENDING);
        return $reservation;
    }

    public function findById(int $id): ?Reservation
    {
        return $this->reservationRepository->find($id);
    }

    public function findReservationsByFilters(?string $title, ?string $description): array
    {
        $qb = $this->reservationRepository->createQueryBuilder('a');

        if ($description) {
            $pattern = '%' . strtolower($description) . '%';
            $qb->andWhere('LOWER(a.description) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        if ($title) {
            $pattern = '%' . strtolower($title) . '%';
            $qb->andWhere('LOWER(a.title) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addReservations(?array $approvedReservations, AppUser $appUser, bool $isApproved): void
    {
        foreach ($approvedReservations as $reservationId) {
            if (is_numeric($reservationId)){
                $reservation = $this->reservationRepository->find($reservationId);
                if ($reservation) {
                    if ($isApproved) {
                        $reservation->setApprovedBy($appUser);
                    } else {
                        $reservation->setReservedFor($appUser);
                    }
                } else {
                    throw new Exception('Reservation not found');
                }
            } else {
                throw new Exception('Reservation ID must be an integer value');
            }
        }
    }
}