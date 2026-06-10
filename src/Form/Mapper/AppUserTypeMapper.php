<?php

namespace App\Form\Mapper;

use App\Entity\AppUser;
use App\Form\Model\AppUserTypeModel;
use Doctrine\Common\Collections\ArrayCollection;

class AppUserTypeMapper
{
    public function toEntity(AppUserTypeModel $model, ?AppUser $appUser = null): AppUser
    {
        if (!$appUser) {
            $appUser = new AppUser();
        }
        $appUser->setFirstName($model->firstName);
        $appUser->setSecondName($model->secondName);
        $appUser->setUsername($model->username);
        $appUser->setEmail($model->email);
        $appUser->setPhone($model->phone);

        foreach ($appUser->getMemberGroups() as $memberGroup) {
            $appUser->removeMemberGroup($memberGroup);
        }
        foreach ($model->memberGroups as $memberGroup) {
            $appUser->addMemberGroup($memberGroup);
        }

        foreach ($appUser->getAdminGroups() as $adminGroup) {
            $appUser->removeAdminGroup($adminGroup);
        }
        foreach ($model->adminGroups as $adminGroup) {
            $appUser->addAdminGroup($adminGroup);
            $appUser->addMemberGroup($adminGroup);
        }

        foreach ($appUser->getMemberRooms() as $memberRoom) {
            $appUser->removeMemberRoom($memberRoom);
        }
        foreach ($model->memberRooms as $memberRoom) {
            $appUser->addMemberRoom($memberRoom);
        }

        foreach ($appUser->getAdminRooms() as $adminRoom) {
            $appUser->removeAdminRoom($adminRoom);
        }
        foreach ($model->adminRooms as $adminRoom) {
            $appUser->addAdminRoom($adminRoom);
            $appUser->addMemberRoom($adminRoom);
        }

        $appUser->addRole('ROLE_USER');
        if ($model->isSuperAdmin) {
            $appUser->addRole('ROLE_SUPER_ADMIN');
        } else {
            $appUser->removeRole('ROLE_SUPER_ADMIN');
        }

        return $appUser;
    }

    public function fromEntity(AppUser $appUser): AppUserTypeModel
    {
        $model = new AppUserTypeModel();
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
        $model->userId = $appUser->getId();

        return $model;
    }
}
