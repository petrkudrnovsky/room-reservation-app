<?php

namespace App\Api\Service;

use App\Api\Model\Links\AppUserLinks;
use App\Api\Model\Links\GroupLinks;
use App\Api\Model\Links\ReservationLinks;
use App\Api\Model\Links\RoomLinks;
use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EntityLinksFactory
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
    ) {}

    public function forAppUser(AppUser $appUser): AppUserLinks
    {
        return new AppUserLinks(
            memberGroups: array_map(
                fn (Group $g) => $this->router->generate('api_groups_detail', ['id' => $g->getId()]),
                $appUser->getMemberGroups()->toArray()
            ),
            adminGroups: array_map(
                fn (Group $g) => $this->router->generate('api_groups_detail', ['id' => $g->getId()]),
                $appUser->getAdminGroups()->toArray()
            ),
            memberRooms: array_map(
                fn (Room $r) => $this->router->generate('api_rooms_detail', ['id' => $r->getId()]),
                $appUser->getMemberRooms()->toArray()
            ),
            adminRooms: array_map(
                fn (Room $r) => $this->router->generate('api_rooms_detail', ['id' => $r->getId()]),
                $appUser->getAdminRooms()->toArray()
            ),
            approvedReservations: array_map(
                fn (Reservation $res) => $this->router->generate('api_reservations_detail', ['id' => $res->getId()]),
                $appUser->getApprovedReservations()->toArray()
            ),
            reservations: array_map(
                fn (Reservation $res) => $this->router->generate('api_reservations_detail', ['id' => $res->getId()]),
                $appUser->getReservations()->toArray()
            ),
        );
    }

    public function forGroup(Group $group): GroupLinks
    {
        return new GroupLinks(
            members: array_map(
                fn (AppUser $u) => $this->router->generate('api_app_users_detail', ['id' => $u->getId()]),
                $group->getMembers()->toArray()
            ),
            admins: array_map(
                fn (AppUser $u) => $this->router->generate('api_app_users_detail', ['id' => $u->getId()]),
                $group->getAdmins()->toArray()
            ),
            rooms: array_map(
                fn (Room $r) => $this->router->generate('api_rooms_detail', ['id' => $r->getId()]),
                $group->getRooms()->toArray()
            ),
        );
    }

    public function forRoom(Room $room): RoomLinks
    {
        return new RoomLinks(
            members: array_map(
                fn (AppUser $u) => $this->router->generate('api_app_users_detail', ['id' => $u->getId()]),
                $room->getMembers()->toArray()
            ),
            admins: array_map(
                fn (AppUser $u) => $this->router->generate('api_app_users_detail', ['id' => $u->getId()]),
                $room->getAdmins()->toArray()
            ),
            owningGroups: array_map(
                fn (Group $g) => $this->router->generate('api_groups_detail', ['id' => $g->getId()]),
                $room->getOwningGroups()->toArray()
            ),
            reservations: array_map(
                fn (Reservation $res) => $this->router->generate('api_reservations_detail', ['id' => $res->getId()]),
                $room->getReservations()->toArray()
            ),
        );
    }

    public function forReservation(Reservation $reservation): ReservationLinks
    {
        return new ReservationLinks(
            roomUrl: $reservation->getRoom() !== null
                ? $this->router->generate('api_rooms_detail', ['id' => $reservation->getRoom()->getId()])
                : null,
            approvedByUrl: $reservation->getApprovedBy() !== null
                ? $this->router->generate('api_app_users_detail', ['id' => $reservation->getApprovedBy()->getId()])
                : null,
            reservedForUrl: $reservation->getReservedFor() !== null
                ? $this->router->generate('api_app_users_detail', ['id' => $reservation->getReservedFor()->getId()])
                : null,
            visitorsUrls: array_map(
                fn (AppUser $u) => $this->router->generate('api_app_users_detail', ['id' => $u->getId()]),
                $reservation->getVisitors()->toArray()
            ),
        );
    }
}
