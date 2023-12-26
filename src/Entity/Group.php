<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'appGroup')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: AppUser::class, inversedBy: 'memberGroups')]
    #[ORM\JoinTable(name: 'appGroup_member_appUser')]
    private Collection $members;

    #[ORM\ManyToMany(targetEntity: AppUser::class, inversedBy: 'adminGroups')]
    #[ORM\JoinTable(name: 'appGroup_admin_appUser')]
    private Collection $admins;

    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'owningGroups')]
    private Collection $rooms;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->admins = new ArrayCollection();
        $this->rooms = new ArrayCollection();
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

    /**
     * @return Collection<int, Room>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
            $room->addOwningGroup($this);
        }

        return $this;
    }

    public function removeRoom(Room $room): static
    {
        if ($this->rooms->removeElement($room)) {
            $room->removeOwningGroup($this);
        }

        return $this;
    }

    public function clearMembers(): void
    {
        $this->members->clear();
    }

    public function clearAdmins(): void
    {
        $this->admins->clear();
    }

    public function clearRooms(): void
    {
        $this->rooms->clear();
    }
}
