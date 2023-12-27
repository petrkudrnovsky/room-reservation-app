<?php

namespace App\Voter;

use App\Entity\AppUser;
use App\Entity\Group;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GroupVoter extends Voter
{
    const VIEW_INDEX = 'group_index_view';
    const VIEW_DETAIL = 'group_detail_view';
    const CREATE = 'group_create';
    const EDIT = 'group_edit';
    const DELETE = 'group_delete';

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

        if(!($subject instanceof Group) && $attribute !== self::VIEW_INDEX && $attribute !== self::CREATE) {
            return false;
        }

        if($attribute === self::VIEW_INDEX) {
            return $this->canViewIndex($currentUser);
        }
        if($attribute === self::CREATE) {
            return $this->canCreate($currentUser);
        }

        /** @var Group $accessedGroup */
        $accessedGroup = $subject;
        return match($attribute) {
            self::VIEW_DETAIL => $this->canViewDetail($currentUser, $accessedGroup),
            self::EDIT => $this->canEdit($currentUser, $accessedGroup),
            self::DELETE => $this->canDelete($currentUser),
            default => throw new \LogicException('This is not valid attribute for this Voter')
        };

    }

    private function canViewIndex(AppUser $currentUser): bool
    {
        return in_array('ROLE_USER', $currentUser->getRoles());
    }

    private function canViewDetail(AppUser $currentUser, Group $accessedGroup): bool
    {
        if(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            return true;
        }
        $memberGroups = $currentUser->getMemberGroups();
        $adminGroups = $currentUser->getAdminGroups();
        if($memberGroups->contains($accessedGroup) || $adminGroups->contains($accessedGroup)) {
            return true;
        }
        return false;
    }

    private function canCreate(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }

    private function canEdit(AppUser $currentUser, Group $accessedGroup): bool
    {
        if(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())) {
            return true;
        }
        $adminGroups = $currentUser->getAdminGroups();
        if($adminGroups->contains($accessedGroup)) {
            return true;
        }
        return false;
    }

    private function canDelete(AppUser $currentUser): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());
    }
}