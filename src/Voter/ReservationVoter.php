<?php

namespace App\Voter;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReservationVoter extends Voter
{
    const VIEW_INDEX_ALL = 'reservation_index_view_all';
    const VIEW_INDEX = 'reservation_index_view';
    const VIEW_DETAIL = 'reservation_detail_view';
    const CREATE = 'reservation_create';
    const EDIT = 'reservation_edit';
    const DELETE = 'reservation_delete';
    const CAN_APPROVE = 'reservation_can_approve';
    const CAN_REJECT = 'reservation_can_reject';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW_INDEX_ALL, self::VIEW_INDEX, self::VIEW_DETAIL, self::CREATE, self::EDIT, self::DELETE, self::CAN_APPROVE, self::CAN_REJECT]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if(!$currentUser instanceof AppUser) {
            return false;
        }

        // reservation creation is determined by room access permissions
        if($subject instanceof Room && $attribute === self::CREATE) {
            return $this->canCreate($currentUser, $subject);
        }

        if($attribute === self::VIEW_INDEX_ALL) {
            return $this->canViewIndexAll($currentUser);
        }

        if(!($subject instanceof Reservation) && $attribute !== self::VIEW_INDEX && $attribute !== self::CREATE) {
            return false;
        }

        /*if($attribute === self::VIEW_INDEX) {
            return $this->canViewIndex($currentUser, $subject);
        }*/

        /** @var Reservation $accessedReservation */
        $accessedReservation = $subject;
        return match($attribute) {
            self::VIEW_DETAIL => $this->canViewDetail($currentUser, $accessedReservation),
            self::EDIT => $this->canEdit($currentUser, $accessedReservation),
            self::DELETE => $this->canDelete($currentUser, $accessedReservation),
            self::CAN_APPROVE => $this->canApprove($currentUser, $accessedReservation),
            self::CAN_REJECT => $this->canReject($currentUser, $accessedReservation),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };
    }

    private function canViewIndexAll(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    /*private function canViewIndex(AppUser $currentUser, Reservation $accessedReservation): bool
    {

    }*/

    private function canCreate(AppUser $currentUser, Room $room): bool
    {
        if($currentUser->getRoles() === ['ROLE_SUPER_ADMIN']) {
            return true;
        }
        if($room->getMembers()->contains($currentUser) || $room->getAdmins()->contains($currentUser)) {
            return true;
        }
        $owningRooms = $room->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($owningRoom->getMembers()->contains($currentUser) || $owningRoom->getAdmins()->contains($currentUser)) {
                    return true;
                }
            }
        }
        return false;
    }

    // determines if the user can view the full details of a reservation - limited details are shown to all that can view the room
    private function canViewDetail(AppUser $currentUser, Reservation $accessedReservation): bool
    {
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedReservation->getRoom()->getAdmins()->contains($currentUser) ||
            $accessedReservation->getReservedFor() === $currentUser ||
            $accessedReservation->getVisitors()->contains($currentUser)
        ) {
            return true;
        }

        $owningRooms = $accessedReservation->getRoom()->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($currentUser->getAdminGroups()->contains($owningRoom)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canEdit(AppUser $currentUser, Reservation $accessedReservation): bool
    {
        // only pending reservations can be edited by the user that are reserved for
        if($accessedReservation->getStatus() === Reservation::STATUS_PENDING && $accessedReservation->getReservedFor() === $currentUser) {
            return true;
        }
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedReservation->getRoom()->getAdmins()->contains($currentUser)
        ) {
            return true;
        }

        $owningRooms = $accessedReservation->getRoom()->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($currentUser->getAdminGroups()->contains($owningRoom)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canDelete(AppUser $currentUser, Reservation $accessedReservation): bool
    {
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedReservation->getRoom()->getAdmins()->contains($currentUser) ||
            $accessedReservation->getReservedFor() === $currentUser
        ) {
            return true;
        }

        $owningRooms = $accessedReservation->getRoom()->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($currentUser->getAdminGroups()->contains($owningRoom)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canApprove(AppUser $currentUser, Reservation $accessedReservation): bool
    {
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedReservation->getRoom()->getAdmins()->contains($currentUser)
        ) {
            return true;
        }

        $owningRooms = $accessedReservation->getRoom()->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($currentUser->getAdminGroups()->contains($owningRoom)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canReject(AppUser $currentUser, Reservation $accessedReservation): bool
    {
        if(
            in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
            $accessedReservation->getRoom()->getAdmins()->contains($currentUser)
        ) {
            return true;
        }

        $owningRooms = $accessedReservation->getRoom()->getOwningGroups();
        if($owningRooms->count() > 0) {
            foreach($owningRooms as $owningRoom) {
                if($currentUser->getAdminGroups()->contains($owningRoom)) {
                    return true;
                }
            }
        }

        return false;
    }
}