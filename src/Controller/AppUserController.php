<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Form\AppUserType;
use App\Form\Model\AppUserTypeModel;
use App\Repository\AppUserRepository;
use App\Service\AppUserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class AppUserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(AppUserRepository $appUserRepository): Response
    {
        return $this->render('app_user/index.html.twig', [
            'app_users' => $appUserRepository->findAll(),
        ]);
    }

    #[Route('/registration', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AppUserManager $appUserManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $appUserModel = new AppUserTypeModel();
        $form = $this->createForm(AppUserType::class, $appUserModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity();
            $appUser->addRole('ROLE_USER');
            if($form->has('password')) {
                $plainPassword = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($appUser, $plainPassword);
                $appUser->setPassword($hashedPassword);
            }
            $appUserManager->saveToDatabase($appUser);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('app_user/new.html.twig', [
            'app_user' => $appUserModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(AppUser $appUser): Response
    {
        return $this->render('app_user/show.html.twig', [
            'app_user' => $appUser,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, AppUser $appUser, AppUserManager $appUserManager): Response
    {
        $appUserModel = AppUserTypeModel::fromEntity($appUser);
        $form = $this->createForm(AppUserType::class, $appUserModel, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity($appUser);
            $appUserManager->saveToDatabase($appUser);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('app_user/edit.html.twig', [
            'app_user' => $appUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, AppUser $appUser, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$appUser->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appUser);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
