<?php

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TenantRepository extends ServiceEntityRepository implements LoggerRepositoryInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    /**
     * @return QueryBuilder|null
     */
    public function findAllTenantsQueryBuilder(): ?QueryBuilder
    {
        return $this->createQueryBuilder('tenant')
            ->where('tenant.deletedAt IS NULL')
            ->orderBy('tenant.id', 'ASC');
    }

    /**
     * @param $value
     * @return Tenant|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByName($value): ?Tenant
    {
        return $this->createQueryBuilder('tenant')
            ->where('tenant.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getEntityArrayResult(string $name, $value): array
    {
        $conn = $this->_em->getConnection();
        return $conn->createQueryBuilder()
            ->select('*')
            ->from('tenant', 't')
            ->where('t.' . $name . ' = :value')
            ->setParameter('value', $value)
            ->execute()
            ->fetchAll();
    }
}
