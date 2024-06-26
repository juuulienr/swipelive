<?php

namespace App\Repository;

use App\Entity\Live;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Live|null find($id, $lockMode = null, $lockVersion = null)
 * @method Live|null findOneBy(array $criteria, array $orderBy = null)
 * @method Live[]    findAll()
 * @method Live[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Live::class);
    }


    public function findByLive($vendor){
      $query = $this->createQueryBuilder('l')
      ->join('l.vendor', 'v')
      ->andWhere('l.status = 1');

      if ($vendor) {
        $query->andWhere('v.id != :vendor')
        ->setParameter('vendor', $vendor);
      }

      return $query->orderBy('RAND()')
      ->getQuery()
      ->getResult();
    }


    public function vendorIsLive($vendor){
      $query = $this->createQueryBuilder('l')
      ->join('l.vendor', 'v')
      ->andWhere('l.status = 1');

      if ($vendor) {
        $query->andWhere('v.id = :vendor')
        ->setParameter('vendor', $vendor);
      }

      return $query->getQuery()
      ->getResult();
    }


    // public function findByLiveAndVendor($vendor){
    //     return $this->createQueryBuilder('l')
    //                 ->join('l.vendor', 'v')
    //                 ->andWhere('l.resourceUri IS NOT NULL')
    //                 ->andWhere('l.status = 1')
    //                 ->andWhere('v.id != :vendor')
    //                 ->setParameter('vendor', $vendor)
    //                 ->orderBy('RAND()')
    //                 // ->setMaxResults(20)
    //                 ->getQuery()
    //                 ->getResult();
    // }


    // /**
    //  * @return Live[] Returns an array of Live objects
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
    public function findOneBySomeField($value): ?Live
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
