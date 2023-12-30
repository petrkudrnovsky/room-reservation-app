<?php

namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueUsername extends Constraint
{
    public string $message = 'This username is already taken';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}