<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SecurityUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityUser>
 *
 * @method SecurityUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SecurityUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SecurityUser[] findAll()
 * @method SecurityUser[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method SecurityUser|null findOneByUser(User $user)
 */
class SecurityUserRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, SecurityUser::class);
  }

  // /**
  //  * @return SecurityUser[] Returns an array of SecurityUser objects
  //  */
  /*
  public function findByExampleField($value)
  {
      return $this->createQueryBuilder('s')
          ->andWhere('s.exampleField = :val')
          ->setParameter('val', $value)
          ->orderBy('s.id', 'ASC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult()
      ;
  }
  */

  /*
  public function findOneBySomeField($value): ?SecurityUser
  {
      return $this->createQueryBuilder('s')
          ->andWhere('s.exampleField = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
      ;
  }
  */
}
