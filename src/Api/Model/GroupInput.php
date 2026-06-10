<?php

namespace App\Api\Model;

use App\Entity\Group;
use Symfony\Component\Validator\Constraints as Assert;

class GroupInput {
    #[Assert\NotBlank(message: 'Group name cannot be blank')]
    #[Assert\Length(min: 3, max: 250, minMessage: 'Group name must have at least 3 characters', maxMessage: 'Group name must have maximum of 250 characters')]
    public ?string $name = null;
    public ?array $members = null;
    public ?array $admins = null;
    public ?array $rooms = null;

}
