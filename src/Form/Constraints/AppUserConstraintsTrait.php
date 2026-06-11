<?php

namespace App\Form\Constraints;

use Symfony\Component\Validator\Mapping\ClassMetadata;

trait AppUserConstraintsTrait
{
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new UniqueUsername());
    }
}
