<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Building;
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
        $this->loadSuperAdmin($manager);
        $this->loadBuildings($manager);

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

    public function loadBuildings(ObjectManager $manager)
    {
        $buildingNamesAndCodes = [
            'TH:A' => 'Budova A - Fakulta Stavební',
            'TH:D' => 'Budova D - Fakulta Stavební',
            'T9' => 'Nová budova ČVUT',
            'TK' => 'Národní technická knihovna',
        ];

        foreach ($buildingNamesAndCodes as $code => $name) {
            $building = new Building();
            $building->setCode($code);
            $building->setName($name);

            $manager->persist($building);
        }
    }
}
