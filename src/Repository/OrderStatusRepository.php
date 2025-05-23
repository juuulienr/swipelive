<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderStatus>
 *
 * @method OrderStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderStatus[] findAll()
 * @method OrderStatus[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderStatus|null findOneByShipping(Order $order)
 */
class OrderStatusRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, OrderStatus::class);
  }

  // /**
  //  * @return OrderStatus[] Returns an array of OrderStatus objects
  //  */
  /*
  public function findByExampleField($value)
  {
      return $this->createQueryBuilder('o')
          ->andWhere('o.exampleField = :val')
          ->setParameter('val', $value)
          ->orderBy('o.id', 'ASC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult()
      ;
  }
  */

  /*
  public function findOneBySomeField($value): ?OrderStatus
  {
      return $this->createQueryBuilder('o')
          ->andWhere('o.exampleField = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
      ;
  }
  */
}
