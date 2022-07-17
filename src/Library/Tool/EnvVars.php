<?php

namespace App\Library\Tool;

class EnvVars
{
    /**
     * @param array<string, string> $vars
     */
    public static function replace(string $definition, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $definition = str_replace(sprintf('${%s}', $key), $value, $definition);
        }

        return $definition;
    }
}
