<?php

namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class RoomAvailability extends Constraint
{
    public string $message = 'The room is not available, because it is already booked for this timespan. Look into reservation list for more suitable time.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}