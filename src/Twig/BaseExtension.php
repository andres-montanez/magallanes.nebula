<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BaseExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('menu_active', [$this, 'menuActive']),
        ];
    }

    public function menuActive(string $path, string $currentPath): string
    {
        if (strpos($currentPath, $path) === 0) {
            return 'active';
        }

        return '';
    }

}
