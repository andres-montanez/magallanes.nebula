<?php

namespace App\Library\SSH;

final class Key
{
    public function __construct(private string $private, private string $public)
    {
    }

    public function getPrivate(): string
    {
        return $this->private;
    }

    public function getPublic(): string
    {
        return $this->public;
    }
}
