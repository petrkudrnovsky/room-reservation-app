<?php

namespace App\Api\Controller;

use App\Api\Model\AppUserInput;
use App\Api\Model\ReservationInput;
use App\Api\Model\ReservationOutput;
use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Service\AppUserManager;
use App\Service\ReservationManager;
use App\Service\RoomManager;
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
        private readonly ReservationManager $reservationManager,
        private readonly RoomManager $roomManager,
        private readonly AppUserManager $appUserManager,
    ) {}

    #[Rest\Get('/reservation', name: 'api_reservations_list')]
    #[Rest\View(statusCode: 200)]
    public function list(Request $request): array
    {
        $this->denyAccessUnlessGranted(ReservationVoter::VIEW_INDEX_ALL);

        $filter = [
            'title' => $request->query->get('title'),
            'status' => $request->query->get('status'),
            'room' => $request->query->get('room'),
            'reservedFor' => $request->query->get('reserved_for'),
            'visitors' => $request->query->get('visitors'),
        ];

        $reservations = array_map(
            fn (Reservation $entity) => ReservationOutput::fromEntity(
                $entity,
                $this->generateUrlIfNotNull($entity->getRoom(), 'api_rooms_detail'),
                $this->generateUrlIfNotNull($entity->getApprovedBy(), 'api_app_users_detail'),
                $this->generateUrlIfNotNull($entity->getReservedFor(), 'api_app_users_detail'),
                $this->getVisitorsUrls($entity),
            ),
            $this->reservationManager->findReservationsByFilters($filter)
        );

        return ['reservations' => $reservations];
    }

    #[Rest\Get('/reservation/{id}', name: 'api_reservations_detail')]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::VIEW_DETAIL, $reservation);

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrlIfNotNull($reservation->getRoom(), 'api_rooms_detail'),
            $this->generateUrlIfNotNull($reservation->getApprovedBy(), 'api_app_users_detail'),
            $this->generateUrlIfNotNull($reservation->getReservedFor(), 'api_app_users_detail'),
            $this->getVisitorsUrls($reservation),
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
            $reservation = new Reservation();
            $reservation->setStatus(Reservation::STATUS_PENDING);
        }

        // convert ReservationInput to Reservation entity before determining access rights, because we need to know the room
        $reservation = $reservationInput->toEntity($this->roomManager, $this->appUserManager, $reservation);

        if($id !== null) {
            $this->denyAccessUnlessGranted(ReservationVoter::EDIT, $reservation);
        }
        else {
            $this->denyAccessUnlessGranted(ReservationVoter::CREATE, $reservation->getRoom());
        }

        $reservation = $this->reservationManager->saveToDatabase($reservation);

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrlIfNotNull($reservation->getRoom(), 'api_rooms_detail'),
            $this->generateUrlIfNotNull($reservation->getApprovedBy(), 'api_app_users_detail'),
            $this->generateUrlIfNotNull($reservation->getReservedFor(), 'api_app_users_detail'),
            $this->getVisitorsUrls($reservation),
        );
    }

    #[Rest\Delete('/reservation/{id}', name: 'api_reservations_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function delete(int $id): void
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::DELETE, $reservation);
        $this->reservationManager->deleteFromDatabase($reservation);
    }

    /**
     * @throws Exception
     */
    #[Rest\Patch('/reservation/{id}/approve', name: 'api_reservations_approve', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function approve(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::CAN_APPROVE, $reservation);

        if($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            throw new HttpException(400, message: 'Reservation is not pending');
        }

        $reservation->setStatus(Reservation::STATUS_APPROVED);
        $reservation->setApprovedBy($this->getUser());
        $reservation = $this->reservationManager->saveToDatabase($reservation);

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrlIfNotNull($reservation->getRoom(), 'api_rooms_detail'),
            $this->generateUrlIfNotNull($reservation->getApprovedBy(), 'api_app_users_detail'),
            $this->generateUrlIfNotNull($reservation->getReservedFor(), 'api_app_users_detail'),
            $this->getVisitorsUrls($reservation),
        );
    }

    /**
     * @throws Exception
     */
    #[Rest\Patch('/reservation/{id}/reject', name: 'api_reservations_reject', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function reject(int $id): ReservationOutput
    {
        $reservation = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(ReservationVoter::CAN_REJECT, $reservation);

        if($reservation->getStatus() !== Reservation::STATUS_PENDING) {
            throw new HttpException(400, message: 'Reservation is not pending');
        }

        $reservation->setStatus(Reservation::STATUS_REJECTED);
        $reservation = $this->reservationManager->saveToDatabase($reservation);

        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrlIfNotNull($reservation->getRoom(), 'api_rooms_detail'),
            $this->generateUrlIfNotNull($reservation->getApprovedBy(), 'api_app_users_detail'),
            $this->generateUrlIfNotNull($reservation->getReservedFor(), 'api_app_users_detail'),
            $this->getVisitorsUrls($reservation),
        );
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
        return ReservationOutput::fromEntity(
            $reservation,
            $this->generateUrlIfNotNull($reservation->getRoom(), 'api_rooms_detail'),
            $this->generateUrlIfNotNull($reservation->getApprovedBy(), 'api_app_users_detail'),
            $this->generateUrlIfNotNull($reservation->getReservedFor(), 'api_app_users_detail'),
            $this->getVisitorsUrls($reservation),
        );
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

    private function generateUrlIfNotNull(?object $entity, string $routeName): ?string {
        if ($entity !== null && method_exists($entity, 'getId')) {
            return $this->generateUrl($routeName, ['id' => $entity->getId()]);
        }
        return null;
    }

    private function getVisitorsUrls(Reservation $reservation): array
    {
        return array_map(
            fn (AppUser $entity) => $this->generateUrl('api_app_users_detail', ['id' => $entity->getId()]),
            $reservation->getVisitors()->toArray()
        );
    }
}