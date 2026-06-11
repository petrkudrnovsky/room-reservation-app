<?php

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use App\Entity\AppUser;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Service\RoomAccessServiceInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ReservationAccessTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RoomAccessServiceInterface $accessService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em            = static::getContainer()->get(EntityManagerInterface::class);
        $this->accessService = static::getContainer()->get(RoomAccessServiceInterface::class);
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(AppFixtures::class));
        (new ORMExecutor($this->em, new ORMPurger()))->execute($loader->getFixtures());
    }

    private function loginAs(AppUser $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function getRoomId(string $buildingCode, string $roomCode): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('r.id')
            ->from(Room::class, 'r')
            ->join('r.building', 'b')
            ->where('b.code = :bc')
            ->andWhere('r.code = :rc')
            ->setParameter('bc', $buildingCode)
            ->setParameter('rc', $roomCode)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUserByUsername(string $username): AppUser
    {
        return $this->em->getRepository(AppUser::class)->findOneBy(['username' => $username]);
    }

    private function getReservationByTitle(string $title): Reservation
    {
        return $this->em->getRepository(Reservation::class)->findOneBy(['title' => $title]);
    }

    private function forceReservationStatus(Reservation $reservation, string $status): void
    {
        $reservation->setStatus($status);
        $this->em->flush();
        $this->em->clear();
    }

    private function forceRoomLockState(Room $room, string $lockState): void
    {
        $room->setLockState($lockState);
        $this->em->flush();
        $this->em->clear();
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_locked_room_denies_access_to_member(): void
    {
        $jakub = $this->getUserByUsername('jakub.simunek');
        $this->loginAs($jakub);
        $room = $this->em->find(Room::class, $this->getRoomId('T9', '301'));
        $this->assertFalse($this->accessService->computeAccess($room, $jakub));
    }

    public function test_unlocked_room_grants_direct_member(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $room = $this->em->find(Room::class, $roomId);
        $jakub = $this->getUserByUsername('jakub.simunek');
        $this->loginAs($jakub);
        $this->assertTrue($this->accessService->computeAccess($room, $jakub));
    }

    public function test_unlocked_room_denies_non_member(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $room = $this->em->find(Room::class, $roomId);
        $anezka = $this->getUserByUsername('anezka.rihova');
        $this->loginAs($anezka);
        $this->assertFalse($this->accessService->computeAccess($room, $anezka));
    }

    public function test_unlocked_room_grants_room_admin(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $room = $this->em->find(Room::class, $roomId);
        $tomas = $this->getUserByUsername('tomas.blaha');
        $this->loginAs($tomas);
        $this->assertTrue($this->accessService->computeAccess($room, $tomas));
    }

    public function test_unlocked_room_grants_group_member_of_owning_group(): void
    {
        // ondrej.sedlacek is KSI member; KSI owns TH:A-9101
        $roomId = $this->getRoomId('TH:A', '9101');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $room = $this->em->find(Room::class, $roomId);
        $ondrej = $this->getUserByUsername('ondrej.sedlacek');
        $this->loginAs($ondrej);
        $this->assertTrue($this->accessService->computeAccess($room, $ondrej));
    }

    public function test_ongoing_reservation_grants_holder(): void
    {
        $roomId = $this->getRoomId('TH:A', '9101');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $reservation = $this->getReservationByTitle('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservation, Reservation::STATUS_ACTIVE);

        $room = $this->em->find(Room::class, $roomId);
        $ondrej = $this->getUserByUsername('ondrej.sedlacek');
        $this->loginAs($ondrej);
        $this->assertTrue($this->accessService->computeAccess($room, $ondrej));
    }

    public function test_ongoing_reservation_denies_unrelated_user(): void
    {
        $roomId = $this->getRoomId('TH:A', '9101');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $reservation = $this->getReservationByTitle('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservation, Reservation::STATUS_ACTIVE);

        $room = $this->em->find(Room::class, $roomId);
        $anezka = $this->getUserByUsername('anezka.rihova');
        $this->loginAs($anezka);
        $this->assertFalse($this->accessService->computeAccess($room, $anezka));
    }

    public function test_ongoing_reservation_grants_visitor(): void
    {
        // r4 (Bachelor Thesis Consultation) has eva.bartosova as visitor
        $roomId = $this->getRoomId('TH:A', '9101');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $reservation = $this->getReservationByTitle('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservation, Reservation::STATUS_ACTIVE);

        $room = $this->em->find(Room::class, $roomId);
        $eva = $this->getUserByUsername('eva.bartosova');
        $this->loginAs($eva);
        $this->assertTrue($this->accessService->computeAccess($room, $eva));
    }

    public function test_ongoing_reservation_room_admin_always_granted(): void
    {
        // jan.dvorak is admin of TH:A-9101 via KSI group admin
        $roomId = $this->getRoomId('TH:A', '9101');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $reservation = $this->getReservationByTitle('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservation, Reservation::STATUS_ACTIVE);

        $room = $this->em->find(Room::class, $roomId);
        $dvorak = $this->getUserByUsername('jan.dvorak');
        $this->loginAs($dvorak);
        $this->assertTrue($this->accessService->computeAccess($room, $dvorak));
    }

    public function test_null_user_on_locked_room_returns_false(): void
    {
        $room = $this->em->find(Room::class, $this->getRoomId('T9', '301'));
        // No loginAs — token_storage remains unset/null
        static::getContainer()->get('security.token_storage')->setToken(null);
        $this->assertFalse($this->accessService->computeAccess($room, null));
    }

    public function test_null_user_on_unlocked_room_returns_false(): void
    {
        $roomId = $this->getRoomId('TK', 'BS');
        $room = $this->em->find(Room::class, $roomId);
        $this->forceRoomLockState($room, Room::LOCK_STATE_UNLOCKED);

        $room = $this->em->find(Room::class, $roomId);
        static::getContainer()->get('security.token_storage')->setToken(null);
        $this->assertFalse($this->accessService->computeAccess($room, null));
    }
}
