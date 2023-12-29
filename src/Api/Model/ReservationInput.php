<?php

namespace App\Api\Model;

use App\Entity\Reservation;
use App\Form\Constraints\RoomAvailability;
use App\Form\Constraints\Timespan;
use App\Service\AppUserManager;
use App\Service\RoomManager;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ReservationInput
{
    #[Assert\NotBlank(message: 'Reservation title cannot be blank')]
    public ?string $title = null;
    public ?string $description = null;
    #[Assert\NotBlank(message: 'Start date and time of the reservation cannot be blank')]
    #[Assert\GreaterThan('now', message: 'Start date and time of the reservation must be in the future.')]
    public ?\DateTime $startDatetime = null;
    #[Assert\NotBlank(message: 'End date and time of the reservation cannot be blank')]
    public ?\DateTime $endDatetime = null;
    #[Assert\NotBlank(message: 'Reservation must be reserved for some room')]
    public ?int $room = null;
    public ?int $approvedBy = null;
    #[Assert\NotBlank(message: 'Reservation must be reserved for someone')]
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

        $reservation->setRoom($roomManager->findById($this->room));

        $appUserManager->addApprovedReservation($this->approvedBy, $reservation);
        $appUserManager->addReservedReservation($this->reservedFor, $reservation);

        return $reservation;
    }

    // adds custom validation constraints to this class (not to single property)
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Timespan());
        $metadata->addConstraint(new RoomAvailability());
    }
}