<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\Model\ReservationTypeModel;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationManager;
use App\Service\RoomManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/room/{roomId}/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_room_reservation_index')]
    public function index(int $roomId, ReservationRepository $reservationRepository, RoomManager $roomManager): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findReservationsByRoomId($roomId),
            'room' => $roomManager->getRoomById($roomId),
        ]);
    }

    #[Route('/new', name: 'app_room_reservation_new')]
    public function new(Request $request, int $roomId, ReservationManager $reservationManager, RoomManager $roomManager): Response
    {
        // TO-DO: determine if user is creating a reservation for themselves or for another user (if they have permission)
        $reservationModel = new ReservationTypeModel();
        $reservationModel->room = $roomManager->getRoomById($roomId);
        $form = $this->createForm(ReservationType::class, $reservationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $reservationModel->toEntity();
            // TO-DO: determine if user is creating a reservation for themselves or for another user
            $reservation = $reservationManager->prepareNewReservation($reservation);
            $reservationManager->saveToDatabase($reservation);

            $this->addFlash('success', 'Reservation created.');
            return $this->redirectToRoute('app_room_show', ['id' => $roomId]);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservationModel,
            'form' => $form,
            'approvedReservations' => $roomManager->getOrderedReservations($reservationModel->room, Reservation::STATUS_APPROVED),
        ]);
    }

    #[Route('/{id}', name: 'app_room_reservation_show')]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_reservation_edit')]
    public function edit(Request $request, Reservation $reservation, ReservationManager $reservationManager): Response
    {
        $reservationModel = ReservationTypeModel::fromEntity($reservation);
        $form = $this->createForm(ReservationType::class, $reservationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $reservationModel->toEntity($reservation);
            $reservationManager->saveToDatabase($reservation);

            return $this->redirectToRoute('app_room_reservation_show', ['id' => $reservation->getId(), 'roomId' => $reservation->getRoom()->getId()]);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_room_reservation_delete')]
    public function delete(Request $request, Reservation $reservation, ReservationManager $reservationManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $reservationManager->deleteFromDatabase($reservation);
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }

    #[Route('/{id}/approve', name: 'app_room_reservation_approve')]
    public function approve(Request $request, Reservation $reservation, ReservationManager $reservationManager, ReservationRepository $reservationRepository): Response
    {
        if ($this->isCsrfTokenValid('approve'.$reservation->getId(), $request->request->get('_token'))) {
            $overlappingReservations = $reservationRepository->findOverlappingReservations(
                $reservation->getRoom()->getId(),
                $reservation->getStartDatetime(),
                $reservation->getEndDatetime(),
            );
            if(count($overlappingReservations) > 0) {
                $this->addFlash('error', 'Cannot approve reservation because it overlaps with another reservation.');
                return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
            }
            $reservation->setStatus(Reservation::STATUS_APPROVED);
            $reservation->setApprovedBy($this->getUser());
            $reservationManager->saveToDatabase($reservation);
            $this->addFlash('success', 'Reservation approved.');
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }

    #[Route('/{id}/reject', name: 'app_room_reservation_reject')]
    public function reject(Request $request, Reservation $reservation, ReservationManager $reservationManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatus(Reservation::STATUS_REJECTED);
            $reservationManager->saveToDatabase($reservation);
            $this->addFlash('error', 'Reservation rejected.');
        }

        return $this->redirectToRoute('app_room_show', ['id' => $reservation->getRoom()->getId()]);
    }
}
