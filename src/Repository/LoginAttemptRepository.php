<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LoginAttempt|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginAttempt|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginAttempt[]    findAll()
 * @method LoginAttempt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    /**
     * LoginAttemptRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    /**
     * @param Account $account
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @return mixed
     */
    public function getAccountAttemptsByPeriod(Account $account, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate)
    {
        return $this->createQueryBuilder('la')
            ->where('la.account =:account')
            ->andWhere('la.createdAt >= :startDate')
            ->andWhere('la.createdAt <= :endDate')
            ->setParameter('account', $account)
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Account $account
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLatestByAccount(Account $account)
    {
        return $this->createQueryBuilder('la')
            ->where('la.account =:account')
            ->setParameter('account', $account)
            ->orderBy('la.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
