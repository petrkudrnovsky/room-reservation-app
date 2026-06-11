<?php

namespace App\Form\Constraints;

use Symfony\Component\Validator\Mapping\ClassMetadata;

trait ReservationConstraintsTrait
{
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addConstraint(new Timespan());
        $metadata->addConstraint(new RoomAvailability());
    }
}
