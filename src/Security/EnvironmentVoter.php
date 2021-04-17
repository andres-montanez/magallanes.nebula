<?php

namespace App\Security;

use App\Entity\Environment;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EnvironmentVoter extends Voter
{
    protected Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (!$subject instanceof Environment) {
            return false;
        }

        if (!in_array($attribute, ['builds', 'deploy'])) {
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

        /** @var Environment $environment */
        $environment = $subject;

        switch ($attribute) {
            case 'builds':
            case 'deploy':
                if ($user->getGroups()->contains($environment->getGroup())) {
                    return true;
                }

                return false;
        }

        throw new \LogicException('This code should not be reached!');
    }
}