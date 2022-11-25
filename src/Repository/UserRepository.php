<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function findUserFollowing($user){
    	return $this->createQueryBuilder('u')
    	->join('u.followers', 'f')
    	->andWhere('f.follower = :user')
    	->setParameter('user', $user)
    	->getQuery()
    	->getResult();
    }


    public function findUserFollowers($user){
    	return $this->createQueryBuilder('u')
    	->join('u.following', 'f')
    	->andWhere('f.following = :user')
    	->setParameter('user', $user)
    	->getQuery()
    	->getResult();
    }

    public function findUserBySearch($search, $vendor = null){
    	return $this->createQueryBuilder('u')
    	->join('u.vendor', 'v')
    	->andWhere('u.firstname LIKE :search OR u.lastname LIKE :search OR v.businessName LIKE :search')
    	->andWhere('u.type = :type')
      ->andWhere('v.id != :vendor')
      ->setParameter('vendor', $vendor)
    	->setParameter('type', 'vendor')
    	->setParameter('search', '%'.$search.'%')
    	->getQuery()
    	->getResult();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
