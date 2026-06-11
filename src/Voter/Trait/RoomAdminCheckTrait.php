<?php

namespace App\Voter\Trait;

use App\Entity\AppUser;
use App\Entity\Room;

trait RoomAdminCheckTrait
{
    private function isAdminOfRoom(AppUser $user, Room $room): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ||
            $room->getAdmins()->contains($user)) {
            return true;
        }
        foreach ($room->getOwningGroups() as $group) {
            if ($user->getAdminGroups()->contains($group)) {
                return true;
            }
        }
        return false;
    }

    // Used where direct room admins may NOT perform the action (e.g. editing the admin list itself)
    private function isSuperOrGroupAdminOfRoom(AppUser $user, Room $room): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }
        foreach ($room->getOwningGroups() as $group) {
            if ($user->getAdminGroups()->contains($group)) {
                return true;
            }
        }
        return false;
    }
}
