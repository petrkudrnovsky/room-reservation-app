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
}