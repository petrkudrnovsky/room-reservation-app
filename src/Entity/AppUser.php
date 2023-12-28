<?php

namespace App\Entity;

use App\Repository\AppUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AppUserRepository::class)]
class AppUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    /**
     * @var ?string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $secondName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'members')]
    private Collection $memberGroups;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'admins')]
    private Collection $adminGroups;

    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'members')]
    private Collection $memberRooms;

    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'admins')]
    private Collection $adminRooms;

    #[ORM\OneToMany(mappedBy: 'approvedBy', targetEntity: Reservation::class)]
    private Collection $approvedReservations;

    #[ORM\OneToMany(mappedBy: 'reservedFor', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\ManyToMany(targetEntity: Reservation::class, mappedBy: 'visitors')]
    private Collection $visitReservations;

    public function __construct()
    {
        $this->memberGroups = new ArrayCollection();
        $this->adminGroups = new ArrayCollection();
        $this->memberRooms = new ArrayCollection();
        $this->adminRooms = new ArrayCollection();
        $this->approvedReservations = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->visitReservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function addRole(string $role): static
    {
        $this->roles[] = $role;
        $this->roles = array_unique($this->roles);
        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_diff($this->roles, [$role]);
        return $this;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getSecondName(): ?string
    {
        return $this->secondName;
    }

    public function setSecondName(string $secondName): static
    {
        $this->secondName = $secondName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getMemberGroups(): Collection
    {
        return $this->memberGroups;
    }

    public function addMemberGroup(Group $group): static
    {
        if (!$this->memberGroups->contains($group)) {
            $this->memberGroups->add($group);
            $group->addMember($this);
        }

        return $this;
    }

    public function removeMemberGroup(Group $group): static
    {
        if ($this->memberGroups->removeElement($group)) {
            $group->removeMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getAdminGroups(): Collection
    {
        return $this->adminGroups;
    }

    public function addAdminGroup(Group $adminGroup): static
    {
        if (!$this->adminGroups->contains($adminGroup)) {
            $this->adminGroups->add($adminGroup);
            $adminGroup->addAdmin($this);
        }

        return $this;
    }

    public function removeAdminGroup(Group $adminGroup): static
    {
        if ($this->adminGroups->removeElement($adminGroup)) {
            $adminGroup->removeAdmin($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getMemberRooms(): Collection
    {
        return $this->memberRooms;
    }

    public function addMemberRoom(Room $memberRoom): static
    {
        if (!$this->memberRooms->contains($memberRoom)) {
            $this->memberRooms->add($memberRoom);
            $memberRoom->addMember($this);
        }

        return $this;
    }

    public function removeMemberRoom(Room $memberRoom): static
    {
        if ($this->memberRooms->removeElement($memberRoom)) {
            $memberRoom->removeMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getAdminRooms(): Collection
    {
        return $this->adminRooms;
    }

    public function addAdminRoom(Room $adminRoom): static
    {
        if (!$this->adminRooms->contains($adminRoom)) {
            $this->adminRooms->add($adminRoom);
            $adminRoom->addAdmin($this);
        }

        return $this;
    }

    public function removeAdminRoom(Room $adminRoom): static
    {
        if ($this->adminRooms->removeElement($adminRoom)) {
            $adminRoom->removeAdmin($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getApprovedReservations(): Collection
    {
        return $this->approvedReservations;
    }

    public function addApprovedReservation(Reservation $approvedReservation): static
    {
        if (!$this->approvedReservations->contains($approvedReservation)) {
            $this->approvedReservations->add($approvedReservation);
            $approvedReservation->setApprovedBy($this);
        }

        return $this;
    }

    public function removeApprovedReservation(Reservation $approvedReservation): static
    {
        if ($this->approvedReservations->removeElement($approvedReservation)) {
            // set the owning side to null (unless already changed)
            if ($approvedReservation->getApprovedBy() === $this) {
                $approvedReservation->setApprovedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setReservedFor($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getReservedFor() === $this) {
                $reservation->setReservedFor(null);
            }
        }

        return $this;
    }

    public function clearMemberGroups(): void
    {
        $this->memberGroups->clear();
    }

    public function clearAdminGroups(): void
    {
        $this->adminGroups->clear();
    }

    public function clearMemberRooms(): void
    {
        $this->memberRooms->clear();
    }

    public function clearAdminRooms(): void
    {
        $this->adminRooms->clear();
    }

    public function clearApprovedReservations(): void
    {
        $this->approvedReservations->clear();
    }

    public function clearReservations(): void
    {
        $this->reservations->clear();
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getVisitReservations(): Collection
    {
        return $this->visitReservations;
    }

    public function addVisitReservation(Reservation $visitReservation): static
    {
        if (!$this->visitReservations->contains($visitReservation)) {
            $this->visitReservations->add($visitReservation);
            $visitReservation->addVisitor($this);
        }

        return $this;
    }

    public function removeVisitReservation(Reservation $visitReservation): static
    {
        if ($this->visitReservations->removeElement($visitReservation)) {
            $visitReservation->removeVisitor($this);
        }

        return $this;
    }
}
