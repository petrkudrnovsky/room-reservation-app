<?php

namespace App\Form\Model;

use App\Entity\Reservation;
use App\Entity\Room;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationTypeModel
{
    #[Assert\NotBlank]
    public ?string $title = null;
    public ?string $description = null;
    #[Assert\NotBlank]
    public ?\DateTime $startDatetime = null;
    #[Assert\NotBlank]
    public ?\DateTime $endDatetime = null;
    #[Assert\NotBlank]
    public ?Room $room = null;
    public ?Collection $members = null;

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

        foreach ($this->members as $member) {
            $reservation->addMember($member);
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
        $model->members = $reservation->getMembers();

        return $model;
    }
}