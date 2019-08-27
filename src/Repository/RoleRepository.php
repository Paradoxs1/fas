<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Role|null find($id, $lockMode = null, $lockVersion = null)
 * @method Role|null findOneBy(array $criteria, array $orderBy = null)
 * @method Role[]    findAll()
 * @method Role[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * @param $value
     * @return Role|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName($value): ?Role
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.internalName = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param array $roles
     * @return mixed
     */
    public function getRolesByCodes(array $roles = []): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if ($roles) {
            $queryBuilder
                ->andWhere('r.internalName IN (:roles)')
                ->setParameter('roles', $roles, Connection::PARAM_INT_ARRAY)
                ->orderBy('r.id', 'ASC');
        }
        return $queryBuilder->getQuery()->getResult();
    }
}
