<?php

namespace App\Api\Model;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use Symfony\Component\Serializer\Annotation\Groups;

class ReservationOutput
{
    #[Groups(['reservation:read'])]
    public int $id;
    #[Groups(['reservation:read'])]
    public string $title;
    #[Groups(['reservation:read'])]
    public string $description;
    #[Groups(['reservation:read'])]
    public \DateTime $startDatetime;
    #[Groups(['reservation:read'])]
    public \DateTime $endDatetime;
    #[Groups(['reservation:read'])]
    public string $status;
    #[Groups(['reservation:read'])]
    public ?Room $room;
    #[Groups(['reservation:read'])]
    public ?AppUser $createdBy;
    #[Groups(['reservation:read'])]
    public ?AppUser $approvedBy;
    #[Groups(['reservation:read'])]
    public array $members;

    public function __construct(
        int $id,
        string $title,
        string $description,
        \DateTime $startDatetime,
        \DateTime $endDatetime,
        string $status,
        ?Room $room,
        ?AppUser $createdBy,
        ?AppUser $approvedBy,
        array $members
    )
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->startDatetime = $startDatetime;
        $this->endDatetime = $endDatetime;
        $this->status = $status;
        $this->room = $room;
        $this->createdBy = $createdBy;
        $this->approvedBy = $approvedBy;
        $this->members = $members;
    }

    public static function fromEntity(Reservation $entity): self
    {
        $members = [];
        foreach ($entity->getMembers() as $member) {
            $members[] = AppUserOutput::fromEntity($member);
        }

        return new self(
            $entity->getId(),
            $entity->getTitle(),
            $entity->getDescription(),
            $entity->getStartDatetime(),
            $entity->getEndDatetime(),
            $entity->getStatus(),
            $entity->getRoom(),
            $entity->getCreatedBy(),
            $entity->getApprovedBy(),
            $members
        );
    }

}