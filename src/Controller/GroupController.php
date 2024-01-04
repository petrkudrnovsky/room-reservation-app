<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Form\GroupType;
use App\Form\Model\GroupTypeModel;
use App\Repository\GroupRepository;
use App\Service\GroupManager;
use App\Voter\GroupVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/group')]
class GroupController extends AbstractController
{
    #[Route('/', name: 'app_group_index')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(GroupRepository $groupRepository): Response
    {
        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        $allGroups = null;

        if($this->isGranted('ROLE_SUPER_ADMIN')) {
            $allGroups = $groupRepository->findAll();
        }

        return $this->render('group/index.html.twig', [
            'memberGroups' => $currentUser->getMemberGroups(),
            'adminGroups' => $currentUser->getAdminGroups(),
            'allGroups' => $allGroups,
        ]);
    }

    #[Route('/new', name: 'app_group_new')]
    #[IsGranted(GroupVoter::CREATE)]
    public function new(Request $request, GroupManager $groupManager): Response
    {
        $groupModel = new GroupTypeModel();
        $form = $this->createForm(GroupType::class, $groupModel, ['is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $groupModel->toEntity();
            $groupManager->saveToDatabase($group);
            $this->addFlash('success', 'Group created successfully.');

            return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('group/new.html.twig', [
            'group' => $groupModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_group_show')]
    #[IsGranted(GroupVoter::VIEW_DETAIL, 'group')]
    public function show(Group $group): Response
    {
        return $this->render('group/show.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_group_edit')]
    #[IsGranted(GroupVoter::EDIT, 'group')]
    public function edit(Request $request, Group $group, GroupManager $groupManager): Response
    {
        $groupModel = GroupTypeModel::fromEntity($group);
        $form = $this->createForm(GroupType::class, $groupModel, ['is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $groupModel->toEntity($group);
            $groupManager->saveToDatabase($group);
            $this->addFlash('success', 'Group edited successfully.');

            return $this->redirectToRoute('app_group_show', ['id' => $group->getId()]);
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_group_delete')]
    #[IsGranted(GroupVoter::DELETE)]
    public function delete(Request $request, Group $group, GroupManager $groupManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$group->getId(), $request->request->get('_token'))) {
            $groupManager->removeFromDatabase($group);
            $this->addFlash('error', 'Group deleted.');
        }

        return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
