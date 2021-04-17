<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ProjectService
{
    protected EntityManagerInterface $entityManager;
    protected Security $security;
    protected ProjectRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;

        /** @var ProjectRepository $repository */
        $repository = $this->entityManager->getRepository(Project::class);
        $this->repository = $repository;
    }

    public function create(Project $project)
    {
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    public function update(Project $project)
    {
        $this->entityManager->flush();
    }

    /**
     * @return Project[]
     */
    public function getProjects(): array
    {
        if ($this->security->isGranted(User::ROLE_ADMINISTRATOR)) {
            return $this->repository->getProjectsForAdministrator();
        } elseif ($this->security->isGranted(User::ROLE_USER)) {
            /** @var User $user */
            $user = $this->security->getUser();
            return $this->repository->getRestrictedProjects($user->getGroupIds());
        }

        return [];
    }
}