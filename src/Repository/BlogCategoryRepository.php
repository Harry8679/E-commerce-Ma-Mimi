<?php

namespace App\Repository;

use App\Entity\BlogCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogCategory>
 */
class BlogCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogCategory::class);
    }

    /**
     * Récupérer toutes les catégories avec le nombre d'articles publiés
     */
    public function findAllWithPostCount(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.posts', 'p', 'WITH', 'p.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}