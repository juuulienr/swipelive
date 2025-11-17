<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Discussion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discussion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discussion[]    findAll()
 * @method Discussion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscussionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussion::class);
    }

    public function findByVendorAndUser($user)
    {
        $query = $this->createQueryBuilder('d')
        ->join('d.vendor', 'v')
        ->join('d.user', 'u')
        ->andWhere('v.id = :user OR u.id = :user')
        ->setParameter('user', $user);

        return $query->orderBy('d.updatedAt', 'DESC')
        ->getQuery()
        ->getResult();
    }
}
