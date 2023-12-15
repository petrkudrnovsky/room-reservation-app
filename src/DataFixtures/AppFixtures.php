<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    )
    {}

    public function load(ObjectManager $manager)
    {
        $appAdmin = new AppUser();
        $appAdmin->setUsername('admin');
        $appAdmin->setPassword($this->hasher->hashPassword($appAdmin, 'admin'));
        $appAdmin->setFirstName('Super');
        $appAdmin->setSecondName('Admin');

        $appAdmin->addRole('ROLE_USER');
        $appAdmin->addRole('ROLE_SUPER_ADMIN');

        $manager->persist($appAdmin);
        $manager->flush();
    }
}
