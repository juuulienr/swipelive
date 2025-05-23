<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[] findAll()
 * @method Order[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Order|null findOneById(int $id)
 */
class OrderRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Order::class);
  }

  public function findByVendorOrBuyer($user)
  {
    return $this->createQueryBuilder('o')
                ->join('o.vendor', 'v')
                ->join('v.user', 'u')
                ->join('o.buyer', 'b')
                ->andWhere('b.id = :user OR u.id = :user')
                ->orderBy('o.createdAt', 'DESC')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
  }

  public function findSucceededOrders()
  {
    return $this->createQueryBuilder('o')
    ->where('o.status = :status')
    ->setParameter('status', 'succeeded')
    ->getQuery()
    ->getResult();
  }


  // /**
  //  * @return Order[] Returns an array of Order objects
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
  public function findOneBySomeField($value): ?Order
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
