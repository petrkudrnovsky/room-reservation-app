<?php

namespace App\Form\Constraints;

use App\Api\Model\ReservationInput;
use App\Form\Model\ReservationTypeModel;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RoomAvailabilityValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $value ReservationTypeModel */
        /* @var $constraint RoomAvailability */

        if(!$constraint instanceof RoomAvailability) {
            throw new UnexpectedTypeException($constraint, RoomAvailability::class);
        }

        if($value instanceof ReservationTypeModel) {
            $overlappingReservations = $this->reservationRepository->findOverlappingReservations(
                $value->room->getId(),
                $value->startDatetime,
                $value->endDatetime,
                $value->reservationId
            );

            if(count($overlappingReservations) > 0) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
        else if($value instanceof ReservationInput) {
            $overlappingReservations = $this->reservationRepository->findOverlappingReservations(
                $value->room,
                $value->startDatetime,
                $value->endDatetime,
                null
            );

            if(count($overlappingReservations) > 0) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}