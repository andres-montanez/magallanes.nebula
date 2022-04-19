<?php

namespace App\Service;

use App\Entity\Build;
use App\Entity\Project;
use App\Entity\Environment;
use Doctrine\ORM\EntityManagerInterface;

final class EnvironmentService
{
    public function __construct(private SSHService $sshService, private EntityManagerInterface $entityManager)
    {
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    private function getSSHService(): SSHService
    {
        return $this->sshService;
    }

    public function create(Environment $environment)
    {
        $key = $this->getSSHService()->generateEnvironmentKey($environment);
        $environment->setSSHPrivateKey($key->getPrivate());
        $environment->setSSHPublicKey($key->getPublic());

        $this->getEntityManager()->persist($environment);
        $this->getEntityManager()->flush();
    }

    public function update(Environment $environment)
    {
        $this->entityManager->flush();
    }

    /**
     * @return Environment[]
     */
    public function getCollection(Project $project): array
    {
        return $this->entityManager->getRepository(Environment::class)->findBy(
            ['project' => $project],
            ['name' => 'ASC']
        );
    }

    public function get(string $id): ?Environment
    {
        return $this->entityManager->getRepository(Environment::class)->findOneBy([
            'id' => $id,
        ]);
    }

    public function getLastBuild(Environment $environment): ?Build
    {
        return $this->entityManager->getRepository(Build::class)->findOneBy(
            ['environment' => $environment],
            ['createdAt' => 'DESC']
        );
    }

    public function getLastSuccessBuild(Environment $environment): ?Build
    {
        return $this->entityManager->getRepository(Build::class)->findOneBy(
            [
                'environment' => $environment,
                'status' => Build::STATUS_SUCCESSFUL,
            ],
            ['createdAt' => 'DESC']
        );
    }

    public function getLastFailBuild(Environment $environment): ?Build
    {
        return $this->entityManager->getRepository(Build::class)->findOneBy(
            [
                'environment' => $environment,
                'status' => Build::STATUS_FAILED,
            ],
            ['createdAt' => 'DESC']
        );
    }
}
