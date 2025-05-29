<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Clip;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clip>
 *
 * @method Clip|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clip|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clip[] findAll()
 * @method Clip[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Clip[] findByProduct(Product $product)
 */
class ClipRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Clip::class);
  }

  public function findByClip($vendor)
  {
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status')
    ->andWhere('c.fileList IS NOT NULL');

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->setParameter('status', 'available')
    ->orderBy('RAND()')
    ->getQuery()
    ->getResult();
  }

  public function findTrendingClips($vendor)
  {
    $query = $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('c.status = :status')
    ->setParameter('status', 'available');

    if ($vendor) {
      $query->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor);
    }

    return $query->orderBy('c.createdAt', 'ASC')
    ->setMaxResults(18)
    ->getQuery()
    ->getResult();
  }

  public function retrieveClips($vendor)
  {
    return $this->createQueryBuilder('c')
    ->join('c.vendor', 'v')
    ->andWhere('v.id = :vendor')
    ->andWhere('c.status = :status')
    ->setParameter('vendor', $vendor)
    ->setParameter('status', 'available')
    ->getQuery()
    ->getResult();
  }}
