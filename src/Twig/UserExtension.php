<?php

namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('user_role', [$this, 'friendlyUserRoles']),
        ];
    }

    public function friendlyUserRoles(User $user): string
    {
        $roles = [];
        foreach ($user->getRoles() as $role) {
            switch ($role) {
                case User::ROLE_USER:
                    $roles[] = 'User';
                    break;
                case User::ROLE_ADMINISTRATOR:
                    $roles[] = 'Administrator';
                    break;
            }
        }

        sort($roles);
        return implode(', ', $roles);
    }

}