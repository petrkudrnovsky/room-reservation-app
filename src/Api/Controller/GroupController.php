<?php

namespace App\Api\Controller;

use App\Api\Model\AppUserInput;
use App\Api\Model\GroupInput;
use App\Api\Model\GroupOutput;
use App\Api\Model\RoomInput;
use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Repository\AppUserRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use App\Service\AppUserManager;
use App\Service\GroupManager;
use App\Service\RoomManager;
use App\Voter\GroupVoter;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
        private readonly AppUserManager $appUserManager,
        private readonly RoomManager $roomManager,
    ) {
    }

    #[Rest\Get('/group', name: 'api_groups_list')]
    #[Rest\View(statusCode: 200)]
    public function list(Request $request): array
    {
        $this->denyAccessUnlessGranted(GroupVoter::VIEW_INDEX);

        $name = $request->query->get('name');
        $filters = [
            'members' => $request->query->get('members'),
            'admins' => $request->query->get('admins'),
            'rooms' => $request->query->get('rooms'),
        ];

        $groups = array_map(
            fn(Group $entity) => GroupOutput::fromEntity($entity, $this->getUsersUrls($entity, true), $this->getUsersUrls($entity, false), $this->getRoomsUrls($entity)),
            $this->groupManager->findGroupsByFilters($name, $filters)
        );

        return ['groups' => $groups];
    }


    #[Rest\Get('/group/{id}', name: 'api_groups_detail', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): GroupOutput
    {
        $group = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(GroupVoter::VIEW_DETAIL, $group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    /**
     * @throws Exception
     */
    #[Rest\Post('/group', name: 'api_groups_create')]
    #[Rest\Put('/group/{id}', name: 'api_groups_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('groupInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function update(?int $id, GroupInput $groupInput, ConstraintViolationListInterface $errors): GroupOutput
    {
        if ($id === null) {
            $this->denyAccessUnlessGranted(GroupVoter::CREATE);
            $group = new Group();
        } else {
            $group = $this->findOrFail($id);
            $this->denyAccessUnlessGranted(GroupVoter::EDIT, $group);
        }

        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $group = $groupInput->toEntity($this->appUserManager, $this->roomManager, $group);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Delete('/group/{id}', name: 'api_groups_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function delete(int $id): void
    {
        $this->denyAccessUnlessGranted(GroupVoter::DELETE);
        $group = $this->findOrFail($id);
        $this->groupManager->removeFromDatabase($group);
    }

    #[Rest\Patch('/group/{id}/user', name: 'api_groups_add_member', requirements: ['id' => '\d+'])]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 200)]
    public function addMember(int $id, AppUserInput $appUserInput): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->findOrFailUser($appUserInput->id);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        if (!$this->isGroupAdmin($this->appUserRepository->find($currentUser->getId()), $group) &&
            !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not an admin of this group or a super admin to perform this action!');
        }

        $group->addMember($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Delete('/group/{id}/user/{userId}', name: 'api_groups_remove_member', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function removeMember(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->findOrFailUser($userId);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        if (!$this->isGroupAdmin($this->appUserRepository->find($currentUser->getId()), $group) &&
            !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not an admin of this group or a super admin to perform this action!');
        }

        if(!$group->getMembers()->contains($user)) {
            throw new HttpException(400, message: 'This user is not a member of this group!');
        }

        $group->removeMember($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Patch('/group/{id}/admin', name: 'api_groups_add_admin', requirements: ['id' => '\d+'])]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 200)]
    public function addAdmin(int $id, AppUserInput $appUserInput): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->findOrFailUser($appUserInput->id);

        if (!$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not a super admin to perform this action!');
        }

        $group->addAdmin($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Delete('/group/{id}/admin/{userId}', name: 'api_groups_remove_admin', requirements: ['id' => '\d+', 'userId' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function removeAdmin(int $id, int $userId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $user = $this->findOrFailUser($userId);

        if (!$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not a super admin to perform this action!');
        }

        if(!$group->getAdmins()->contains($user)) {
            throw new HttpException(400, message: 'This user is not an admin of this group!');
        }

        $group->removeAdmin($user);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Patch('/group/{id}/room', name: 'api_groups_add_room', requirements: ['id' => '\d+'])]
    #[ParamConverter('roomInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 200)]
    public function addRoom(int $id, RoomInput $roomInput): GroupOutput
    {
        $group = $this->findOrFail($id);

        $roomId = $roomInput->id;
        $room = $this->findOrFailRoom($roomId);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        if (!$this->isGroupAdmin($this->appUserRepository->find($currentUser->getId()), $group) &&
            !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not an admin of this group or a super admin to perform this action!');
        }

        $group->addRoom($room);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    #[Rest\Delete('/group/{id}/room/{roomId}', name: 'api_groups_remove_room', requirements: ['id' => '\d+', 'roomId' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function removeRoom(int $id, int $roomId): GroupOutput
    {
        $group = $this->findOrFail($id);
        $room = $this->findOrFailRoom($roomId);

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();
        if (!$this->isGroupAdmin($this->appUserRepository->find($currentUser->getId()), $group) &&
            !$this->isGranted('ROLE_SUPER_ADMIN'))
        {
            throw new HttpException(403, message: 'You are not an admin of this group or a super admin to perform this action!');
        }

        if(!$group->getRooms()->contains($room)) {
            throw new HttpException(400, message: 'This room is not part of this group!');
        }

        $group->removeRoom($room);
        $this->groupManager->saveToDatabase($group);
        return GroupOutput::fromEntity($group, $this->getUsersUrls($group, true), $this->getUsersUrls($group, false), $this->getRoomsUrls($group));
    }

    private function getUsersUrls(Group $group, bool $isMember): array
    {
        if ($isMember) {
            return array_map(
                fn ($member) => $this->generateUrl('api_app_users_detail', ['id' => $member->getId()]),
                $group->getMembers()->toArray()
            );
        } else {
            return array_map(
                fn ($admin) => $this->generateUrl('api_app_users_detail', ['id' => $admin->getId()]),
                $group->getAdmins()->toArray()
            );

        }
    }

    private function getRoomsUrls(Group $group): array
    {
        return array_map(
            fn ($room) => $this->generateUrl('api_rooms_detail', ['id' => $room->getId()]),
            $group->getRooms()->toArray()
        );
    }

    private function findOrFailUser(int $id): AppUser
    {
        $user = $this->appUserRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $user;
    }

    private function findOrFailRoom(int $id): Room
    {
        $room = $this->roomRepository->find($id);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        return $room;
    }

    private function findOrFail(int $id): Group
    {
        $group = $this->groupRepository->find($id);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        return $group;
    }

    private function isGroupAdmin(AppUser $currentUser, Group $group): bool{
        return $group->getAdmins()->contains($currentUser);
    }
}