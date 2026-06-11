<?php

namespace App\Api\Model;

use App\Entity\Reservation;
use App\Form\Constraints\ReservationConstraintsTrait;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationInput
{
    use ReservationConstraintsTrait;
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

}