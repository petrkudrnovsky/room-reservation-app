<?php

namespace App\Api\Mapper;

use App\Api\Model\ReservationInput;
use App\Entity\Reservation;
use App\Service\ReservationManagerInterface;
use App\Service\RoomManagerInterface;
use Exception;

class ReservationInputMapper
{
    public function __construct(
        private readonly RoomManagerInterface $roomManager,
        private readonly ReservationManagerInterface $reservationManager,
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

        $this->reservationManager->addApprovedReservation($input->approvedBy, $reservation);
        $this->reservationManager->addReservedReservation($input->reservedFor, $reservation);

        return $reservation;
    }
}
