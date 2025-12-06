<?php

namespace App\Repository;

use App\Entity\BlogPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<BlogPost>
 */
class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPost::class);
    }

    /**
     * Récupérer les articles publiés avec pagination
     */
    public function findPublishedPaginated(int $page = 1, int $limit = 9, ?int $categoryId = null, ?string $search = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.publishedAt', 'DESC');

        if ($categoryId) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $categoryId);
        }

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.excerpt LIKE :search OR p.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), true);
    }

    /**
     * Récupérer les articles récents
     */
    public function findRecentPublished(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les articles populaires (les plus likés)
     */
    public function findPopular(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('p.id')
            ->orderBy('COUNT(l.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}