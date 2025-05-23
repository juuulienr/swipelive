<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\Vendor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankAccount>
 *
 * @method BankAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankAccount[] findAll()
 * @method BankAccount[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method BankAccount[] findByVendor(Vendor $vendor)
 * @method BankAccount|null findOneByVendor(Vendor $vendor)
 */
class BankAccountRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, BankAccount::class);
  }

  // /**
  //  * @return BankAccount[] Returns an array of BankAccount objects
  //  */
  /*
  public function findByExampleField($value)
  {
      return $this->createQueryBuilder('b')
          ->andWhere('b.exampleField = :val')
          ->setParameter('val', $value)
          ->orderBy('b.id', 'ASC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult()
      ;
  }
  */

  /*
  public function findOneBySomeField($value): ?BankAccount
  {
      return $this->createQueryBuilder('b')
          ->andWhere('b.exampleField = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
      ;
  }
  */
}
