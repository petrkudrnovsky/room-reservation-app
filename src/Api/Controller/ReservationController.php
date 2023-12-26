<?php

namespace App\Api\Controller;

use App\Api\Model\ReservationInput;
use App\Api\Model\ReservationOutput;
use App\Entity\Reservation;
use App\Repository\RoomRepository;
use App\Service\ReservationManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use App\Repository\AppUserRepository;
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
        private readonly AppUserRepository $userRepository,
        private readonly RoomRepository $roomRepository,
    ) {}

    #[Rest\Get('/reservation', name: 'api_reservations_list')]
    #[Rest\View(serializerGroups: ['reservation:read'])]
    public function list(Request $request): array
    {
        $title = $request->query->get('title');
        $description = $request->query->get('description');

        $reservations = array_map(
            fn (Reservation $entity) => ReservationOutput::fromEntity($entity),
            $this->reservationManager->findReservationsByFilters($title, $description)
        );

        return ['reservations' => $reservations];
    }

    #[Rest\Get('/reservation/{id}', name: 'api_reservations_get')]
    #[Rest\View(serializerGroups: ['reservation:read'])]
    public function detail(int $id): ReservationOutput
    {
        $reservation = $this->reservationManager->findById($id);
        if (!$reservation) {
            throw new HttpException(404, 'Reservation not found');
        }

        return ReservationOutput::fromEntity($reservation);
    }
}