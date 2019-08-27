<?php

namespace App\Repository;

use App\Entity\CountryTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CountryTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method CountryTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method CountryTranslation[]    findAll()
 * @method CountryTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryTranslationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CountryTranslation::class);
    }
}
