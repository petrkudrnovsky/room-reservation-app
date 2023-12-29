<?php

namespace App\Api\Model;

use App\Entity\Reservation;
use App\Service\AppUserManager;
use App\Service\RoomManager;
use Exception;

class ReservationInput
{
    public ?string $title = null;
    public ?string $description = null;
    public ?\DateTime $startDatetime = null;
    public ?\DateTime $endDatetime = null;
    public ?string $status = null;
    public ?int $room = null;
    public ?int $approvedBy = null;
    public ?int $reservedFor = null;
    public ?array $visitorsUrls = null;

    /**
     * @throws Exception
     */
    public function toEntity(
        RoomManager $roomManager,
        AppUserManager $appUserManager,
        Reservation $reservation = new Reservation(),
    ): Reservation
    {
        $reservation->setTitle($this->title);
        $reservation->setDescription($this->description);
        $reservation->setStartDatetime($this->startDatetime);
        $reservation->setEndDatetime($this->endDatetime);
        $reservation->setStatus($this->status);

        $reservation->setRoom($roomManager->findById($this->room));

        $appUserManager->addApprovedReservation($this->approvedBy, $reservation);
        $appUserManager->addReservedReservation($this->reservedFor, $reservation);

        return $reservation;
    }

}