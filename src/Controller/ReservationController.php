<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\Model\ReservationTypeModel;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Service\ReservationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new')]
    public function new(Request $request, ReservationManager $reservationManager): Response
    {
        $reservationModel = new ReservationTypeModel();
        $form = $this->createForm(ReservationType::class, $reservationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $reservationModel->toEntity();
            $reservation->setStatus(Reservation::STATUS_PENDING);
            $currentUser = $this->getUser();
            if($currentUser != null) {
                $reservation->setCreatedBy($currentUser);
                $reservation->addMember($currentUser);
            }
            $reservationManager->saveToDatabase($reservation);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservationModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show')]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit')]
    public function edit(Request $request, Reservation $reservation, ReservationManager $reservationManager): Response
    {
        $reservationModel = ReservationTypeModel::fromEntity($reservation);
        $form = $this->createForm(ReservationType::class, $reservationModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $reservationModel->toEntity($reservation);
            $reservationManager->saveToDatabase($reservation);

            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_reservation_delete')]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
