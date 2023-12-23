<?php

namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Timespan extends Constraint
{
    public string $message = 'The end datetime must be after the start datetime';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}