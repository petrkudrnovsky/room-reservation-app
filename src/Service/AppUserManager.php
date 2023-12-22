<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $this->em->remove($appUser);
        $this->em->flush();
    }

    public function getCurrentUser()
    {
    }

    public function getAppUserById(int $appUserId): AppUser
    {
        $appUser = $this->userRepository->find($appUserId);
        if(!$appUser) {
            throw new NotFoundHttpException("User with ID $appUserId not found.");
        }

        return $appUser;
    }

    public function getAppUserByUsername(?string $username): array
    {
        if($username) {
            $appUser = $this->userRepository->findOneBy(array('username' => $username));
            if(!$appUser) {
                throw new NotFoundHttpException("User with username $username not found.");
            }
        } else {
            $appUser = $this->userRepository->findAll();
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

}