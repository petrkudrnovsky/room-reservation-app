<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
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

    public function findAppUsersByFilters(?string $username, ?string $name, ?string $email, ?string $phone): array {
        $qb = $this->userRepository->createQueryBuilder('a');

        if ($username) {
            $qb->andWhere('LOWER(a.username) = :username')
                ->setParameter('username', strtolower($username));
        }

        if ($name) {
            // Split the name into parts and convert to lowercase
            $nameParts = explode(' ', strtolower($name));
            $qb->andWhere('(LOWER(a.firstName) LIKE :part1 OR LOWER(a.secondName) LIKE :part1)')
                ->setParameter('part1', '%' . $nameParts[0] . '%');

            if (isset($nameParts[1])) {
                $qb->andWhere('(LOWER(a.firstName) LIKE :part2 OR LOWER(a.secondName) LIKE :part2)')
                    ->setParameter('part2', '%' . $nameParts[1] . '%');
            }
        }

        if ($email) {
            $qb->andWhere('LOWER(a.email) = :email')
                ->setParameter('email', strtolower($email));
        }

        if ($phone) {
            $qb->andWhere('a.phone = :phone')
                ->setParameter('phone', $phone);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addMembers(?array $members, ?Group $group = null, ?Room $room = null): void
    {
        if ($members === null) {
            return;
        }
        foreach ($members as $memberId) {
            if (is_numeric($memberId)) {
                $member = $this->userRepository->find($memberId);
                if ($member) {
                    $group !== null ? $group->addMember($member) : $room->addMember($member);
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
    public function addAdmins(?array $admins, ?Group $group = null, ?Room $room = null): void
    {
        if ($admins === null) {
            return;
        }
        foreach ($admins as $adminId) {
            if (is_numeric($adminId)) {
                $admin = $this->userRepository->find($adminId);
                if ($admin) {
                    $group !== null ? $group->addAdmin($admin) : $room->addAdmin($admin);
                    $group !== null ? $group->addMember($admin) : $room->addMember($admin);
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

    public function isUniqueUsername(?string $username): bool
    {
        $appUser = $this->userRepository->findOneBy(array('username' => $username));
        return $appUser === null;
    }
}