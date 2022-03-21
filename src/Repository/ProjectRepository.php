<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return Project[]
     */
    public function getProjectsForAdministrator(): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('p')
            ->orderBy('p.name', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Project[]
     */
    public function getRestrictedProjects(array $groups = []): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('p, e')
            ->join('p.environments', 'e')
            ->where('e.group IN (:groups)')
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->setParameter('groups', $groups)
        ;

        return $qb->getQuery()->getResult();
    }
}
