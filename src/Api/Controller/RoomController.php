<?php

namespace App\Api\Controller;

use App\Api\Mapper\RoomInputMapper;
use App\Api\Model\RoomInput;
use App\Api\Model\RoomOutput;
use App\Api\Service\EntityLinksFactory;
use App\Entity\AccessLog;
use App\Entity\AppUser;
use App\Entity\Room;
use App\Filter\RoomFilterCriteria;
use App\Repository\RoomRepository;
use App\Service\ReservationManagerInterface;
use App\Service\RoomAccessServiceInterface;
use App\Service\RoomManagerInterface;
use App\Voter\RoomVoter;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly RoomManagerInterface $roomManager,
        private readonly ReservationManagerInterface $reservationManager,
        private readonly RoomAccessServiceInterface $roomAccessService,
        private readonly RoomInputMapper $roomInputMapper,
        private readonly EntityLinksFactory $linksFactory,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Rest\Get('/room', name: 'api_rooms_list')]
    #[Rest\View(statusCode: 200)]

    public function list(Request $request): array
    {
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_INDEX);

        $parseIds = fn (?string $s): ?array => $s !== null ? explode(',', $s) : null;

        $criteria = new RoomFilterCriteria(
            name: $request->query->get('name'),
            code: $request->query->get('code'),
            buildingCode: $request->query->get('building_code'),
            owningGroupIds: $parseIds($request->query->get('owning_groups')),
            memberIds: $parseIds($request->query->get('members')),
            adminIds: $parseIds($request->query->get('admins')),
        );

        /** @var AppUser $currentUser */
        $currentUser = $this->getUser();

        $allRooms = $this->roomManager->findRoomsByFilters($criteria);

        // restrict rooms to those that are accessible by current user, public or have an approved reservation for current user
        $rooms = array_values(array_filter(
            $allRooms,
            fn (Room $room) => $this->isGranted(RoomVoter::VIEW_DETAIL, $room)
                || $room->isIsPrivate() === false
                || ($currentUser !== null && $this->reservationManager->hasApprovedReservation($room, $currentUser))
        ));

        return ['rooms' => array_map(
            fn (Room $entity) => RoomOutput::fromEntity($entity, $this->linksFactory->forRoom($entity)),
            $rooms
        )];
    }

    #[Rest\Get('/room/{id}', name: 'api_rooms_detail', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function detail(int $id): RoomOutput
    {
        $room = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(RoomVoter::VIEW_DETAIL, $room);

        return RoomOutput::fromEntity($room, $this->linksFactory->forRoom($room));
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

        $room = $this->roomInputMapper->toEntity($roomInput, $room);
        $room = $this->roomManager->saveToDatabase($room);
        return RoomOutput::fromEntity($room, $this->linksFactory->forRoom($room));
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
    public function hasAccess(int $id): JsonResponse
    {
        $room = $this->findOrFail($id);
        /** @var ?AppUser $user */
        $user = $this->getUser();

        $result = $this->roomAccessService->computeAccess($room, $user);

        $log = new AccessLog();
        $log->setRoom($room)
            ->setUser($user)
            ->setDecision($result ? AccessLog::DECISION_GRANTED : AccessLog::DECISION_DENIED)
            ->setAccessedAt(new \DateTime());
        $this->em->persist($log);
        $this->em->flush();

        return $this->json(['hasAccess' => $result]);
    }

    #[Rest\Patch('/room/{id}/toggleLock', name: 'api_rooms_toggle_lock', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 200)]
    public function toggleLock(int $id, RoomRepository $roomRepository): JsonResponse
    {
        $room = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(RoomVoter::CAN_TOGGLE_LOCK, $room);
        if($room->getLockState() === Room::LOCK_STATE_LOCKED) {
            $this->roomManager->unlockRoom($room);
            $message = "Room unlocked successfully.";
        } else {
            $this->roomManager->lockRoom($room);
            $message = "Room locked successfully.";
        }

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
}
