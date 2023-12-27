<?php

namespace App\Voter;

use App\Entity\AppUser;
use App\Entity\Room;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoomVoter extends Voter
{
    const VIEW_INDEX = 'room_index_view';
    const VIEW_DETAIL = 'room_detail_view';
    const CREATE = 'room_create';
    const EDIT = 'room_edit';
    const DELETE = 'room_delete';
    const EDIT_MEMBERS = 'room_edit_members';
    const EDIT_ADMINS = 'room_edit_admins';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW_INDEX, self::VIEW_DETAIL, self::CREATE, self::EDIT, self::DELETE, self::EDIT_MEMBERS, self::EDIT_ADMINS]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if($subject instanceof Room && $attribute === self::VIEW_DETAIL && $subject->isIsPrivate() === false) {
            return true;
        }

        $currentUser = $token->getUser();
        if(!$currentUser instanceof AppUser) {
            return false;
        }

        if(!($subject instanceof Room) && $attribute !== self::VIEW_INDEX && $attribute !== self::CREATE) {
            return false;
        }

        if($attribute === self::VIEW_INDEX) {
            return $this->canViewIndex($currentUser);
        }
        
        if($attribute === self::CREATE) {
            return $this->canCreate($currentUser);
        }
        
        /** @var Room $accessedRoom */
        $accessedRoom = $subject;
        return match($attribute) {
            self::VIEW_DETAIL => $this->canViewDetail($currentUser, $accessedRoom),
            self::EDIT => $this->canEdit($currentUser, $accessedRoom),
            self::DELETE => $this->canDelete($currentUser),
            self::EDIT_MEMBERS => $this->canEditMembers($currentUser, $accessedRoom),
            self::EDIT_ADMINS => $this->canEditAdmins($currentUser, $accessedRoom),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };
    }

    private function canViewIndex(AppUser $currentUser): bool
    {
        return in_array('ROLE_USER', $currentUser->getRoles());
    }

    private function canCreate(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    private function canViewDetail(AppUser $currentUser, Room $accessedRoom): bool
    {
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedRoom->isIsPrivate() === false ||
            $accessedRoom->getAdmins()->contains($currentUser) ||
            $accessedRoom->getMembers()->contains($currentUser)
            ) {
            return true;
        }

        $owningGroups = $accessedRoom->getOwningGroups();
        if($owningGroups->count() > 0) {
            foreach($owningGroups as $owningGroup) {
                if($currentUser->getMemberGroups()->contains($owningGroup) || $currentUser->getAdminGroups()->contains($owningGroup)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canEdit(AppUser $currentUser, Room $accessedRoom): bool
    {
        if(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedRoom->getAdmins()->contains($currentUser)) {
            return true;
        }

        $owningGroups = $accessedRoom->getOwningGroups();
        if($owningGroups->count() > 0) {
            foreach($owningGroups as $owningGroup) {
                if($currentUser->getAdminGroups()->contains($owningGroup)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canDelete(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    // members can be edited by room admins, group (owning the room) admins and super admins
    private function canEditMembers(AppUser $currentUser, Room $accessedRoom): bool
    {
        if(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedRoom->getAdmins()->contains($currentUser)) {
            return true;
        }

        $owningGroups = $accessedRoom->getOwningGroups();
        if($owningGroups->count() > 0) {
            foreach($owningGroups as $owningGroup) {
                if($currentUser->getAdminGroups()->contains($owningGroup)) {
                    return true;
                }
            }
        }

        return false;
    }

    // room admins can be edited by group (owning the room) admins and super admins
    private function canEditAdmins(AppUser $currentUser, Room $accessedRoom): bool
    {
        if(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            return true;
        }

        $owningGroups = $accessedRoom->getOwningGroups();
        if($owningGroups->count() > 0) {
            foreach($owningGroups as $owningGroup) {
                if($currentUser->getAdminGroups()->contains($owningGroup)) {
                    return true;
                }
            }
        }

        return false;
    }
}