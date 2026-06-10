<?php

namespace App\Form\Mapper;

use App\Entity\Reservation;
use App\Form\Model\ReservationTypeModel;
use Doctrine\Common\Collections\ArrayCollection;

class ReservationTypeMapper
{
    public function toEntity(ReservationTypeModel $model, ?Reservation $reservation = null): Reservation
    {
        if (!$reservation) {
            $reservation = new Reservation();
        }
        $reservation->setTitle($model->title);
        $reservation->setDescription($model->description);
        $reservation->setStartDatetime($model->startDatetime);
        $reservation->setEndDatetime($model->endDatetime);
        $reservation->setRoom($model->room);
        $reservation->setReservedFor($model->reservedFor);

        foreach ($reservation->getVisitors() as $visitor) {
            $reservation->removeVisitor($visitor);
        }
        foreach ($model->visitors as $visitor) {
            $reservation->addVisitor($visitor);
        }

        return $reservation;
    }

    public function fromEntity(Reservation $reservation): ReservationTypeModel
    {
        $model = new ReservationTypeModel();
        $model->title = $reservation->getTitle();
        $model->description = $reservation->getDescription();
        $model->startDatetime = $reservation->getStartDatetime();
        $model->endDatetime = $reservation->getEndDatetime();
        $model->room = $reservation->getRoom();
        $model->reservedFor = $reservation->getReservedFor();
        $model->visitors = new ArrayCollection(iterator_to_array($reservation->getVisitors()));
        $model->reservationId = $reservation->getId(); // just for edit form validation (overlapping reservations)

        return $model;
    }
}
