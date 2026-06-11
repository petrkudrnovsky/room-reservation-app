<?php

namespace App\Form\Model;

use App\Entity\AppUser;
use App\Entity\Room;
use App\Form\Constraints\ReservationConstraintsTrait;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationTypeModel
{
    use ReservationConstraintsTrait;
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

}