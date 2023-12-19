<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Form\AppUserType;
use App\Form\Model\AppUserTypeModel;
use App\Repository\AppUserRepository;
use App\Service\AppUserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class AppUserController extends AbstractController
{
    #[Route('/', name: 'app_user_index')]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_ROOM_MANAGER") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function index(AppUserRepository $appUserRepository): Response
    {
        return $this->render('app_user/index.html.twig', [
            'app_users' => $appUserRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show')]
    public function show(AppUser $appUser): Response
    {
        return $this->render('app_user/show.html.twig', [
            'app_user' => $appUser,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit')]
    #[IsGranted('user_edit', 'appUser')]
    public function edit(Request $request, AppUser $appUser, AppUserManager $appUserManager): Response
    {
        $appUserModel = AppUserTypeModel::fromEntity($appUser);
        $form = $this->createForm(AppUserType::class, $appUserModel, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appUser = $appUserModel->toEntity($appUser);
            $appUserManager->saveToDatabase($appUser);

            return $this->redirectToRoute('app_user_show', ['id' => $appUser->getId()]);
        }

        return $this->render('app_user/edit.html.twig', [
            'app_user' => $appUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete')]
    #[IsGranted('user_delete', 'appUser')]
    public function delete(Request $request, AppUser $appUser, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $isCurrentUser = $this->getUser()->getId() == $appUser->getId();
        if ($this->isCsrfTokenValid('delete'.$appUser->getId(), $request->request->get('_token'))) {
            $entityManager->remove($appUser);
            $entityManager->flush();
        }

        if($isCurrentUser) {
            // TO-DO: not working
            $session->invalidate();
            return $this->render('app_user/deleted_user.html.twig');
        }
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
