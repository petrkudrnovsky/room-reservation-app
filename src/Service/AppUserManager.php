<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Filter\AppUserFilterCriteria;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AppUserManager
{
    public function __construct(
        public EntityManagerInterface $em,
        public AppUserRepository $userRepository,
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
        if(!$appUser) {
            throw new NotFoundHttpException("User with ID $appUserId not found.");
        }

        return $appUser;
    }

    public function getAppUserByUsername(string $username): AppUser
    {
        $appUser = $this->userRepository->findOneBy(array('username' => $username));
        if(!$appUser) {
            throw new NotFoundHttpException("User with username $username not found.");
        }

        return $appUser;
    }

    public function findAppUsersByFilters(AppUserFilterCriteria $criteria): array {
        $qb = $this->userRepository->createQueryBuilder('a');

        if ($criteria->username) {
            $qb->andWhere('LOWER(a.username) = :username')
                ->setParameter('username', strtolower($criteria->username));
        }

        if ($criteria->name) {
            // Split the name into parts and convert to lowercase
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

    /**
     * @throws Exception
     */
    public function addGroupMembers(?array $members, Group $group): void
    {
        if ($members === null) {
            return;
        }
        foreach ($members as $memberId) {
            if (is_numeric($memberId)) {
                $member = $this->userRepository->find($memberId);
                if ($member) {
                    $group->addMember($member);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Member must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addRoomMembers(?array $members, Room $room): void
    {
        if ($members === null) {
            return;
        }
        foreach ($members as $memberId) {
            if (is_numeric($memberId)) {
                $member = $this->userRepository->find($memberId);
                if ($member) {
                    $room->addMember($member);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Member must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addGroupAdmins(?array $admins, Group $group): void
    {
        if ($admins === null) {
            return;
        }
        foreach ($admins as $adminId) {
            if (is_numeric($adminId)) {
                $admin = $this->userRepository->find($adminId);
                if ($admin) {
                    $group->addAdmin($admin);
                    $group->addMember($admin);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Admin must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addRoomAdmins(?array $admins, Room $room): void
    {
        if ($admins === null) {
            return;
        }
        foreach ($admins as $adminId) {
            if (is_numeric($adminId)) {
                $admin = $this->userRepository->find($adminId);
                if ($admin) {
                    $room->addAdmin($admin);
                    $room->addMember($admin);
                } else {
                    throw new Exception('User not found');
                }
            } else {
                throw new Exception('Admin must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addApprovedReservation(?string $approvedBy, Reservation $reservation): void
    {
        if ($approvedBy) {
            $appUser = $this->userRepository->find($approvedBy);
            if ($appUser) {
                $reservation->setApprovedBy($appUser);
            } else {
                throw new Exception('User not found');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addReservedReservation(?string $reservedFor, Reservation $reservation): void
    {
        if ($reservedFor) {
            $appUser = $this->userRepository->find($reservedFor);
            if ($appUser) {
                $reservation->setReservedFor($appUser);
            } else {
                throw new Exception('User not found');
            }
        }
    }

    public function isUniqueUsername(?string $username, ?int $userId): bool
    {
        $appUser = $this->userRepository->findOneBy(array('username' => $username));
        if ($appUser !== null && $appUser->getId() === $userId) {
            return true;
        }
        return $appUser === null;
    }
}