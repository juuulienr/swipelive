<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Promotion;
use App\Entity\Vendor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 *
 * @method Promotion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Promotion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Promotion[] findAll()
 * @method Promotion[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Promotion[] findByVendor(Vendor $vendor)
 * @method Promotion|null findOneById(int $id)
 */
class PromotionRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Promotion::class);
  }}
