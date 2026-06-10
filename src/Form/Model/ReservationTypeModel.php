<?php

namespace App\Form\Model;

use App\Entity\AppUser;
use App\Entity\Room;
use App\Form\Constraints\RoomAvailability;
use App\Form\Constraints\Timespan;
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

    // adds custom validation constraints to this class (not to single property)
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Timespan());
        $metadata->addConstraint(new RoomAvailability());
    }
}