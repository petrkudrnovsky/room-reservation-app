<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Form\Model\ReservationTypeModel;
use App\Form\Model\RoomTypeModel;
use App\Form\ReservationType;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use App\Service\ReservationManager;
use App\Service\RoomManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/room')]
class RoomController extends AbstractController
{
    #[Route('/', name: 'app_room_index')]
    public function index(RoomRepository $roomRepository): Response
    {
        return $this->render('room/index.html.twig', [
            'rooms' => $roomRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_room_new')]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function new(Request $request, RoomManager $roomManager): Response
    {
        $roomModel = new RoomTypeModel();
        $form = $this->createForm(RoomType::class, $roomModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $roomModel->toEntity();

            $roomManager->saveToDatabase($room);

            return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('room/new.html.twig', [
            'room' => $roomModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_room_show')]
    public function show(Room $room, RoomManager $roomManager): Response
    {
        return $this->render('room/show.html.twig', [
            'room' => $room,
            'approvedReservations' => $roomManager->getOrderedReservations($room, Reservation::STATUS_APPROVED),
            'pendingReservations' => $roomManager->getOrderedReservations($room, Reservation::STATUS_PENDING),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_edit')]
    public function edit(Request $request, Room $room, RoomManager $roomManager): Response
    {
        $roomModel = RoomTypeModel::fromEntity($room);
        $form = $this->createForm(RoomType::class, $roomModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $room = $roomModel->toEntity($room);
            $roomManager->saveToDatabase($room);

            return $this->redirectToRoute('app_room_show', ['id' => $room->getId()]);
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_room_delete')]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function delete(Request $request, Room $room, RoomManager $roomManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), $request->request->get('_token'))) {
            $roomManager->deleteFromDatabase($room);
        }

        return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
    }
}
