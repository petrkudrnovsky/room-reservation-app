<?php

namespace App\Api\Controller;

use App\Api\Model\RoomInput;
use App\Api\Model\RoomOutput;
use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Repository\BuildingRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use App\Service\AppUserManager;
use App\Service\GroupManager;
use App\Service\RoomManager;
use App\Voter\RoomVoter;
use Exception;
use PHPUnit\Util\Json;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $buildingCode = $request->query->get('building_code');
        $filter = [
            'owningGroups' => $request->query->get('owning_groups'),
            'members' => $request->query->get('members'),
            'admins' => $request->query->get('admins'),
        ];

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();

        // restrict rooms to those that are accessible by current user, public or have an approved reservation for current user
        $roomsOutput = array_filter(
            $this->roomManager->findRoomsByFilters($name, $code, $buildingCode, $filter),
            fn (Room $room) => $this->isGranted(RoomVoter::VIEW_DETAIL, $room)
                || $room->isIsPrivate() === false
                || $this->roomManager->hasApprovedReservation($room, $currentUser)
        );

        $rooms = array_map(
            fn (Room $entity) => RoomOutput::fromEntity(
                $entity,
                $this->getUsersUrls($entity, true),
                $this->getUsersUrls($entity, false),
                $this->getGroupsUrls($entity),
                $this->getReservationsUrls($entity)),
            $this->roomManager->findRoomsByFilters($name, $code, $buildingCode, $filter)
        );

        return ['rooms' => $rooms];
    }

    #[Rest\Get('/room/{id}', name: 'api_rooms_detail', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): RoomOutput
    {
        $room = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_DETAIL, $room);

        return RoomOutput::fromEntity(
            $room,
            $this->getUsersUrls($room, true),
            $this->getUsersUrls($room, false),
            $this->getGroupsUrls($room),
            $this->getReservationsUrls($room)
        );
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
            $this->denyAccessUnlessGranted(RoomVoter::CREATE);
            $room = new Room();
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
            $this->getGroupsUrls($room),
            $this->getReservationsUrls($room)
        );
    }

    #[Rest\Delete('/room/{id}', name: 'api_rooms_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function delete(int $id): void
    {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE);
        $room = $this->findOrFail($id);
        $this->roomManager->deleteFromDatabase($room);
    }

    // ověření zda uživatel má v tuto chvíli přístup do místnosti (je neobsazená, je jejím uživatelem, má schválenou rezervaci)
    #[Rest\Get('/room/{id}/access', name: 'api_rooms_access', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function hasAccess(int $id): bool
    {
        $room = $this->findOrFail($id);

        if($room->isIsLocked()) {
            return false;
        }
        // the room is unlocked now

        /** @var AppUser $user */
        $user = $this->getUser();
        $ongoingReservation = $this->roomManager->getOngoingReservation($room);
        if(!$ongoingReservation) {
            if ($this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
                || $room->getMembers()->contains($user)
                || $room->getOwningGroups()->exists(fn (int $key, $group) => $group->getMembers()->contains($user))
            ) {
                return true;
            }
        }
        else {
            if ($this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
                || $ongoingReservation->getReservedFor() === $user
                || $ongoingReservation->getVisitors()->contains($user)
            ) {
                return true;
            }
        }
        return false;
    }

    #[Rest\Patch('/room/{id}/toggleLock', name: 'api_rooms_toggle_lock', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function toggleLock(int $id, RoomRepository $roomRepository): JsonResponse
    {
        $room = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(RoomVoter::CAN_TOGGLE_LOCK, $room);
        if($room->isIsLocked()) {
            $this->roomManager->unlockRoom($room);
            $message = "Room unlocked successfully.";
        } else {
            $this->roomManager->lockRoom($room);
            $message = "Room locked successfully.";
        }

        $this->roomManager->saveToDatabase($room);

        return new JsonResponse(['message' => $message], 200);
    }

    private function findOrFail(int $id): Room
    {
        $room = $this->roomManager->getRoomById($id);
        if (!$room) {
            throw $this->createNotFoundException('Room not found');
        }
        return $room;
    }

    private function getUsersUrls(Room $room, bool $isMember): array
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

    private function getGroupsUrls(Room $room): array
    {
        return array_map(
            fn ($group) => $this->generateUrl('api_groups_detail', ['id' => $group->getId()]),
            $room->getOwningGroups()->toArray()
        );
    }

    private function getReservationsUrls(Room $room): array
    {
        return array_map(
            fn ($reservation) => $this->generateUrl('api_reservations_detail', ['id' => $reservation->getId()]),
            $room->getReservations()->toArray()
        );
    }
}