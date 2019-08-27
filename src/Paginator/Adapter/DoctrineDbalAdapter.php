<?php

namespace App\Paginator\Adapter;

use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Adapter\DoctrineDbalAdapter as BaseDoctrineDbalAdapter;

class DoctrineDbalAdapter extends BaseDoctrineDbalAdapter
{
    private $queryBuilder;
    private $countQueryBuilderModifier;

    /**
     * Constructor.
     *
     * @param QueryBuilder $queryBuilder              A DBAL query builder.
     * @param callable     $countQueryBuilderModifier A callable to modifier the query builder to count.
     */
    public function __construct(QueryBuilder $queryBuilder, $countQueryBuilderModifier)
    {
        if ($queryBuilder->getType() !== QueryBuilder::SELECT) {
            throw new InvalidArgumentException('Only SELECT queries can be paginated.');
        }

        if (!is_callable($countQueryBuilderModifier)) {
            throw new InvalidArgumentException('The count query builder modifier must be a callable.');
        }

        $this->queryBuilder = clone $queryBuilder;
        $this->countQueryBuilderModifier = $countQueryBuilderModifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $qb = $this->prepareCountQueryBuilder();
        $result = $qb->execute()->fetchAll();

        return count($result);
    }

    private function prepareCountQueryBuilder()
    {
        $qb = clone $this->queryBuilder;
        call_user_func($this->countQueryBuilderModifier, $qb);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $qb = clone $this->queryBuilder;
        $result = $qb->setMaxResults($length)
            ->setFirstResult($offset)
            ->execute();

        return $result->fetchAll();
    }
}
