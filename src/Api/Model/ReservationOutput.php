<?php

namespace App\Api\Model;

use App\Entity\Reservation;

class ReservationOutput
{
    public int $id;
    public string $title;
    public string $description;
    public \DateTime $startDatetime;
    public \DateTime $endDatetime;
    public string $status;
    public ?string $roomUrl;
    public ?string $approvedByUrl;
    public ?string $reservedForUrl;

    public function __construct(
        int $id,
        string $title,
        string $description,
        \DateTime $startDatetime,
        \DateTime $endDatetime,
        string $status,
        ?string $roomUrl,
        ?string $approvedByUrl,
        ?string $reservedForUrl,
    )
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->startDatetime = $startDatetime;
        $this->endDatetime = $endDatetime;
        $this->status = $status;
        $this->roomUrl = $roomUrl;
        $this->approvedByUrl = $approvedByUrl;
        $this->reservedForUrl = $reservedForUrl;
    }

    public static function fromEntity(
        Reservation $entity,
        ?string $roomUrl,
        ?string $approvedByUrl,
        ?string $reservedForUrl,
    ): self
    {
        return new self(
            $entity->getId(),
            $entity->getTitle(),
            $entity->getDescription(),
            $entity->getStartDatetime(),
            $entity->getEndDatetime(),
            $entity->getStatus(),
            $roomUrl,
            $approvedByUrl,
            $reservedForUrl,
        );
    }

}