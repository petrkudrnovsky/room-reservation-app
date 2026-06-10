<?php

namespace App\Form\Model;

use App\Entity\Building;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class RoomTypeModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 250, minMessage: 'Room name must have at least 1 character', maxMessage: 'Room name must have maximum of 250 characters')]
    public ?string $name = null;
    #[Assert\NotBlank]
    public ?string $code = null;
    public ?bool $isPrivate = true;
    public ?Collection $owningGroups = null;
    public ?Collection $members = null;
    public ?Collection $admins = null;
    #[Assert\NotBlank]
    public ?Building $building = null;

}