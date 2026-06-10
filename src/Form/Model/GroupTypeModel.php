<?php

namespace App\Form\Model;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class GroupTypeModel
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?Collection $members = null;
    public ?Collection $admins = null;
    public ?Collection $rooms = null;

}