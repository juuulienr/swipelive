<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Favoris;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favoris>
 *
 * @method Favoris|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favoris|null findOneBy(array $criteria, array $orderBy = null)
 * @method Favoris[] findAll()
 * @method Favoris[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Favoris[] findByUser(User $user)
 */
class FavorisRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Favoris::class);
  }
}
