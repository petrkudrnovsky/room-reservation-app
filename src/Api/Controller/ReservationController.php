<?php

namespace App\Api\Controller;

use App\Api\Model\ReservationInput;
use App\Api\Model\ReservationOutput;
use App\Entity\Reservation;
use App\Service\AppUserManager;
use App\Service\ReservationManager;
use App\Service\RoomManager;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ReservationController extends AbstractFOSRestController
{
    public function __construct(
        private readonly ReservationManager $reservationManager,
        private readonly RoomManager $roomManager,
        private readonly AppUserManager $appUserManager,
    ) {}

    #[Rest\Get('/reservation', name: 'api_reservations_list')]
    #[Rest\View]
    public function list(Request $request): array
    {
        $title = $request->query->get('title');
        $description = $request->query->get('description');

        // TODO if the approvedby is empty then it will not generate the url
        $reservations = array_map(
            fn (Reservation $entity) => ReservationOutput::fromEntity(
                $entity,
                $this->generateUrl('api_rooms_detail', ['id' => $entity->getRoom()?->getId()]),
                $this->generateUrl('api_app_users_detail', ['id' => $entity->getApprovedBy()?->getId()]),
                $this->generateUrl('api_app_users_detail', ['id' => $entity->getReservedFor()?->getId()]),
            ),
            $this->reservationManager->findReservationsByFilters($title, $description)
        );

        return ['reservations' => $reservations];
    }

    #[Rest\Get('/reservation/{id}', name: 'api_reservations_detail')]
    #[Rest\View]
    public function detail(int $id): ReservationOutput
    {
        $reservation = $this->reservationManager->findById($id);
        if (!$reservation) {
            throw new HttpException(404, 'Reservation not found');
        }

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrl('api_rooms_detail', ['id' => $reservation->getRoom()?->getId()]),
            $this->generateUrl('api_app_users_detail', ['id' => $reservation->getApprovedBy()?->getId()]),
            $this->generateUrl('api_app_users_detail', ['id' => $reservation->getReservedFor()?->getId()]),
        );
    }

    /**
     * @throws Exception
     */
    #[Rest\Post('/reservation', name: 'api_reservations_create')]
    #[Rest\Put('/reservation/{id}', name: 'api_reservations_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('reservationInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function update(?int $id, ReservationInput $reservationInput, ConstraintViolationListInterface $errors): ReservationOutput
    {
        $reservation = $id !== null ? $this->findOrFail($id) : new Reservation();

        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $reservation = $reservationInput->toEntity($this->roomManager, $this->appUserManager, $reservation);
        $reservation = $this->reservationManager->saveToDatabase($reservation);

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrl('api_rooms_detail', ['id' => $reservation->getRoom()?->getId()]),
            $this->generateUrl('api_app_users_detail', ['id' => $reservation->getApprovedBy()?->getId()]),
            $this->generateUrl('api_app_users_detail', ['id' => $reservation->getReservedFor()?->getId()]),
        );
    }

    private function findOrFail(int $id): Reservation
    {
        $reservation = $this->reservationManager->findById($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        return $reservation;
    }
}