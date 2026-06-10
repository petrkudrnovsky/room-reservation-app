<?php

namespace App\Api\Model;

use App\Entity\Room;
use Symfony\Component\Validator\Constraints as Assert;

class RoomInput
{
    public ?int $id;
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 250, minMessage: 'Room name must have at least 1 character', maxMessage: 'Room name must have maximum of 250 characters')]
    public ?string $name = null;
    #[Assert\NotBlank]
    public ?string $code = null;
    public ?bool $isPrivate = true;
    public ?array $owningGroups = null;
    public ?array $members = null;
    public ?array $admins = null;
    public ?int $buildingId = null;

}