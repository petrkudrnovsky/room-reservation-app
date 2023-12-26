<?php

namespace App\Api\Controller;

use App\Api\Model\GroupInput;
use App\Api\Model\GroupOutput;
use App\Entity\Group;
use App\Repository\AppUserRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use App\Service\GroupManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;

class GroupController extends AbstractFOSRestController {
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly GroupManager $groupManager,
        private readonly AppUserRepository $appUserRepository,
        private readonly RoomRepository $roomRepository,
    ) {
    }

    #[Rest\Get('/group', name: 'api_groups_list')]
    #[Rest\View]
    public function list(Request $request): array {
        $name = $request->query->get('name');
        $groups = array_map(
            fn (Group $entity) => GroupOutput::fromEntity($entity),
            $this->groupManager->findGroupsByName($name)
        );

        return ['groups' => $groups];
    }

    #[Rest\Get('/group/{id}', name: 'api_groups_get', requirements: ['id' => '\d+'])]
    #[Rest\View]
    public function get(int $id): GroupOutput
    {
        $group = $this->findOrFail($id);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Post('/group', name: 'api_groups_create')]
    #[Rest\Put('/group/{id}', name: 'api_groups_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('groupInput', converter: 'fos_rest.request_body')]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function update(?int $id, GroupInput $groupInput, ConstraintViolationListInterface $errors): GroupOutput
    {
        $group = $id !== null ? $this->findOrFail($id) : new Group();
        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $group = $groupInput->toEntity($group, $this->appUserRepository, $this->roomRepository);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Delete('/group/{id}', name: 'api_groups_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function delete(int $id): void
    {
        $group = $this->findOrFail($id);
        $this->groupManager->removeFromDatabase($group);
    }

    public function findOrFail(int $id): Group
    {
        $group = $this->groupRepository->find($id);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        return $group;
    }

    #[Rest\Post('/group/{id}/user/{userId}', name: 'api_groups_add_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function addMember(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->appUserRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $group->addMember($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Delete('/group/{id}/user/{userId}', name: 'api_groups_remove_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function removeMember(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->appUserRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $group->removeMember($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Post('/group/{id}/admin/{userId}', name: 'api_groups_add_admin', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function addAdmin(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->appUserRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $group->addAdmin($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Delete('/group/{id}/admin/{userId}', name: 'api_groups_remove_admin', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function removeAdmin(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->appUserRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $group->removeAdmin($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Post('/group/{id}/room/{roomId}', name: 'api_groups_add_room', requirements: ['id' => '\d+', 'roomId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function addRoom(int $id, int $roomId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $room = $this->roomRepository->find($roomId);
        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }
        $group->addRoom($room);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

    #[Rest\Delete('/group/{id}/room/{roomId}', name: 'api_groups_remove_room', requirements: ['id' => '\d+', 'roomId' => '\d+'])]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function removeRoom(int $id, int $roomId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $room = $this->roomRepository->find($roomId);
        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }
        $group->removeRoom($room);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group);
    }

}