<?php

namespace App\Repository;

use App\Entity\BlogLike;
use App\Entity\BlogPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogLike>
 */
class BlogLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogLike::class);
    }

    /**
     * Trouver un like par utilisateur et article
     */
    public function findOneByUserAndPost(User $user, BlogPost $post): ?BlogLike
    {
        return $this->createQueryBuilder('l')
            ->where('l.user = :user')
            ->andWhere('l.post = :post')
            ->setParameter('user', $user)
            ->setParameter('post', $post)
            ->getQuery()
            ->getOneOrNullResult();
    }
}