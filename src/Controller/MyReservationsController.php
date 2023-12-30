<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyReservationsController extends AbstractController
{
    #[Route('my-reservations', name: 'app_my_reservations_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // render only reservations for current user
        $reservations = $reservationRepository->findBy(['reservedFor' => $this->getUser()]);
        return $this->render('reservation/my-reservations-index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}
