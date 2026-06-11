<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Filter\AppUserFilterCriteria;
use App\Repository\AppUserRepository;
use App\Repository\GroupRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AppUserManager implements AppUserManagerInterface
{
    public function __construct(
        public EntityManagerInterface $em,
        public AppUserRepository $userRepository,
        private readonly GroupRepository $groupRepository,
        private readonly RoomRepository $roomRepository,
    ) {}

    public function saveToDatabase(AppUser $appUser): AppUser
    {
        $this->em->persist($appUser);
        $this->em->flush();
        return $appUser;
    }

    public function removeFromDatabase(AppUser $appUser): void
    {
        // if there are any reservations, remove them first
        $reservations = $appUser->getReservations();
        foreach ($reservations as $reservation) {
            $this->em->remove($reservation);
        }
        $reservations = $appUser->getApprovedReservations();
        foreach ($reservations as $reservation) {
            $this->em->remove($reservation);
        }
        $this->em->remove($appUser);
        $this->em->flush();
    }

    public function getAppUserById(int $appUserId): AppUser
    {
        $appUser = $this->userRepository->find($appUserId);
        if (!$appUser) {
            throw new NotFoundHttpException("User with ID $appUserId not found.");
        }
        return $appUser;
    }

    public function getAppUserByUsername(string $username): AppUser
    {
        $appUser = $this->userRepository->findOneBy(['username' => $username]);
        if (!$appUser) {
            throw new NotFoundHttpException("User with username $username not found.");
        }
        return $appUser;
    }

    public function findAppUsersByFilters(AppUserFilterCriteria $criteria): array
    {
        $qb = $this->userRepository->createQueryBuilder('a');

        if ($criteria->username) {
            $qb->andWhere('LOWER(a.username) = :username')
                ->setParameter('username', strtolower($criteria->username));
        }

        if ($criteria->name) {
            $nameParts = explode(' ', strtolower($criteria->name));
            $qb->andWhere('(LOWER(a.firstName) LIKE :part1 OR LOWER(a.secondName) LIKE :part1)')
                ->setParameter('part1', '%' . $nameParts[0] . '%');

            if (isset($nameParts[1])) {
                $qb->andWhere('(LOWER(a.firstName) LIKE :part2 OR LOWER(a.secondName) LIKE :part2)')
                    ->setParameter('part2', '%' . $nameParts[1] . '%');
            }
        }

        if ($criteria->email) {
            $qb->andWhere('LOWER(a.email) = :email')
                ->setParameter('email', strtolower($criteria->email));
        }

        if ($criteria->phone) {
            $qb->andWhere('a.phone = :phone')
                ->setParameter('phone', $criteria->phone);
        }

        return $qb->getQuery()->getResult();
    }

    public function isUniqueUsername(?string $username, ?int $userId): bool
    {
        $appUser = $this->userRepository->findOneBy(['username' => $username]);
        if ($appUser !== null && $appUser->getId() === $userId) {
            return true;
        }
        return $appUser === null;
    }

    /**
     * @throws Exception
     */
    public function addMemberUserGroups(?array $groups, AppUser $appUser): void
    {
        if (!$groups) {
            return;
        }
        foreach ($groups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                $appUser->addMemberGroup($group);
            } else {
                throw new Exception('Group not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addAdminUserGroups(?array $groups, AppUser $appUser): void
    {
        if (!$groups) {
            return;
        }
        foreach ($groups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                $appUser->addAdminGroup($group);
            } else {
                throw new Exception('Group not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addMemberRooms(?array $rooms, AppUser $appUser): void
    {
        if (!$rooms) {
            return;
        }
        foreach ($rooms as $roomId) {
            $room = $this->roomRepository->find($roomId);
            if ($room) {
                $appUser->addMemberRoom($room);
            } else {
                throw new Exception('Room not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addAdminRooms(?array $rooms, AppUser $appUser): void
    {
        if (!$rooms) {
            return;
        }
        foreach ($rooms as $roomId) {
            $room = $this->roomRepository->find($roomId);
            if ($room) {
                $appUser->addAdminRoom($room);
            } else {
                throw new Exception('Room not found');
            }
        }
    }
}
