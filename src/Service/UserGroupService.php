<?php

namespace App\Service;

use App\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;

class UserGroupService
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return UserGroup[]
     */
    public function getGroups(): array
    {
        $repository = $this->entityManager->getRepository(UserGroup::class);
        return $repository->findBy([], ['name' => 'ASC']);
    }

    public function create(UserGroup $group)
    {
        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    public function update(UserGroup $group)
    {
        $this->entityManager->flush();
    }
}