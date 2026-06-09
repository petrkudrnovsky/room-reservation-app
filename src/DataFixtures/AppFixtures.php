<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Building;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const NUMBER_OF_USERS = 2;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    )
    {}

    public function load(ObjectManager $manager)
    {
        $this->loadSuperAdmin($manager);
        $this->loadBuildings($manager);
        $this->loadUsers($manager);

        $manager->flush();
    }

    public function loadSuperAdmin(ObjectManager $manager)
    {
        $appAdmin = new AppUser();
        $appAdmin->setUsername('admin');
        $appAdmin->setPassword($this->hasher->hashPassword($appAdmin, 'admin'));
        $appAdmin->setFirstName('Super');
        $appAdmin->setSecondName('Admin');

        $appAdmin->addRole('ROLE_USER');
        $appAdmin->addRole('ROLE_SUPER_ADMIN');

        $manager->persist($appAdmin);
    }

    public function loadUsers(ObjectManager $manager)
    {
        for($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $appUser = new AppUser();
            $appUser->setUsername('user' . $i);
            $appUser->setPassword($this->hasher->hashPassword($appUser, 'user' . $i));
            $appUser->setFirstName('User');
            $appUser->setSecondName($i);

            $appUser->addRole('ROLE_USER');

            $manager->persist($appUser);
        }
    }

    public function loadBuildings(ObjectManager $manager)
    {
        $buildingNamesAndCodes = [
            'TH:A' => 'Building A - Faculty of Civil Engineering',
            'TH:D' => 'Building D - Faculty of Civil Engineering',
            'T9' => 'New Building ČVUT',
            'TK' => 'National Technical Library',
        ];

        foreach ($buildingNamesAndCodes as $code => $name) {
            $building = new Building();
            $building->setCode($code);
            $building->setName($name);

            $manager->persist($building);
        }
    }
}
