<?php

namespace App\Service;

use App\Entity\Build;
use App\Entity\Project;
use App\Entity\Environment;
use Doctrine\ORM\EntityManagerInterface;

class EnvironmentService
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(Environment $environment)
    {
        $this->entityManager->persist($environment);
        $this->entityManager->flush();
    }

    public function update(Environment $environment)
    {
        $this->entityManager->flush();
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(Project $project): array
    {
        return $this->entityManager->getRepository(Environment::class)->findBy(
            ['project' => $project],
            ['name' => 'ASC']
        );
    }

    /**
     * @return Build[]
     */
    public function getBuilds(Environment $environment): array
    {
        return $this->entityManager->getRepository(Build::class)->findBy(
            ['environment' => $environment],
            ['number' => 'DESC']
        );
    }

    public function getLastSuccessful(Environment $environment): ?Build
    {
        /** @var Build $build */
        $build = $this->entityManager->getRepository(Build::class)->findOneBy(
            [
                'environment' => $environment,
                'status' =>  Build::STATUS_SUCCESSFUL
            ],
            ['number' => 'DESC']
        );

        return $build;
    }

    public function getLastFailed(Environment $environment): ?Build
    {
        /** @var Build $build */
        $build = $this->entityManager->getRepository(Build::class)->findOneBy(
            [
                'environment' => $environment,
                'status' =>  Build::STATUS_FAILED
            ],
            ['number' => 'DESC']
        );

        return $build;
    }

    public function getRunning(Environment $environment): ?Build
    {
        /** @var Build $build */
        $build = $this->entityManager->getRepository(Build::class)->findOneBy(
            [
                'environment' => $environment,
                'finishedAt' =>  null
            ],
            ['number' => 'DESC']
        );

        return $build;
    }
}