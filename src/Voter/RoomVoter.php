<?php

namespace App\Voter;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Service\RoomManager;
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
    const CAN_VIEW_FULL_RESERVATIONS = 'room_can_view_full_reservations';
    const CAN_VIEW_PENDING_RESERVATIONS = 'room_can_view_pending_reservations';
    const HAS_FULL_ACCESS_TO_ROOM = 'room_has_full_access_to_room';
    const CAN_TOGGLE_LOCK = 'room_can_lock';

    public function __construct(
        private RoomManager $roomManager
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW_INDEX, self::VIEW_DETAIL, self::CREATE, self::EDIT, self::DELETE, self::EDIT_MEMBERS, self::EDIT_ADMINS, self::CAN_VIEW_FULL_RESERVATIONS, self::CAN_VIEW_PENDING_RESERVATIONS, self::HAS_FULL_ACCESS_TO_ROOM, self::CAN_TOGGLE_LOCK]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if($subject instanceof Room && $attribute === self::VIEW_DETAIL && $subject->isIsPrivate() === false) {
            return true;
        }

        // room index is visible to all users
        if($attribute === self::VIEW_INDEX) {
            return true;
        }

        $currentUser = $token->getUser();
        if(!$currentUser instanceof AppUser) {
            return false;
        }

        if(!($subject instanceof Room) && $attribute !== self::CREATE) {
            return false;
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
            self::CAN_VIEW_FULL_RESERVATIONS => $this->canViewFullReservations($currentUser, $accessedRoom),
            self::CAN_VIEW_PENDING_RESERVATIONS => $this->canViewPendingReservations($currentUser, $accessedRoom),
            self::HAS_FULL_ACCESS_TO_ROOM => $this->hasFullAccessToRoom($currentUser, $accessedRoom),
            self::CAN_TOGGLE_LOCK => $this->canLock($currentUser, $accessedRoom),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };
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
            $accessedRoom->getMembers()->contains($currentUser) ||
            $this->roomManager->hasUserCurrentOrFutureReservations($accessedRoom, $currentUser)
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

    private function canViewFullReservations(AppUser $currentUser, Room $accessedRoom): bool
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

    private function canViewPendingReservations(AppUser $currentUser, Room $accessedRoom): bool
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

        $pendingReservations = $accessedRoom->getReservations()->filter(function($reservation) {
            return $reservation->getStatus() === Reservation::STATUS_PENDING;
        });
        if($pendingReservations->count() > 0) {
            foreach($pendingReservations as $pendingReservation) {
                if($pendingReservation->getReservedFor() === $currentUser) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasFullAccessToRoom(AppUser $currentUser, Room $accessedRoom): bool
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

    private function canLock(AppUser $currentUser, Room $accessedRoom): bool
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

        $reservations = $accessedRoom->getReservations();
        $today = new \DateTime();
        foreach ($reservations as $reservation) {
            if($reservation->getStatus() === Reservation::STATUS_APPROVED
                && $reservation->getReservedFor() === $currentUser
                && $reservation->getStartDatetime() <= $today
                && $reservation->getEndDatetime() >= $today) {
                return true;
            }
        }

        return false;
    }
}