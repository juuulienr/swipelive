<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LiveProducts;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LiveProducts>
 *
 * @method LiveProducts|null find($id, $lockMode = null, $lockVersion = null)
 * @method LiveProducts|null findOneBy(array $criteria, array $orderBy = null)
 * @method LiveProducts[]    findAll()
 * @method LiveProducts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method LiveProducts[]    findByProduct(Product $product)
 */
class LiveProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LiveProducts::class);
    }
}
