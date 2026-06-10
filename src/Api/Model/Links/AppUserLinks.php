<?php

namespace App\Api\Model\Links;

final class AppUserLinks
{
    public function __construct(
        public readonly array $memberGroups,
        public readonly array $adminGroups,
        public readonly array $memberRooms,
        public readonly array $adminRooms,
        public readonly array $approvedReservations,
        public readonly array $reservations,
    ) {}
}
