<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Room;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class GroupManager
{
    public function __construct(
        public EntityManagerInterface $em,
        public GroupRepository $groupRepository,
    ) {}

    public function saveToDatabase(Group $group): Group
    {
        $this->em->persist($group);
        $this->em->flush();
        return $group;
    }

    public function removeFromDatabase(Group $group): void
    {
        $this->em->remove($group);
        $this->em->flush();
    }

    public function findGroupsByName(?string $name): array {
        $qb = $this->groupRepository->createQueryBuilder('a');

        if ($name) {
            $pattern = '%' . strtolower($name) . '%';
            $qb->andWhere('LOWER(a.name) LIKE :pattern')
                ->setParameter('pattern', $pattern);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function addOwningGroups(?array $owningGroups, Room $room): void {
        if (!$owningGroups) {
            return;
        }
        foreach ($owningGroups as $groupId) {
            if (is_numeric($groupId)){
                $group = $this->groupRepository->find($groupId);
                if ($group) {
                    $room->addOwningGroup($group);
                } else {
                    throw new Exception('Group not found');
                }
            } else {
                throw new Exception('Group ID must be an integer value');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function addUserGroups(?array $memberGroups, AppUser $appUser, bool $isAdmin): void
    {
        foreach ($memberGroups as $groupId) {
            $group = $this->groupRepository->find($groupId);
            if ($group) {
                if ($isAdmin) {
                    $appUser->addAdminGroup($group);
                } else {
                    $appUser->addMemberGroup($group);
                }
            } else {
                throw new Exception('Group not found');
            }
        }
    }
}