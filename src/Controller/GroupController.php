<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupType;
use App\Form\Model\GroupTypeModel;
use App\Repository\GroupRepository;
use App\Service\GroupManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/group')]
class GroupController extends AbstractController
{
    #[Route('/', name: 'app_group_index')]
    public function index(GroupRepository $groupRepository): Response
    {
        return $this->render('group/index.html.twig', [
            'groups' => $groupRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_group_new')]
    public function new(Request $request, GroupManager $groupManager): Response
    {
        $groupModel = new GroupTypeModel();
        $form = $this->createForm(GroupType::class, $groupModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $groupModel->toEntity();
            $groupManager->saveToDatabase($group);

            return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('group/new.html.twig', [
            'group' => $groupModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_group_show')]
    public function show(Group $group): Response
    {
        return $this->render('group/show.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_group_edit')]
    public function edit(Request $request, Group $group, GroupManager $groupManager): Response
    {
        $groupModel = GroupTypeModel::fromEntity($group);
        $form = $this->createForm(GroupType::class, $groupModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $groupModel->toEntity($group);
            $groupManager->saveToDatabase($group);

            return $this->redirectToRoute('app_group_show', ['id' => $group->getId()]);
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_group_delete')]
    public function delete(Request $request, Group $group, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$group->getId(), $request->request->get('_token'))) {
            $entityManager->remove($group);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
