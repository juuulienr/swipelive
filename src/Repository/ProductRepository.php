<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findTrendingProducts($vendor)
    {
        $query = $this->createQueryBuilder('p')
        ->join('p.vendor', 'v');

        if ($vendor) {
            $query->andWhere('v.id != :vendor')
            ->setParameter('vendor', $vendor);
        }

        return $query->getQuery()
        ->setMaxResults(18)
        ->getResult();
    }

    public function findProductsNotCreatedByVendor($vendor)
    {
        $query = $this->createQueryBuilder('p')
        ->join('p.vendor', 'v');

        if ($vendor) {
            $query->andWhere('v.id != :vendor')
            ->setParameter('vendor', $vendor);
        }

        return $query->getQuery()
        ->setMaxResults(100)
        ->getResult();
    }

    public function findOneById($id): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByVendor($vendor)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.vendor = :vendor')
            ->setParameter('vendor', $vendor)
            ->getQuery()
            ->getResult()
        ;
    }
}
