<?php

namespace App\Controller;

use App\Repository\BuildingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/building')]
class BuildingController extends AbstractController
{
    #[Route('/', name: 'app_building_index')]
    public function index(BuildingRepository $buildingRepository): Response
    {
        return $this->render('building/index.html.twig', [
            'buildings' => $buildingRepository->findAll(),
        ]);
    }
}