<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShippingAddress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingAddress>
 *
 * @method ShippingAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingAddress[] findAll()
 * @method ShippingAddress[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method ShippingAddress|null findOneByUser(User $user)
 */
class ShippingAddressRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, ShippingAddress::class);
  }}
