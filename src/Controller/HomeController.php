<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();

        $userReservations = null;
        $userVisitingReservations = null;
        if($currentUser) {
            $userReservations = $reservationRepository->findReservationsByUser($currentUser);
            $userVisitingReservations = array_filter($reservationRepository->findVisitingReservationsByUser($currentUser), fn($reservation) => $reservation->getStatus() === Reservation::STATUS_APPROVED);
        }

        return $this->render('home/index.html.twig', [
            'userReservations' => $userReservations,
            'userVisitingReservations' => $userVisitingReservations,
        ]);
    }
}
