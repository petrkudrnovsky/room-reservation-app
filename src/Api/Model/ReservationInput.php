<?php

namespace App\Api\Model;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\AppUserRepository;
use App\Repository\RoomRepository;

class ReservationInput
{
    public ?string $title = null;
    public ?string $description = null;
    public ?\DateTime $startDatetime = null;
    public ?\DateTime $endDatetime = null;
    public ?int $roomId = null;
    public ?int $createdById = null;
    public ?int $approvedById = null;
    public ?array $membersIds = null;


    public function toEntity(
        ?AppUserRepository $userRepository = null,
        ?RoomRepository $roomRepository = null,
        Reservation $reservation = new Reservation()): Reservation
    {
        return $reservation;
    }

}