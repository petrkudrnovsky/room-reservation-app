<?php

namespace App\Form\Model;

use App\Entity\AppUser;
use Symfony\Component\Validator\Constraints as Assert;

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

        return $model;
    }
}