<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Building;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $buildings = $this->loadBuildings($manager);
        $rooms     = $this->loadRooms($manager, $buildings);
        $groups    = $this->loadGroups($manager, $rooms);
        $users     = $this->loadUsers($manager, $rooms, $groups);
        $this->loadReservations($manager, $rooms, $users);

        $manager->flush();
    }

    // -------------------------------------------------------------------------
    // Buildings
    // -------------------------------------------------------------------------

    /** @return Building[] keyed by code */
    private function loadBuildings(ObjectManager $manager): array
    {
        $data = [
            'TH:A' => 'Building A – Faculty of Civil Engineering',
            'TH:D' => 'Building D – Faculty of Civil Engineering',
            'T9'   => 'New ČVUT Building – Faculty of Information Technology',
            'TK'   => 'National Technical Library',
        ];

        $buildings = [];
        foreach ($data as $code => $name) {
            $b = new Building();
            $b->setCode($code);
            $b->setName($name);
            $manager->persist($b);
            $buildings[$code] = $b;
        }

        return $buildings;
    }

    // -------------------------------------------------------------------------
    // Rooms
    // -------------------------------------------------------------------------

    /** @return Room[] keyed by display code (e.g. 'TK:BS', 'T9:301', 'TH:A-9105') */
    private function loadRooms(ObjectManager $manager, array $buildings): array
    {
        // [buildingKey, roomCode, name, isPrivate]
        $data = [
            // TK – library public study / lecture rooms
            ['TK',   'BS',   'Study Room BS',               false],
            ['TK',   'PU1',  'Lecture Hall PU1',             true],
            ['TK',   'PU2',  'Lecture Hall PU2',             true],

            // T9 – 3rd floor FIT
            ['T9',   '300',  'Lecture Hall T9:300',          true],
            ['T9',   '301',  'Computer Lab T9:301',           true],
            ['T9',   '302',  'Computer Lab T9:302',           true],
            ['T9',   '303',  'Seminar Room T9:303',           true],

            // TH:A – floors 9–14
            ['TH:A', '9101', 'Lecture Hall TH:A-9101',        true],
            ['TH:A', '9105', 'Seminar Room TH:A-9105',        true],
            ['TH:A', '1046', 'Computer Lab TH:A-1046',        true],
            ['TH:A', '1154', 'Seminar Room TH:A-1154',        true],
            ['TH:A', '1158', 'Computer Lab TH:A-1158',        true],
            ['TH:A', '1247', 'Computer Lab TH:A-1247',        true],
            ['TH:A', '1345', 'Seminar Room TH:A-1345',        true],
            ['TH:A', '1442', 'Computer Lab TH:A-1442',        true],

            // TH:D
            ['TH:D', '1122', 'Lab TH:D-1122', true],
        ];

        $rooms = [];
        foreach ($data as [$buildingKey, $code, $name, $isPrivate]) {
            $room = new Room();
            $room->setCode($code);
            $room->setName($name);
            $room->setIsPrivate($isPrivate);
            $room->setBuilding($buildings[$buildingKey]);
            $manager->persist($room);
            $rooms[$room->getCodeName()] = $room;
        }

        return $rooms;
    }

    // -------------------------------------------------------------------------
    // Groups
    // -------------------------------------------------------------------------

    /** @return Group[] keyed by abbreviation */
    private function loadGroups(ObjectManager $manager, array $rooms): array
    {
        // Each entry: [abbreviation, full name, owned room display codes]
        $data = [
            'KSI' => [
                'name'  => 'Department of Software Engineering',
                'rooms' => ['TH:A-9101', 'TH:A-9105', 'TH:A-1345'],
            ],
            'KIB' => [
                'name'  => 'Department of Information Security',
                'rooms' => ['TH:A-1046', 'TH:A-1154', 'TH:A-1158'],
            ],
            'KPS' => [
                'name'  => 'Department of Computer Systems',
                'rooms' => ['T9:300', 'T9:301', 'TH:D-1122'],
            ],
            'KAM' => [
                'name'  => 'Department of Applied Mathematics',
                'rooms' => ['T9:302', 'T9:303', 'TH:A-1247', 'TH:A-1442'],
            ],
        ];

        $groups = [];
        foreach ($data as $abbr => $config) {
            $group = new Group();
            $group->setName($config['name']);

            // Room owns the owningGroups side – call addOwningGroup on Room
            foreach ($config['rooms'] as $roomKey) {
                $rooms[$roomKey]->addOwningGroup($group);
            }

            $manager->persist($group);
            $groups[$abbr] = $group;
        }

        return $groups;
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /** @return AppUser[] keyed by username */
    private function loadUsers(ObjectManager $manager, array $rooms, array $groups): array
    {
        $users = [];

        // Super admin
        $users['admin'] = $this->makeUser(
            $manager,
            username: 'admin',
            password: 'admin',
            firstName: 'Super',
            lastName: 'Admin',
            superAdmin: true,
        );

        // ── Department heads (group admin + room admin of their group's rooms) ─────

        $users['jan.dvorak'] = $this->makeUser(
            $manager,
            username: 'jan.dvorak',
            password: 'password',
            firstName: 'Jan',
            lastName: 'Dvořák',
            email: 'jan.dvorak@fit.cvut.cz',
            adminGroups: [$groups['KSI']],
            memberGroups: [$groups['KSI']],
            adminRooms: [$rooms['TH:A-9101'], $rooms['TH:A-9105'], $rooms['TH:A-1345']],
            memberRooms: [$rooms['TH:A-9101'], $rooms['TH:A-9105'], $rooms['TH:A-1345']],
        );

        $users['petra.novakova'] = $this->makeUser(
            $manager,
            username: 'petra.novakova',
            password: 'password',
            firstName: 'Petra',
            lastName: 'Nováková',
            email: 'petra.novakova@fit.cvut.cz',
            adminGroups: [$groups['KIB']],
            memberGroups: [$groups['KIB']],
            adminRooms: [$rooms['TH:A-1046'], $rooms['TH:A-1154'], $rooms['TH:A-1158']],
            memberRooms: [$rooms['TH:A-1046'], $rooms['TH:A-1154'], $rooms['TH:A-1158']],
        );

        $users['martin.horak'] = $this->makeUser(
            $manager,
            username: 'martin.horak',
            password: 'password',
            firstName: 'Martin',
            lastName: 'Horák',
            email: 'martin.horak@fit.cvut.cz',
            adminGroups: [$groups['KPS']],
            memberGroups: [$groups['KPS']],
            adminRooms: [$rooms['T9:300'], $rooms['T9:301'], $rooms['TH:D-1122']],
            memberRooms: [$rooms['T9:300'], $rooms['T9:301'], $rooms['TH:D-1122']],
        );

        $users['alena.zahradnikova'] = $this->makeUser(
            $manager,
            username: 'alena.zahradnikova',
            password: 'password',
            firstName: 'Alena',
            lastName: 'Zahradníková',
            email: 'alena.zahradnikova@fit.cvut.cz',
            adminGroups: [$groups['KAM']],
            memberGroups: [$groups['KAM']],
            adminRooms: [$rooms['T9:302'], $rooms['T9:303'], $rooms['TH:A-1247'], $rooms['TH:A-1442']],
            memberRooms: [$rooms['T9:302'], $rooms['T9:303'], $rooms['TH:A-1247'], $rooms['TH:A-1442']],
        );

        // ── Lab / room managers (room admin, group member, not department head) ────

        $users['pavel.kratochvil'] = $this->makeUser(
            $manager,
            username: 'pavel.kratochvil',
            password: 'password',
            firstName: 'Pavel',
            lastName: 'Kratochvíl',
            email: 'pavel.kratochvil@fit.cvut.cz',
            memberGroups: [$groups['KSI']],
            adminRooms: [$rooms['TH:A-9101'], $rooms['TH:A-9105']],
            memberRooms: [$rooms['TH:A-9101'], $rooms['TH:A-9105']],
        );

        $users['jana.kovarikova'] = $this->makeUser(
            $manager,
            username: 'jana.kovarikova',
            password: 'password',
            firstName: 'Jana',
            lastName: 'Kováříková',
            email: 'jana.kovarikova@fit.cvut.cz',
            memberGroups: [$groups['KIB']],
            adminRooms: [$rooms['TH:A-1158']],
            memberRooms: [$rooms['TH:A-1158']],
        );

        $users['tomas.blaha'] = $this->makeUser(
            $manager,
            username: 'tomas.blaha',
            password: 'password',
            firstName: 'Tomáš',
            lastName: 'Bláha',
            email: 'tomas.blaha@fit.cvut.cz',
            memberGroups: [$groups['KPS']],
            adminRooms: [$rooms['T9:301'], $rooms['TH:D-1122']],
            memberRooms: [$rooms['T9:301'], $rooms['TH:D-1122']],
        );

        // Library manager – not in any FIT group; manages TK rooms directly
        $users['roman.blazek'] = $this->makeUser(
            $manager,
            username: 'roman.blazek',
            password: 'password',
            firstName: 'Roman',
            lastName: 'Blažek',
            email: 'roman.blazek@cvut.cz',
            adminRooms: [$rooms['TK:BS'], $rooms['TK:PU1'], $rooms['TK:PU2']],
            memberRooms: [$rooms['TK:BS'], $rooms['TK:PU1'], $rooms['TK:PU2']],
        );

        // ── Regular academic staff (group members, no admin role) ────────────────

        $users['eva.bartosova'] = $this->makeUser(
            $manager,
            username: 'eva.bartosova',
            password: 'password',
            firstName: 'Eva',
            lastName: 'Bartošová',
            email: 'eva.bartosova@fit.cvut.cz',
            memberGroups: [$groups['KSI']],
            memberRooms: [$rooms['TH:A-9105']],
        );

        $users['ondrej.sedlacek'] = $this->makeUser(
            $manager,
            username: 'ondrej.sedlacek',
            password: 'password',
            firstName: 'Ondřej',
            lastName: 'Sedláček',
            email: 'ondrej.sedlacek@fit.cvut.cz',
            memberGroups: [$groups['KSI']],
        );

        $users['lucie.hruskova'] = $this->makeUser(
            $manager,
            username: 'lucie.hruskova',
            password: 'password',
            firstName: 'Lucie',
            lastName: 'Hrušková',
            email: 'lucie.hruskova@fit.cvut.cz',
            memberGroups: [$groups['KIB']],
            memberRooms: [$rooms['TH:A-1154']],
        );

        $users['michal.holub'] = $this->makeUser(
            $manager,
            username: 'michal.holub',
            password: 'password',
            firstName: 'Michal',
            lastName: 'Holub',
            email: 'michal.holub@fit.cvut.cz',
            memberGroups: [$groups['KPS']],
        );

        $users['katerina.novakova'] = $this->makeUser(
            $manager,
            username: 'katerina.novakova',
            password: 'password',
            firstName: 'Kateřina',
            lastName: 'Nováková',
            email: 'katerina.novakova@fit.cvut.cz',
            memberGroups: [$groups['KAM']],
        );

        // ── Students (ROLE_USER, some with direct room membership) ──────────────

        $users['jakub.simunek'] = $this->makeUser(
            $manager,
            username: 'jakub.simunek',
            password: 'password',
            firstName: 'Jakub',
            lastName: 'Šimůnek',
            memberRooms: [$rooms['T9:301']],
        );

        $users['tereza.krejci'] = $this->makeUser(
            $manager,
            username: 'tereza.krejci',
            password: 'password',
            firstName: 'Tereza',
            lastName: 'Krejčí',
            memberRooms: [$rooms['T9:302']],
        );

        $users['matej.prochazka'] = $this->makeUser(
            $manager,
            username: 'matej.prochazka',
            password: 'password',
            firstName: 'Matěj',
            lastName: 'Procházka',
            memberRooms: [$rooms['TH:A-1046']],
        );

        $users['anezka.rihova'] = $this->makeUser(
            $manager,
            username: 'anezka.rihova',
            password: 'password',
            firstName: 'Anežka',
            lastName: 'Říhová',
        );

        $users['david.mares'] = $this->makeUser(
            $manager,
            username: 'david.mares',
            password: 'password',
            firstName: 'David',
            lastName: 'Mareš',
        );

        $users['simona.hlavacova'] = $this->makeUser(
            $manager,
            username: 'simona.hlavacova',
            password: 'password',
            firstName: 'Simona',
            lastName: 'Hlaváčová',
            memberRooms: [$rooms['TK:PU1']],
        );

        return $users;
    }

    // -------------------------------------------------------------------------
    // Reservations
    // -------------------------------------------------------------------------

    private function loadReservations(ObjectManager $manager, array $rooms, array $users): void
    {
        $now = new \DateTime();

        // 1. PENDING – Jakub Šimůnek requesting Computer Lab T9:301 next week
        $r1 = new Reservation();
        $r1->setTitle('PA1 Practice Session');
        $r1->setDescription('Group exercise for the semester project.');
        $r1->setStartDatetime((clone $now)->modify('+7 days')->setTime(10, 0));
        $r1->setEndDatetime((clone $now)->modify('+7 days')->setTime(12, 0));
        $r1->setStatus(Reservation::STATUS_PENDING);
        $r1->setRoom($rooms['T9:301']);
        $r1->setReservedFor($users['jakub.simunek']);
        $manager->persist($r1);

        // 2. PENDING – Tereza Krejčí requesting T9:302 in two days
        $r2 = new Reservation();
        $r2->setTitle('Study Group – Databases');
        $r2->setDescription('Exam preparation for BI-DBS.');
        $r2->setStartDatetime((clone $now)->modify('+2 days')->setTime(14, 0));
        $r2->setEndDatetime((clone $now)->modify('+2 days')->setTime(16, 0));
        $r2->setStatus(Reservation::STATUS_PENDING);
        $r2->setRoom($rooms['T9:302']);
        $r2->setReservedFor($users['tereza.krejci']);
        $manager->persist($r2);

        // 3. APPROVED – Eva Bartošová has an approved reservation for TH:A-9105 tomorrow
        $r3 = new Reservation();
        $r3->setTitle('KSI Department Seminar');
        $r3->setDescription('Regular weekly seminar of the department.');
        $r3->setStartDatetime((clone $now)->modify('+1 day')->setTime(9, 0));
        $r3->setEndDatetime((clone $now)->modify('+1 day')->setTime(11, 0));
        $r3->setStatus(Reservation::STATUS_APPROVED);
        $r3->setRoom($rooms['TH:A-9105']);
        $r3->setReservedFor($users['eva.bartosova']);
        $r3->setApprovedBy($users['jan.dvorak']);
        $manager->persist($r3);

        // 4. APPROVED + ACTIVE – Ondřej Sedláček has an ongoing reservation (right now)
        $r4 = new Reservation();
        $r4->setTitle('Bachelor Thesis Consultation');
        $r4->setDescription('Thesis consultation with supervisor.');
        $r4->setStartDatetime((clone $now)->modify('-30 minutes'));
        $r4->setEndDatetime((clone $now)->modify('+90 minutes'));
        $r4->setStatus(Reservation::STATUS_APPROVED);
        $r4->setRoom($rooms['TH:A-9101']);
        $r4->setReservedFor($users['ondrej.sedlacek']);
        $r4->setApprovedBy($users['jan.dvorak']);
        $r4->addVisitor($users['eva.bartosova']);
        $manager->persist($r4);

        // 5. APPROVED – Michal Holub – T9:300 next week
        $r5 = new Reservation();
        $r5->setTitle('KPS Department Lecture');
        $r5->setStartDatetime((clone $now)->modify('+5 days')->setTime(13, 0));
        $r5->setEndDatetime((clone $now)->modify('+5 days')->setTime(15, 0));
        $r5->setStatus(Reservation::STATUS_APPROVED);
        $r5->setRoom($rooms['T9:300']);
        $r5->setReservedFor($users['michal.holub']);
        $r5->setApprovedBy($users['martin.horak']);
        $manager->persist($r5);

        // 6. REJECTED – David Mareš – TH:A-1046 (rejected: not a room member)
        $r6 = new Reservation();
        $r6->setTitle('Study Group');
        $r6->setDescription('Rejected – requester is not a member of this room.');
        $r6->setStartDatetime((clone $now)->modify('+3 days')->setTime(16, 0));
        $r6->setEndDatetime((clone $now)->modify('+3 days')->setTime(18, 0));
        $r6->setStatus(Reservation::STATUS_REJECTED);
        $r6->setRoom($rooms['TH:A-1046']);
        $r6->setReservedFor($users['david.mares']);
        $manager->persist($r6);

        // 7. APPROVED – Lucie Hrušková – TH:A-1154 – half-day workshop next week
        $r7 = new Reservation();
        $r7->setTitle('Workshop: Applied Cryptography');
        $r7->setDescription('Workshop for KIB students.');
        $r7->setStartDatetime((clone $now)->modify('+8 days')->setTime(9, 0));
        $r7->setEndDatetime((clone $now)->modify('+8 days')->setTime(13, 0));
        $r7->setStatus(Reservation::STATUS_APPROVED);
        $r7->setRoom($rooms['TH:A-1154']);
        $r7->setReservedFor($users['lucie.hruskova']);
        $r7->setApprovedBy($users['petra.novakova']);
        $r7->addVisitor($users['michal.holub']);
        $r7->addVisitor($users['tereza.krejci']);
        $manager->persist($r7);

        // 8. PENDING – Simona Hlaváčová – TK:PU1
        $r8 = new Reservation();
        $r8->setTitle('FIT Reading Club');
        $r8->setStartDatetime((clone $now)->modify('+4 days')->setTime(17, 0));
        $r8->setEndDatetime((clone $now)->modify('+4 days')->setTime(19, 0));
        $r8->setStatus(Reservation::STATUS_PENDING);
        $r8->setRoom($rooms['TK:PU1']);
        $r8->setReservedFor($users['simona.hlavacova']);
        $manager->persist($r8);
    }

    // -------------------------------------------------------------------------
    // Factory helper
    // -------------------------------------------------------------------------

    private function makeUser(
        ObjectManager $manager,
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        ?string $email = null,
        bool $superAdmin = false,
        array $adminGroups = [],
        array $memberGroups = [],
        array $adminRooms = [],
        array $memberRooms = [],
    ): AppUser {
        $user = new AppUser();
        $user->setUsername($username);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setFirstName($firstName);
        $user->setSecondName($lastName);
        if ($email !== null) {
            $user->setEmail($email);
        }
        if ($superAdmin) {
            $user->addRole('ROLE_SUPER_ADMIN');
        }

        // Group owns the members/admins side, so call addMember/addAdmin on the Group.
        foreach ($memberGroups as $group) {
            $group->addMember($user);
        }
        foreach ($adminGroups as $group) {
            $group->addAdmin($user);
        }

        // Room owns the members/admins side, so call addMember/addAdmin on the Room.
        foreach ($memberRooms as $room) {
            $room->addMember($user);
        }
        foreach ($adminRooms as $room) {
            $room->addAdmin($user);
        }

        $manager->persist($user);
        return $user;
    }
}
