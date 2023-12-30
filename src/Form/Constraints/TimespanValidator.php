<?php

namespace App\Form\Constraints;

use App\Form\Model\ReservationTypeModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class TimespanValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $value ReservationTypeModel */
        /* @var $constraint Timespan */
        if(!$constraint instanceof Timespan) {
            throw new UnexpectedTypeException($constraint, Timespan::class);
        }

        if($value->startDatetime === null || $value->endDatetime === null) {
            return;
        }

        if($value->startDatetime >= $value->endDatetime) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}