<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Room;
use App\Voter\RoomVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RoomAccessService implements RoomAccessServiceInterface
{
    public function __construct(
        private readonly ReservationManagerInterface $reservationManager,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public function computeAccess(Room $room, ?AppUser $user): bool
    {
        if ($room->getLockState() === Room::LOCK_STATE_LOCKED) {
            return false;
        }

        $ongoingReservation = $this->reservationManager->getOngoingReservation($room);
        if (!$ongoingReservation) {
            return $this->authorizationChecker->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
                || $room->getMembers()->contains($user)
                || $room->getOwningGroups()->exists(fn (int $key, $group) => $group->getMembers()->contains($user));
        }

        return $this->authorizationChecker->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
            || $ongoingReservation->getReservedFor() === $user
            || $ongoingReservation->getVisitors()->contains($user);
    }
}
