<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
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

    /*public function getReservationsForUser(AppUser $user): array
    {
        $userReservations = null;
        $reservationRepository->findReservationsByUser($currentUser, $userReservations, $userVisitingReservations);

    }*/

    public function findById(int $id): ?Reservation
    {
        return $this->reservationRepository->find($id);
    }

    public function findReservationsByFilters($filter): array
    {
        $qb = $this->reservationRepository->createQueryBuilder('a');
        $filter['visitors'] = $filter['visitors'] ? explode(',', $filter['visitors']) : [];

        if ($filter['title']) {
            $pattern = '%' . strtolower($filter['title']) . '%';
            $qb->andWhere('LOWER(a.title) LIKE :titlePattern')
                ->setParameter('titlePattern', $pattern);
        }

        if ($filter['status']) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if ($filter['room']) {
            $qb->andWhere('a.room = :room')
                ->setParameter('room', $filter['room']);
        }

        if ($filter['reservedFor']) {
            $qb->andWhere('a.reservedFor = :reservedFor')
                ->setParameter('reservedFor', $filter['reservedFor']);
        }

        if ($filter['visitors']) {
            $qb->innerJoin('a.visitors', 'v')
                ->andWhere('v.id IN (:visitors)')
                ->setParameter('visitors', $filter['visitors']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addReservations(?array $reservations, AppUser $appUser, bool $isApproved): void
    {
        if (!$reservations) {
            return;
        }
        foreach ($reservations as $reservationId) {
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

    public function addReservationsToRoom(?array $reservations, Room $room): void
    {
        if (!$reservations) {
            return;
        }
        foreach ($reservations as $reservationId) {
            if (is_numeric($reservationId)){
                $reservation = $this->reservationRepository->find($reservationId);
                if ($reservation) {
                    $room->addReservation($reservation);
                } else {
                    throw new Exception('Reservation not found');
                }
            } else {
                throw new Exception('Reservation ID must be an integer value');
            }
        }
    }
}