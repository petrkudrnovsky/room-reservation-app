<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Filter\AppUserFilterCriteria;

interface AppUserManagerInterface
{
    public function saveToDatabase(AppUser $appUser): AppUser;

    public function removeFromDatabase(AppUser $appUser): void;

    public function getAppUserById(int $appUserId): AppUser;

    public function getAppUserByUsername(string $username): AppUser;

    public function findAppUsersByFilters(AppUserFilterCriteria $criteria): array;

    public function isUniqueUsername(?string $username, ?int $userId): bool;

    public function addMemberUserGroups(?array $groups, AppUser $appUser): void;

    public function addAdminUserGroups(?array $groups, AppUser $appUser): void;

    public function addMemberRooms(?array $rooms, AppUser $appUser): void;

    public function addAdminRooms(?array $rooms, AppUser $appUser): void;
}
