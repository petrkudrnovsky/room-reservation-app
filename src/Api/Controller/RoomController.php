<?php

namespace App\Api\Controller;

use App\Api\Model\RoomInput;
use App\Api\Model\RoomOutput;
use App\Entity\Room;
use App\Repository\BuildingRepository;
use App\Repository\GroupRepository;
use App\Service\AppUserManager;
use App\Service\GroupManager;
use App\Service\RoomManager;
use App\Voter\RoomVoter;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class RoomController extends AbstractFOSRestController
{
    public function __construct(
        private readonly RoomManager $roomManager,
        private readonly AppUserManager $userManager,
        private readonly GroupManager $groupManager,
        private readonly BuildingRepository $buildingRepository,
    ) {}

    #[Rest\Get('/room', name: 'api_rooms_list')]
    #[Rest\View(statusCode: 200)]

    public function list(Request $request): array
    {
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_INDEX);
        $name = $request->query->get('name');
        $code = $request->query->get('code');
        $buildingId = $request->query->get('buildingId');

        $rooms = array_map(
            fn (Room $entity) => RoomOutput::fromEntity($entity, $this->getUsersUrls($entity, true), $this->getUsersUrls($entity, false), $this->getGroupsUrls($entity)),
            $this->roomManager->findRoomsByFilters($name, $code, $buildingId)
        );

        return ['rooms' => $rooms];
    }

    #[Rest\Get('/room/{id}', name: 'api_rooms_detail', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): RoomOutput
    {
        $room = $this->roomManager->getRoomById($id);
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_DETAIL, $room);

        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }

        return RoomOutput::fromEntity($room, $this->getUsersUrls($room, true), $this->getUsersUrls($room, false), $this->getGroupsUrls($room));
    }

    /**
     * @throws Exception
     */
    #[Rest\Post('/room', name: 'api_rooms_create')]
    #[Rest\Put('/room/{id}', name: 'api_rooms_edit', requirements: ['id' => '\d+'])]
    #[ParamConverter('roomInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function update(?int $id, RoomInput $roomInput, ConstraintViolationListInterface $errors): RoomOutput
    {
        if ($id !== null) {
            $room = $this->findOrFail($id);
            $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);
        } else {
            $room = new Room();
            $this->denyAccessUnlessGranted(RoomVoter::CREATE);
        }

        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $room = $roomInput->toEntity($this->userManager, $this->groupManager, $this->buildingRepository, $room);
        $room = $this->roomManager->saveToDatabase($room);
        return RoomOutput::fromEntity(
            $room,
            $this->getUsersUrls($room, true),
            $this->getUsersUrls($room, false),
            $this->getGroupsUrls($room));
    }

    #[Rest\Delete('/room/{id}', name: 'api_rooms_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function delete(int $id): void
    {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE);
        $room = $this->findOrFail($id);
        $this->roomManager->deleteFromDatabase($room);
    }

    public function findOrFail(int $id): Room
    {
        $room = $this->roomManager->getRoomById($id);
        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }
        return $room;
    }

    public function getUsersUrls(Room $room, bool $isMember): array
    {
        if ($isMember) {
            return array_map(
                fn ($member) => $this->generateUrl('api_app_users_detail', ['id' => $member->getId()]),
                $room->getMembers()->toArray()
            );
        } else {
            return array_map(
                fn ($admin) => $this->generateUrl('api_app_users_detail', ['id' => $admin->getId()]),
                $room->getAdmins()->toArray()
            );

        }
    }

    public function getGroupsUrls(Room $room): array
    {
        return array_map(
            fn ($group) => $this->generateUrl('api_groups_detail', ['id' => $group->getId()]),
            $room->getOwningGroups()->toArray()
        );
    }
}