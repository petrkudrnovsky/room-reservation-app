<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column]
    private ?bool $isPrivate = null;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'rooms')]
    private Collection $owningGroups;

    #[ORM\ManyToMany(targetEntity: AppUser::class, inversedBy: 'memberRooms')]
    #[ORM\JoinTable(name: 'appRoom_member_appUser')]
    private Collection $members;

    #[ORM\ManyToMany(targetEntity: AppUser::class, inversedBy: 'adminRooms')]
    #[ORM\JoinTable(name: 'appRoom_admin_appUser')]
    private Collection $admins;

    public function __construct()
    {
        $this->owningGroups = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->admins = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function isIsPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getOwningGroups(): Collection
    {
        return $this->owningGroups;
    }

    public function addOwningGroup(Group $owningGroup): static
    {
        if (!$this->owningGroups->contains($owningGroup)) {
            $this->owningGroups->add($owningGroup);
        }

        return $this;
    }

    public function removeOwningGroup(Group $owningGroup): static
    {
        $this->owningGroups->removeElement($owningGroup);

        return $this;
    }

    /**
     * @return Collection<int, AppUser>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(AppUser $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(AppUser $member): static
    {
        $this->members->removeElement($member);

        return $this;
    }

    /**
     * @return Collection<int, AppUser>
     */
    public function getAdmins(): Collection
    {
        return $this->admins;
    }

    public function addAdmin(AppUser $admin): static
    {
        if (!$this->admins->contains($admin)) {
            $this->admins->add($admin);
        }

        return $this;
    }

    public function removeAdmin(AppUser $admin): static
    {
        $this->admins->removeElement($admin);

        return $this;
    }
}
