<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Form\Mapper\ReservationTypeMapper;
use App\Form\Model\ReservationTypeModel;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationManagerInterface;
use App\Service\RoomManagerInterface;
use App\Voter\ReservationVoter;
use App\Voter\RoomVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/room/{roomId}/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_room_reservation_index')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] // further access is checked in RoomVoter
    public function index(int $roomId, ReservationRepository $reservationRepository, RoomManagerInterface $roomManager): Response
    {
        $room = $roomManager->getRoomById($roomId);
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_DETAIL, $room);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        $allReservations = null;
        if($this->isGranted(RoomVoter::CAN_VIEW_FULL_RESERVATIONS, $room)) {
            $allReservations = $reservationRepository->findReservationsByRoomId($roomId);
        }

        return $this->render('reservation/index.html.twig', [
            'allReservations' => $allReservations,
            'userReservations' => $currentUser->getReservations()->filter(fn(Reservation $reservation) => $reservation->getRoom() === $room),
            'room' => $room,
        ]);
    }

    #[Route('/new', name: 'app_room_reservation_new')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] // further access is checked in ReservationVoter
    public function new(Request $request, int $roomId, ReservationManagerInterface $reservationManager, RoomManagerInterface $roomManager, ReservationTypeMapper $mapper): Response
    {
        $reservationModel = new ReservationTypeModel();
        $reservationModel->room = $roomManager->getRoomById($roomId);

        $this->denyAccessUnlessGranted(ReservationVoter::CREATE, $reservationModel->room);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        $reservationModel->reservedFor = $currentUser;
        // can can_edit_after_approved be true? - yes, because the user can edit the reservation before it is approved
        $form = $this->createForm(ReservationType::class, $reservationModel, ['can_edit_reservedFor' => $this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $reservationModel->room), 'can_edit_after_approved' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $mapper->toEntity($reservationModel);
            $reservation = $reservationManager->prepareNewReservation($reservation);
            $reservationManager->saveToDatabase($reservation);

            $this->addFlash('success', 'Reservation created successfully.');
            return $this->redirectToRoute('app_room_show', ['id' => $roomId]);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservationModel,
            'form' => $form,
            'room' => $reservationModel->room,
            'approvedReservations' => $reservationManager->getOrderedReservations($reservationModel->room, [Reservation::STATUS_APPROVED, Reservation::STATUS_ACTIVE]),
        ]);
    }

    #[Route('/{id}', name: 'app_room_reservation_show')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')] // limited information will be displayed in the template
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_reservation_edit')]
    #[IsGranted(ReservationVoter::EDIT, 'reservation')]
    public function edit(Request $request, Reservation $reservation, ReservationManagerInterface $reservationManager, ReservationTypeMapper $mapper): Response
    {
        $reservationModel = $mapper->fromEntity($reservation);
        $form = $this->createForm(ReservationType::class, $reservationModel, [
            'can_edit_reservedFor' => $this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $reservation->getRoom()),
            'can_edit_after_approved' => (!in_array($reservation->getStatus(), [Reservation::STATUS_APPROVED, Reservation::STATUS_ACTIVE]) || $this->isGranted(ReservationVoter::CAN_APPROVE, $reservation))
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $mapper->toEntity($reservationModel, $reservation);
            $reservationManager->saveToDatabase($reservation);
            $this->addFlash('success', 'Reservation edited successfully.');

            return $this->redirectToRoute('app_room_reservation_show', ['id' => $reservation->getId(), 'roomId' => $reservation->getRoom()->getId()]);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_room_reservation_delete')]
    #[IsGranted(ReservationVoter::DELETE, 'reservation')]
    public function delete(Request $request, Reservation $reservation, ReservationManagerInterface $reservationManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $reservationManager->deleteFromDatabase($reservation);
            $this->addFlash('error', 'Reservation deleted.');
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }

    #[Route('/{id}/approve', name: 'app_room_reservation_approve')]
    #[IsGranted(ReservationVoter::CAN_APPROVE, 'reservation')]
    public function approve(Request $request, Reservation $reservation, ReservationManagerInterface $reservationManager): Response
    {
        if ($this->isCsrfTokenValid('approve'.$reservation->getId(), $request->request->get('_token'))) {
            try {
                $reservationManager->approve($reservation, $this->getUser());
                $this->addFlash('success', 'Reservation approved.');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_room_reservation_reject')]
    #[IsGranted(ReservationVoter::CAN_REJECT, 'reservation')]
    public function reject(Request $request, Reservation $reservation, ReservationManagerInterface $reservationManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$reservation->getId(), $request->request->get('_token'))) {
            try {
                $reservationManager->reject($reservation);
                $this->addFlash('error', 'Reservation rejected.');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }
}
