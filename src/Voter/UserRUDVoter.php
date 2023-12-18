<?php

namespace App\Voter;

use App\Entity\AppUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserRUDVoter extends Voter
{
    const VIEW = 'user_detail_view';
    const EDIT = 'user_edit';
    const DELETE = 'user_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof AppUser && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if(!$currentUser instanceof AppUser) {
            return false;
        }

        /** @var AppUser $accessedUser */
        $accessedUser = $subject;

        if($this->isCurrentUser($accessedUser, $currentUser)) {
            return true;
        }

        return match($attribute) {
            self::VIEW => $this->canView($currentUser),
            self::EDIT => $this->canEdit($currentUser),
            self::DELETE => $this->canDelete($currentUser),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };
    }

    private function isCurrentUser(AppUser $accessedUser, AppUser $currentUser): bool
    {
        return $accessedUser->getId() == $currentUser->getId();
    }

    private function canView(AppUser $currentUser): bool
    {
        $userRoles = $currentUser->getRoles();
        $allowedRoles = ['ROLE_ROOM_MANAGER', 'ROLE_GROUP_MANAGER', 'ROLE_SUPER_ADMIN'];

        if (array_intersect($userRoles, $allowedRoles)) {
            return true;
        }
        return false;
    }

    private function canEdit(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    private function canDelete(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }
}