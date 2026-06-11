<?php

namespace App\Api\Controller;

use App\Api\Mapper\ReservationInputMapper;
use App\Api\Model\AppUserInput;
use App\Api\Model\ReservationInput;
use App\Api\Model\ReservationOutput;
use App\Api\Service\EntityLinksFactory;
use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Filter\ReservationFilterCriteria;
use App\Service\AppUserManagerInterface;
use App\Service\ReservationManagerInterface;
use App\Voter\ReservationVoter;
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
        private readonly ReservationManagerInterface $reservationManager,
        private readonly AppUserManagerInterface $appUserManager,
        private readonly ReservationInputMapper $reservationInputMapper,
        private readonly EntityLinksFactory $linksFactory,
    ) {}

    #[Rest\Get('/reservation', name: 'api_reservations_list')]
    #[Rest\View(statusCode: 200)]
    public function list(Request $request): array
    {
        $this->denyAccessUnlessGranted(ReservationVoter::VIEW_INDEX_ALL);

        $criteria = new ReservationFilterCriteria(
            title: $request->query->get('title'),
            status: $request->query->get('status'),
            room: $request->query->get('room') ? (int) $request->query->get('room') : null,
            reservedFor: $request->query->get('reserved_for') ? (int) $request->query->get('reserved_for') : null,
            visitors: $request->query->get('visitors'),
        );

        $reservations = array_map(
            fn (Reservation $entity) => ReservationOutput::fromEntity($entity, $this->linksFactory->forReservation($entity)),
            $this->reservationManager->findReservationsByFilters($criteria)
        );

        return ['reservations' => $reservations];
    }

    #[Rest\Get('/reservation/{id}', name: 'api_reservations_detail')]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::VIEW_DETAIL, $reservation);

        return ReservationOutput::fromEntity($reservation, $this->linksFactory->forReservation($reservation));
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
        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        // create new reservation or update existing one
        if ($id !== null) {
            $reservation = $this->findOrFail($id);
        } else {
            $reservation = $this->reservationManager->prepareNewReservation(new Reservation());
        }

        // convert ReservationInput to Reservation entity before determining access rights, because we need to know the room
        $reservation = $this->reservationInputMapper->toEntity($reservationInput, $reservation);

        if($id !== null) {
            $this->denyAccessUnlessGranted(ReservationVoter::EDIT, $reservation);
        }
        else {
            $this->denyAccessUnlessGranted(ReservationVoter::CREATE, $reservation->getRoom());
        }

        $reservation = $this->reservationManager->saveToDatabase($reservation);

        return ReservationOutput::fromEntity($reservation, $this->linksFactory->forReservation($reservation));
    }

    #[Rest\Delete('/reservation/{id}', name: 'api_reservations_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function delete(int $id): void
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::DELETE, $reservation);
        $this->reservationManager->deleteFromDatabase($reservation);
    }

    #[Rest\Patch('/reservation/{id}/approve', name: 'api_reservations_approve', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function approve(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::CAN_APPROVE, $reservation);

        try {
            $reservation = $this->reservationManager->approve($reservation, $this->getUser());
        } catch (\DomainException $e) {
            throw new HttpException(400, $e->getMessage());
        }

        return ReservationOutput::fromEntity($reservation, $this->linksFactory->forReservation($reservation));
    }

    #[Rest\Patch('/reservation/{id}/reject', name: 'api_reservations_reject', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function reject(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::CAN_REJECT, $reservation);

        try {
            $reservation = $this->reservationManager->reject($reservation);
        } catch (\DomainException $e) {
            throw new HttpException(400, $e->getMessage());
        }

        return ReservationOutput::fromEntity($reservation, $this->linksFactory->forReservation($reservation));
    }

    #[Rest\Patch('/reservation/{id}/visitor', name: 'api_reservations_add_visitor', requirements: ['id' => '\d+'])]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 200)]
    public function addVisitor(int $id, AppUserInput $appUserInput): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $user = $this->findOrFailUser($appUserInput->id);
        $this->denyAccessUnlessGranted(ReservationVoter::EDIT, $reservation);

        $reservation->addVisitor($user);
        $reservation = $this->reservationManager->saveToDatabase($reservation);
        return ReservationOutput::fromEntity($reservation, $this->linksFactory->forReservation($reservation));
    }

    #[Rest\Delete('/reservation/{id}/visitor/{visitorId}', name: 'api_reservations_remove_visitor', requirements: ['id' => '\d+', 'visitorId' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function removeVisitor(int $id, int $visitorId): void
    {
        $reservation = $this->findOrFail($id);
        $user = $this->findOrFailUser($visitorId);
        $this->denyAccessUnlessGranted(ReservationVoter::EDIT, $reservation);

        $reservation->removeVisitor($user);
        $this->reservationManager->saveToDatabase($reservation);
    }


    private function findOrFail(int $id): Reservation
    {
        $reservation = $this->reservationManager->findById($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }

        return $reservation;
    }

    private function findOrFailUser(int $id): AppUser
    {
        $user = $this->appUserManager->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $user;
    }
}
