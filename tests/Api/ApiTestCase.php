<?php

namespace App\Tests\Api;

use App\DataFixtures\AppFixtures;
use App\Entity\AppUser;
use App\Entity\Building;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(AppFixtures::class));
        (new ORMExecutor($this->em, new ORMPurger()))->execute($loader->getFixtures());
    }

    protected function getTokenFor(string $username, string $password = 'password'): string
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['token'];
    }

    protected function makeAuthHeaders(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    protected function jsonHeaders(): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    protected function assertJsonResponse(int $expectedStatus): array
    {
        $response = $this->client->getResponse();
        $this->assertSame(
            $expectedStatus,
            $response->getStatusCode(),
            'Unexpected status. Body: ' . $response->getContent()
        );
        $this->assertJson($response->getContent());
        return json_decode($response->getContent(), true);
    }

    protected function assertStatusCode(int $expectedStatus): void
    {
        $this->assertSame(
            $expectedStatus,
            $this->client->getResponse()->getStatusCode(),
            'Unexpected status. Body: ' . $this->client->getResponse()->getContent()
        );
    }

    protected function getRoomId(string $buildingCode, string $roomCode): int
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

    protected function getBuildingId(string $code): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('b.id')
            ->from(Building::class, 'b')
            ->where('b.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function getGroupId(string $namePart): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('g.id')
            ->from(Group::class, 'g')
            ->where('g.name LIKE :name')
            ->setParameter('name', '%' . $namePart . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function getUserId(string $username): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(AppUser::class, 'u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function getReservationId(string $title): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('r.id')
            ->from(Reservation::class, 'r')
            ->where('r.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function forceReservationStatus(int $reservationId, string $status): void
    {
        $reservation = $this->em->find(Reservation::class, $reservationId);
        $reservation->setStatus($status);
        $this->em->flush();
        $this->em->clear();
    }

    protected function forceRoomLockState(int $roomId, string $lockState): void
    {
        $room = $this->em->find(Room::class, $roomId);
        $room->setLockState($lockState);
        $this->em->flush();
        $this->em->clear();
    }

    protected function futureDatetime(string $modifier = '+2 days', string $time = '10:00'): string
    {
        return (new \DateTime($modifier))->setTime(...array_map('intval', explode(':', $time)))->format('Y-m-d H:i:s');
    }
}
