<?php

namespace App\Form\Model;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\Constraints\RoomAvailability;
use App\Form\Constraints\Timespan;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ReservationTypeModel
{
    public ?int $reservationId = null;
    #[Assert\NotBlank]
    public ?string $title = null;
    public ?string $description = null;
    #[Assert\NotBlank]
    #[Assert\GreaterThan('now', message: 'Start date and time of the reservation must be in the future.')]
    public ?\DateTime $startDatetime = null;
    #[Assert\NotBlank]
    public ?\DateTime $endDatetime = null;
    #[Assert\NotBlank]
    public ?Room $room = null;
    public ?AppUser $reservedFor = null;
    public ?Collection $visitors;

    public function toEntity(?Reservation $reservation = null): Reservation
    {
        if(!$reservation) {
            $reservation = new Reservation();
        }
        $reservation->setTitle($this->title);
        $reservation->setDescription($this->description);
        $reservation->setStartDatetime($this->startDatetime);
        $reservation->setEndDatetime($this->endDatetime);
        $reservation->setRoom($this->room);
        $reservation->setReservedFor($this->reservedFor);

        foreach ($reservation->getVisitors() as $visitor) {
            $reservation->removeVisitor($visitor);
        }
        foreach ($this->visitors as $visitor) {
            $reservation->addVisitor($visitor);
        }

        return $reservation;
    }

    public static function fromEntity(Reservation $reservation): self
    {
        $model = new self();
        $model->title = $reservation->getTitle();
        $model->description = $reservation->getDescription();
        $model->startDatetime = $reservation->getStartDatetime();
        $model->endDatetime = $reservation->getEndDatetime();
        $model->room = $reservation->getRoom();
        $model->reservedFor = $reservation->getReservedFor();
        $model->visitors = new ArrayCollection(iterator_to_array($reservation->getVisitors()));
        $model->reservationId = $reservation->getId(); // just for edit form validation (overlapping reservations)

        return $model;
    }

    // adds custom validation constraints to this class (not to single property)
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Timespan());
        $metadata->addConstraint(new RoomAvailability());
    }
}