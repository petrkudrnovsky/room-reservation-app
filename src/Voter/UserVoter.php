<?php

namespace App\Voter;

use App\Entity\AppUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const VIEW_INDEX = 'user_index_view';
    const VIEW_DETAIL = 'user_detail_view';
    const CREATE = 'user_create';
    const EDIT = 'user_edit';
    const DELETE = 'user_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW_INDEX, self::VIEW_DETAIL, self::CREATE, self::EDIT, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if(!$currentUser instanceof AppUser) {
            return false;
        }

        // check because of the INDEX_VIEW and CREATE, where the subject is null, however we still want to manage access
        if(!($subject instanceof AppUser) && $attribute !== self::VIEW_INDEX && $attribute !== self::CREATE) {
            return false;
        }
        if($attribute === self::VIEW_INDEX) {
            return $this->canViewIndex($currentUser);
        }
        if($attribute === self::CREATE) {
            return $this->canCreate($currentUser);
        }

        /** @var AppUser $accessedUser */
        $accessedUser = $subject;
        return match($attribute) {
            self::VIEW_DETAIL => $this->canViewDetail($currentUser, $accessedUser),
            self::EDIT => $this->canEdit($currentUser, $accessedUser),
            self::DELETE => $this->canDelete($currentUser),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };
    }

    private function isCurrentUser(AppUser $accessedUser, AppUser $currentUser): bool
    {
        return $accessedUser->getId() == $currentUser->getId();
    }

    private function canViewIndex(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    private function canViewDetail(AppUser $currentUser, AppUser $accessedUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) || $this->isCurrentUser($accessedUser, $currentUser);
    }

    private function canCreate(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    private function canEdit(AppUser $currentUser, AppUser $accessedUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) || $this->isCurrentUser($accessedUser, $currentUser);
    }

    private function canDelete(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }
}