<?php

namespace App\Library\Environment;

use App\Entity\Environment;
use App\Library\Configuration\EnvironmentConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct(Environment $environment)
    {
        $config = Yaml::parse($environment->getConfig());

        $processor = new Processor();
        $environmentConfiguration = new EnvironmentConfiguration();

        $this->config = $processor->processConfiguration(
            $environmentConfiguration,
            [$config]
        );
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getBuildsToKeep(): int
    {
        return $this->config['buildsToKeep'];
    }

    public function getReleasesToKeep(): int
    {
        return $this->config['release']['releasesToKeep'];
    }
}
