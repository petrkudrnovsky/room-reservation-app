<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Filter\ReservationFilterCriteria;
use App\Repository\AppUserRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ReservationManager implements ReservationManagerInterface
{
    public function __construct(
        public EntityManagerInterface $em,
        public ReservationRepository $reservationRepository,
        private readonly AppUserRepository $appUserRepository,
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

    /**
     * @throws \DomainException if the reservation is not pending or overlaps an existing approved reservation
     */
    public function approve(Reservation $reservation, AppUser $approvedBy): Reservation
    {
        if ($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            throw new \DomainException('Reservation is not pending.');
        }
        $overlapping = $this->reservationRepository->findOverlappingReservations(
            $reservation->getRoom()->getId(),
            $reservation->getStartDatetime(),
            $reservation->getEndDatetime(),
            null
        );
        if (count($overlapping) > 0) {
            throw new \DomainException('Cannot approve reservation because it overlaps with another reservation.');
        }
        $reservation->setStatus(Reservation::STATUS_APPROVED);
        $reservation->setApprovedBy($approvedBy);
        $reservation->setApprovedAt(new \DateTime());
        return $this->saveToDatabase($reservation);
    }

    /**
     * @throws \DomainException if the reservation is not pending
     */
    public function reject(Reservation $reservation): Reservation
    {
        if ($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            throw new \DomainException('Reservation is not pending.');
        }
        $reservation->setStatus(Reservation::STATUS_REJECTED);
        return $this->saveToDatabase($reservation);
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

    public function findReservationsByFilters(ReservationFilterCriteria $criteria): array
    {
        $qb = $this->reservationRepository->createQueryBuilder('a');
        $visitors = $criteria->visitors ? explode(',', $criteria->visitors) : [];

        if ($criteria->title) {
            $pattern = '%' . strtolower($criteria->title) . '%';
            $qb->andWhere('LOWER(a.title) LIKE :titlePattern')
                ->setParameter('titlePattern', $pattern);
        }

        if ($criteria->status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $criteria->status);
        }

        if ($criteria->room) {
            $qb->andWhere('a.room = :room')
                ->setParameter('room', $criteria->room);
        }

        if ($criteria->reservedFor) {
            $qb->andWhere('a.reservedFor = :reservedFor')
                ->setParameter('reservedFor', $criteria->reservedFor);
        }

        if ($visitors) {
            $qb->innerJoin('a.visitors', 'v')
                ->andWhere('v.id IN (:visitors)')
                ->setParameter('visitors', $visitors);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addApprovedReservations(?array $reservations, AppUser $appUser): void
    {
        if (!$reservations) {
            return;
        }
        foreach ($reservations as $reservationId) {
            if (is_numeric($reservationId)) {
                $reservation = $this->reservationRepository->find($reservationId);
                if ($reservation) {
                    $reservation->setApprovedBy($appUser);
                } else {
                    throw new Exception('Reservation not found');
                }
            } else {
                throw new Exception('Reservation ID must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addPendingReservations(?array $reservations, AppUser $appUser): void
    {
        if (!$reservations) {
            return;
        }
        foreach ($reservations as $reservationId) {
            if (is_numeric($reservationId)) {
                $reservation = $this->reservationRepository->find($reservationId);
                if ($reservation) {
                    $reservation->setReservedFor($appUser);
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

    /**
     * @throws Exception
     */
    public function addApprovedReservation(?string $approvedBy, Reservation $reservation): void
    {
        if ($approvedBy) {
            $appUser = $this->appUserRepository->find($approvedBy);
            if ($appUser) {
                $reservation->setApprovedBy($appUser);
            } else {
                throw new Exception('User not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addReservedReservation(?string $reservedFor, Reservation $reservation): void
    {
        if ($reservedFor) {
            $appUser = $this->appUserRepository->find($reservedFor);
            if ($appUser) {
                $reservation->setReservedFor($appUser);
            } else {
                throw new Exception('User not found');
            }
        }
    }

    public function hasUserCurrentOrFutureReservations(Room $room, AppUser $user): bool
    {
        $today = new \DateTime();
        foreach ($room->getReservations() as $reservation) {
            if ($reservation->getEndDatetime() >= $today && $reservation->getReservedFor() === $user) {
                return true;
            }
        }
        return false;
    }

    public function getOrderedReservations(Room $room, string|array $status): array
    {
        $statuses = is_array($status) ? $status : [$status];
        $orderedReservations = [];
        $today = new \DateTime();
        foreach ($room->getReservations() as $reservation) {
            if ($reservation->getEndDatetime() >= $today && in_array($reservation->getStatus(), $statuses)) {
                $orderedReservations[] = $reservation;
            }
        }
        usort($orderedReservations, fn($a, $b) => $a->getStartDatetime() <=> $b->getStartDatetime());
        return $orderedReservations;
    }

    public function hasApprovedReservation(Room $room, AppUser $user): bool
    {
        foreach ($room->getReservations() as $reservation) {
            if (in_array($reservation->getStatus(), [Reservation::STATUS_APPROVED, Reservation::STATUS_ACTIVE])
                && $reservation->getReservedFor() === $user
            ) {
                return true;
            }
        }
        return false;
    }

    public function getOngoingReservation(Room $room): ?Reservation
    {
        foreach ($room->getReservations() as $reservation) {
            if ($reservation->getStatus() === Reservation::STATUS_ACTIVE) {
                return $reservation;
            }
        }
        return null;
    }

    public function isRoomFree(Room $room): bool
    {
        foreach ($room->getReservations() as $reservation) {
            if ($reservation->getStatus() === Reservation::STATUS_ACTIVE) {
                return false;
            }
        }
        return true;
    }
}