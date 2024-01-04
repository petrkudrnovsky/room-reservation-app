<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\Model\RoomTypeModel;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use App\Service\RoomManager;
use App\Voter\ReservationVoter;
use App\Voter\RoomVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/room')]
class RoomController extends AbstractController
{
    // this page is accessible even for not logged-in users - it shows only public rooms
    #[Route('/', name: 'app_room_index')]
    public function index(RoomRepository $roomRepository): Response
    {
        /** @var ?AppUser $currentUser */
        $currentUser = $this->getUser();
        $allRooms = null;

        if($this->isGranted('ROLE_SUPER_ADMIN')) {
            $allRooms = $roomRepository->findAll();
        }

        if($currentUser === null) {
            return $this->render('room/index.html.twig', [
                'publicRooms' => $roomRepository->findBy(['isPrivate' => false]),
            ]);
        }

        return $this->render('room/index.html.twig', [
            'memberRooms' => $currentUser->getMemberRooms(),
            'adminRooms' => $currentUser->getAdminRooms(),
            'allRooms' => $allRooms,
            'publicRooms' => $roomRepository->findBy(['isPrivate' => false]),
        ]);
    }

    #[Route('/new', name: 'app_room_new')]
    #[IsGranted(RoomVoter::CREATE)]
    public function new(Request $request, RoomManager $roomManager): Response
    {
        $roomModel = new RoomTypeModel();
        $form = $this->createForm(RoomType::class, $roomModel, ['is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $roomModel->toEntity();
            $roomManager->saveToDatabase($room);
            $this->addFlash('success', 'Room created successfully.');

            return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('room/new.html.twig', [
            'room' => $roomModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_room_show')]
    #[IsGranted(RoomVoter::VIEW_DETAIL, 'room')]
    public function show(Room $room, RoomManager $roomManager): Response
    {
        $pendingReservations = null;
        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        if($this->isGranted(RoomVoter::CAN_VIEW_FULL_RESERVATIONS, $room)) {
            $pendingReservations = $roomManager->getOrderedReservations($room, Reservation::STATUS_PENDING);
        }
        else {
            /** @var Reservation[] $pendingReservations */
            $pendingReservations = $roomManager->getOrderedReservations($room, Reservation::STATUS_PENDING);
            $pendingReservations = array_filter($pendingReservations, fn(Reservation $reservation) => $reservation->getReservedFor() === $currentUser);
        }

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'approvedReservations' => $roomManager->getOrderedReservations($room, Reservation::STATUS_APPROVED),
            'pendingReservations' => $pendingReservations,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_edit')]
    #[IsGranted(RoomVoter::EDIT, 'room')]
    public function edit(Request $request, Room $room, RoomManager $roomManager): Response
    {
        $roomModel = RoomTypeModel::fromEntity($room);
        $form = $this->createForm(RoomType::class, $roomModel, ['is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN'), 'can_edit_members' => $this->isGranted(RoomVoter::EDIT_MEMBERS, $room), 'can_edit_admins' => $this->isGranted(RoomVoter::EDIT_ADMINS, $room)]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $roomModel->toEntity($room);
            $roomManager->saveToDatabase($room);
            $this->addFlash('success', 'Room edited successfully.');

            return $this->redirectToRoute('app_room_show', ['id' => $room->getId()]);
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_room_delete')]
    #[IsGranted(RoomVoter::DELETE, 'room')]
    public function delete(Request $request, Room $room, RoomManager $roomManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), $request->request->get('_token'))) {
            $roomManager->deleteFromDatabase($room);
            $this->addFlash('error', 'Room deleted.');
        }

        return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
    }
}
