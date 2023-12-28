<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Voter\ReservationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('reservation')]
#[IsGranted(ReservationVoter::VIEW_INDEX_ALL)]
class AllReservationsController extends AbstractController
{
    #[Route('/', name: 'app_all_reservations_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findAll();
        return $this->render('reservation/all-reservations-index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}