<?php

namespace App\Form\Model;

use App\Form\Constraints\UniqueUsername;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AppUserTypeModel
{
    public ?int $userId = null;
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Username must have at least 3 characters', maxMessage: 'Username must have maximum of 250 characters')]
    public ?string $username = null;
    #[Assert\NotBlank]
    public ?string $firstName = null;
    #[Assert\NotBlank]
    public ?string $secondName = null;
    #[Assert\Email]
    public ?string $email = null;
    public ?string $phone = null;
    public Collection $memberGroups;
    public Collection $adminGroups;
    public Collection $memberRooms;
    public Collection $adminRooms;
    public bool $isSuperAdmin = false;

    public function __construct()
    {
        $this->memberGroups = new ArrayCollection();
        $this->adminGroups = new ArrayCollection();
        $this->memberRooms = new ArrayCollection();
        $this->adminRooms = new ArrayCollection();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueUsername());
    }
}