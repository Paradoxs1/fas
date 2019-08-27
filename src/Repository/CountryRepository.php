<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Country|null find($id, $lockMode = null, $lockVersion = null)
 * @method Country|null findOneBy(array $criteria, array $orderBy = null)
 * @method Country[]    findAll()
 * @method Country[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * @param $iso
     * @return Country|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByISO($iso): ?Country
    {
        return $this->createQueryBuilder('country')
            ->andWhere('country.isoCode = :val')
            ->setParameter('val', $iso)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
