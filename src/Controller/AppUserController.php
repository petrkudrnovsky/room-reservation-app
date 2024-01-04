<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Form\AppUserType;
use App\Form\Model\AppUserTypeModel;
use App\Repository\AppUserRepository;
use App\Service\AppUserManager;
use App\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class AppUserController extends AbstractController
{
    #[Route('/', name: 'app_user_index')]
    #[IsGranted(UserVoter::VIEW_INDEX)]
    public function index(AppUserRepository $appUserRepository): Response
    {
        return $this->render('app_user/index.html.twig', [
            'app_users' => $appUserRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new')]
    #[IsGranted(UserVoter::CREATE)]
    public function new(Request $request, AppUserManager $appUserManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $appUserModel = new AppUserTypeModel();
        $form = $this->createForm(AppUserType::class, $appUserModel, ['is_registration' => false, 'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity();
            if($form->has('password')) {
                $plainPassword = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($appUser, $plainPassword);
                $appUser->setPassword($hashedPassword);
            }
            $appUserManager->saveToDatabase($appUser);
            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('app_user/new.html.twig', [
            'app_user' => $appUserModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show')]
    #[IsGranted(UserVoter::VIEW_DETAIL, 'appUser')]
    public function show(AppUser $appUser): Response
    {
        return $this->render('app_user/show.html.twig', [
            'app_user' => $appUser,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit')]
    #[IsGranted(UserVoter::EDIT, 'appUser')]
    public function edit(Request $request, AppUser $appUser, AppUserManager $appUserManager): Response
    {
        $appUserModel = AppUserTypeModel::fromEntity($appUser);
        $form = $this->createForm(AppUserType::class, $appUserModel, ['is_edit' => true, 'is_registration' => false, 'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity($appUser);
            $appUserManager->saveToDatabase($appUser);
            $this->addFlash('success', 'User edited successfully.');

            return $this->redirectToRoute('app_user_show', ['id' => $appUser->getId()]);
        }

        return $this->render('app_user/edit.html.twig', [
            'app_user' => $appUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete')]
    #[IsGranted(UserVoter::DELETE, 'appUser')]
    public function delete(Request $request, AppUser $appUser, AppUserManager $appUserManager, SessionInterface $session): Response
    {
        // if the user is deleting their own account, log them out
        if($appUser === $this->getUser()) {
            if ($this->isCsrfTokenValid('delete'.$appUser->getId(), $request->request->get('_token'))) {
                //dd("deleteing");
                $session->invalidate();
                $appUserManager->removeFromDatabase($appUser);
            }
            return $this->redirectToRoute('app_logout');
        }
        if ($this->isCsrfTokenValid('delete'.$appUser->getId(), $request->request->get('_token'))) {
            $appUserManager->removeFromDatabase($appUser);
            $this->addFlash('success', 'User deleted from the system.');
        }
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}

