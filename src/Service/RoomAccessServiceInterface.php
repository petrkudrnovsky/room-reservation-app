<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Room;

interface RoomAccessServiceInterface
{
    public function computeAccess(Room $room, ?AppUser $user): bool;
}
