<?php

namespace App\Form\Model;

use App\Entity\AppUser;

class AppUserTypeModel
{
    public ?string $username = null;
    public ?string $firstName = null;
    public ?string $secondName = null;
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