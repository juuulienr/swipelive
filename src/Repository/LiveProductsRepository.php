<?php

namespace App\Repository;

use App\Entity\LiveProducts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Product;

/**
 * @extends ServiceEntityRepository<LiveProducts>
 *
 * @method LiveProducts|null find($id, $lockMode = null, $lockVersion = null)
 * @method LiveProducts|null findOneBy(array $criteria, array $orderBy = null)
 * @method LiveProducts[]    findAll()
 * @method LiveProducts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method LiveProducts[]    findByProduct(Product $product)
 */
class LiveProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveProducts::class);
    }

    // /**
    //  * @return LiveProducts[] Returns an array of LiveProducts objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LiveProducts
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
