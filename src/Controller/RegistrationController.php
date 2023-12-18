<?php

namespace App\Controller;

use App\Form\AppUserType;
use App\Form\Model\AppUserTypeModel;
use App\Service\AppUserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_user_new')]
    public function new(Request $request, AppUserManager $appUserManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $appUserModel = new AppUserTypeModel();
        $form = $this->createForm(AppUserType::class, $appUserModel);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity();
            $appUser->addRole('ROLE_USER'); // default
            if($form->has('password')) {
                $plainPassword = $form->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($appUser, $plainPassword);
                $appUser->setPassword($hashedPassword);
            }
            $appUserManager->saveToDatabase($appUser);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('app_user/new.html.twig', [
            'app_user' => $appUserModel,
            'form' => $form,
        ]);
    }
}