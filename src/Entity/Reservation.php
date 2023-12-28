<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $startDatetime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $endDatetime = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    private ?Room $room = null;

    #[ORM\ManyToOne(inversedBy: 'approvedReservations')]
    private ?AppUser $approvedBy = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AppUser $reservedFor = null;

    #[ORM\ManyToMany(targetEntity: AppUser::class, inversedBy: 'visitReservations')]
    private Collection $visitors;

    public function __construct()
    {
        $this->visitors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getStartDatetime(): ?\DateTime
    {
        return $this->startDatetime;
    }

    public function setStartDatetime(\DateTime $startDatetime): static
    {
        $this->startDatetime = $startDatetime;

        return $this;
    }

    public function getEndDatetime(): ?\DateTime
    {
        return $this->endDatetime;
    }

    public function setEndDatetime(\DateTime $endDatetime): static
    {
        $this->endDatetime = $endDatetime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getApprovedBy(): ?AppUser
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?AppUser $approvedBy): static
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getReservedFor(): ?AppUser
    {
        return $this->reservedFor;
    }

    public function setReservedFor(?AppUser $reservedFor): static
    {
        $this->reservedFor = $reservedFor;

        return $this;
    }

    /**
     * @return Collection<int, AppUser>
     */
    public function getVisitors(): Collection
    {
        return $this->visitors;
    }

    public function addVisitor(AppUser $visitor): static
    {
        if (!$this->visitors->contains($visitor)) {
            $this->visitors->add($visitor);
        }

        return $this;
    }

    public function removeVisitor(AppUser $visitor): static
    {
        $this->visitors->removeElement($visitor);

        return $this;
    }
}
