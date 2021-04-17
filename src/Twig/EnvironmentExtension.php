<?php

namespace App\Twig;

use App\Entity\Build;
use App\Entity\Environment;
use App\Service\EnvironmentService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EnvironmentExtension extends AbstractExtension
{
    protected EnvironmentService $environmentService;

    public function __construct(EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('env_get_last_successful', [$this, 'getLastSuccessful']),
            new TwigFunction('env_get_last_failed', [$this, 'getLastFailed']),
            new TwigFunction('env_get_running', [$this, 'getRunning']),
            new TwigFunction('get_time_ago', [$this, 'getTimeAgo']),
            new TwigFunction('get_repository_name', [$this, 'getRepositoryName']),
        ];
    }

    public function getLastSuccessful(Environment $environment): ?Build
    {
        $build = $this->environmentService->getLastSuccessful($environment);
        if ($build instanceof Build) {
            return $build;
        }

        return null;
    }

    public function getLastFailed(Environment $environment): ?Build
    {
        $build = $this->environmentService->getLastFailed($environment);
        if ($build instanceof Build) {
            return $build;
        }

        return null;
    }

    public function getRunning(Environment $environment): ?Build
    {
        $build = $this->environmentService->getRunning($environment);
        if ($build instanceof Build) {
            return $build;
        }

        return null;
    }

    public function getTimeAgo(\DateTimeInterface $dateTime): string
    {
        $now = new \DateTimeImmutable('now');
        $diff = $now->diff($dateTime);

        if ($diff->days == 0) {
            return 'Today';
        }

        if ($diff->days == 1) {
            return 'Yesterday';
        }

        if ($diff->days < 14) {
            return sprintf('%d days ago', $diff->days);
        }

        if ($diff->days < 31) {
            return sprintf('%d weeks ago', round($diff->days / 7));
        }

        if ($diff->days < 360) {
            return sprintf('%d months ago', round($diff->days / 30));
        }

        return $dateTime->format('Y-m-d');
    }

    public function getRepositoryName(string $repository): string
    {
        $repositoryPath = explode('/', $repository);
        if (isset($repositoryPath[1])) {
            return rtrim($repositoryPath[1], '.git');
        }

        return $repository;
    }
}