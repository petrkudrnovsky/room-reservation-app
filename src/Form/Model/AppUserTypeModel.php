<?php

namespace App\Form\Model;

use App\Entity\AppUser;
use App\Form\Constraints\UniqueUsername;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AppUserTypeModel
{
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

    public function toEntity(?AppUser $appUser = null): AppUser
    {
        if(!$appUser) {
            $appUser = new AppUser();
        }
        $appUser->setFirstName($this->firstName);
        $appUser->setSecondName($this->secondName);
        $appUser->setUsername($this->username);
        $appUser->setEmail($this->email);
        $appUser->setPhone($this->phone);

        foreach ($appUser->getMemberGroups() as $memberGroup) {
            $appUser->removeMemberGroup($memberGroup);
        }
        foreach ($this->memberGroups as $memberGroup) {
            $appUser->addMemberGroup($memberGroup);
        }

        foreach ($appUser->getAdminGroups() as $adminGroup) {
            $appUser->removeAdminGroup($adminGroup);
        }
        foreach ($this->adminGroups as $adminGroup) {
            $appUser->addAdminGroup($adminGroup);
        }

        foreach ($appUser->getMemberRooms() as $memberRoom) {
            $appUser->removeMemberRoom($memberRoom);
        }
        foreach ($this->memberRooms as $memberRoom) {
            $appUser->addMemberRoom($memberRoom);
        }

        foreach ($appUser->getAdminRooms() as $adminRoom) {
            $appUser->removeAdminRoom($adminRoom);
        }
        foreach ($this->adminRooms as $adminRoom) {
            $appUser->addAdminRoom($adminRoom);
        }

        $appUser->addRole('ROLE_USER');
        if($this->isSuperAdmin) {
            $appUser->addRole('ROLE_SUPER_ADMIN');
        }
        else {
            $appUser->removeRole('ROLE_SUPER_ADMIN');
        }

        return $appUser;
    }

    public static function fromEntity(AppUser $appUser): self
    {
        $model = new self();
        $model->username = $appUser->getUsername();
        $model->firstName = $appUser->getFirstName();
        $model->secondName = $appUser->getSecondName();
        $model->email = $appUser->getEmail();
        $model->phone = $appUser->getPhone();
        $model->memberGroups = new ArrayCollection(iterator_to_array($appUser->getMemberGroups()));
        $model->adminGroups = new ArrayCollection(iterator_to_array($appUser->getAdminGroups()));
        $model->memberRooms = new ArrayCollection(iterator_to_array($appUser->getMemberRooms()));
        $model->adminRooms = new ArrayCollection(iterator_to_array($appUser->getAdminRooms()));
        $model->isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $appUser->getRoles());

        return $model;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueUsername());
    }
}