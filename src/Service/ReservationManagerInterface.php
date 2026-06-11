<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Filter\ReservationFilterCriteria;

interface ReservationManagerInterface
{
    public function saveToDatabase(Reservation $reservation): Reservation;

    public function deleteFromDatabase(Reservation $reservation): void;

    public function prepareNewReservation(Reservation $reservation): Reservation;

    public function approve(Reservation $reservation, AppUser $approvedBy): Reservation;

    public function reject(Reservation $reservation): Reservation;

    public function findById(int $id): ?Reservation;

    public function findReservationsByFilters(ReservationFilterCriteria $criteria): array;

    public function addApprovedReservations(?array $reservations, AppUser $appUser): void;

    public function addPendingReservations(?array $reservations, AppUser $appUser): void;

    public function addReservationsToRoom(?array $reservations, Room $room): void;

    public function addApprovedReservation(?string $approvedBy, Reservation $reservation): void;

    public function addReservedReservation(?string $reservedFor, Reservation $reservation): void;

    public function hasUserCurrentOrFutureReservations(Room $room, AppUser $user): bool;

    public function getOrderedReservations(Room $room, string|array $status): array;

    public function hasApprovedReservation(Room $room, AppUser $user): bool;

    public function getOngoingReservation(Room $room): ?Reservation;

    public function isRoomFree(Room $room): bool;
}
