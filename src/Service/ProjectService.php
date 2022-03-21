<?php

namespace App\Service;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectService
{
    protected ProjectRepository $repository;

    public function __construct(private SSHService $sshService, private EntityManagerInterface $entityManager)
    {
        /** @var ProjectRepository $repository */
        $repository = $this->entityManager->getRepository(Project::class);
        $this->repository = $repository;
    }

    private function getRepository(): ProjectRepository
    {
        return $this->repository;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    private function getSSHService(): SSHService
    {
        return $this->sshService;
    }

    public function create(Project $project)
    {
        $key = $this->getSSHService()->generateProjectKey($project);
        $project->setRepositorySSHPrivateKey($key->getPrivate());
        $project->setRepositorySSHPublicKey($key->getPublic());


        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();
    }

    public function update(Project $project)
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @return Project[]
     */
    public function getCollection(): array
    {
        return $this->getRepository()->getProjectsForAdministrator();
    }

    public function get(string $id): ?Project
    {
        return $this->getRepository()->find($id);
    }
}
