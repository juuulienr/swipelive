<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Withdraw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Withdraw|null find($id, $lockMode = null, $lockVersion = null)
 * @method Withdraw|null findOneBy(array $criteria, array $orderBy = null)
 * @method Withdraw[] findAll()
 * @method Withdraw[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WithdrawRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Withdraw::class);
  }
}
