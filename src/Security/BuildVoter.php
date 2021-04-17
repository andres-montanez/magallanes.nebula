<?php

namespace App\Security;

use App\Entity\Build;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BuildVoter extends Voter
{
    protected Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!$subject instanceof Build) {
            return false;
        }

        if (!in_array($attribute, ['view', 'delete', 'rollback'])) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted(User::ROLE_ADMINISTRATOR)) {
            return true;
        }

        /** @var Build $build */
        $build = $subject;

        switch ($attribute) {
            case 'view':
            case 'delete':
            case 'rollback':
                if ($user->getGroups()->contains($build->getEnvironment()->getGroup())) {
                    return true;
                }

                return false;
        }

        throw new \LogicException('This code should not be reached!');
    }
}