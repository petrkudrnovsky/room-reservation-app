<?php

namespace App\Api\Mapper;

use App\Api\Model\ReservationInput;
use App\Entity\Reservation;
use App\Service\AppUserManager;
use App\Service\RoomManager;
use Exception;

class ReservationInputMapper
{
    public function __construct(
        private readonly RoomManager $roomManager,
        private readonly AppUserManager $appUserManager,
    ) {}

    /**
     * @throws Exception
     */
    public function toEntity(ReservationInput $input, Reservation $reservation): Reservation
    {
        $reservation->setTitle($input->title);
        $reservation->setDescription($input->description);
        $reservation->setStartDatetime($input->startDatetime);
        $reservation->setEndDatetime($input->endDatetime);

        $reservation->setRoom($this->roomManager->findById($input->room));

        $this->appUserManager->addApprovedReservation($input->approvedBy, $reservation);
        $this->appUserManager->addReservedReservation($input->reservedFor, $reservation);

        return $reservation;
    }
}
