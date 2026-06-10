<?php

namespace App\Entity;

use App\Repository\AccessLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessLogRepository::class)]
class AccessLog
{
    public const DECISION_GRANTED = 'granted';
    public const DECISION_DENIED = 'denied';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Room $room;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AppUser $user;

    #[ORM\Column(length: 10)]
    private string $decision;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $accessedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getUser(): ?AppUser
    {
        return $this->user;
    }

    public function setUser(?AppUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;

        return $this;
    }

    public function getAccessedAt(): \DateTime
    {
        return $this->accessedAt;
    }

    public function setAccessedAt(\DateTime $accessedAt): static
    {
        $this->accessedAt = $accessedAt;

        return $this;
    }
}
