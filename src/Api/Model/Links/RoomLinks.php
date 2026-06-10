<?php

namespace App\Api\Model\Links;

final class RoomLinks
{
    public function __construct(
        public readonly array $members,
        public readonly array $admins,
        public readonly array $owningGroups,
        public readonly array $reservations,
    ) {}
}
