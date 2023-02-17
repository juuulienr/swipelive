<?php

namespace App\Repository;

use App\Entity\Clip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Clip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clip[]    findAll()
 * @method Clip[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClipRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Clip::class);
  }


  public function findByClip($vendor){
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status');

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->setParameter('status', "available")
    ->orderBy('RAND()')
    ->getQuery()
    ->getResult();
  }


  public function findTrendingClips($vendor){
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status')
    ->setParameter('status', "available");

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->orderBy('c.createdAt', 'ASC')
    ->getQuery()
    ->getResult();
  }


  public function findLatestClips($vendor){
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status')
    ->setParameter('status', "available");

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->orderBy('c.createdAt', 'DESC')
    ->getQuery()
    ->getResult();
  }


  public function retrieveClips($vendor){
    return $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('v.id = :vendor')
    ->andWhere('c.status = :status')
    ->setParameter('vendor', $vendor)
    ->setParameter('status', "available")
    ->getQuery()
    ->getResult();
  }



    // /**
    //  * @return Clip[] Returns an array of Clip objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Clip
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
  }
