<?php

namespace App\Repository;

use App\Entity\AccountEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AccountEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountEmail[]    findAll()
 * @method AccountEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountEmailRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AccountEmail::class);
    }
}
