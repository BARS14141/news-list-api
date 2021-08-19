<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @param array $ids
     * @param \DateTime|null $minDate
     * @param \DateTime|null $maxDate
     * @param int $offset
     * @param int $limit
     * @return int|mixed|string
     */
    public function search(array $ids = [], ?\DateTime $minDate, ?\DateTime $maxDate, int $offset = 0, int $limit = 10)
    {
        $queryBuilder = $this->createQueryBuilder('n');
        if ($ids) {
            $queryBuilder->andWhere('n.id IN (:ids)')
            ->setParameter('ids', $ids);
        }
        $queryBuilder = $this->minDate($minDate, $queryBuilder);
        $queryBuilder = $this->maxDate($maxDate, $queryBuilder);
        $queryBuilder->addOrderBy('n.publish_date', 'DESC')->addOrderBy('n.header', 'ASC')
            ->setFirstResult($offset)->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime|null $minDate
     * @param \DateTime|null $maxDate
     * @return int|mixed|string
     */
    public function getCountByDays(?\DateTime $minDate, ?\DateTime $maxDate)
    {
        $queryBuilder = $this->createQueryBuilder('n');
        $queryBuilder = $this->minDate($minDate, $queryBuilder);
        $queryBuilder = $this->maxDate($maxDate, $queryBuilder);
        $queryBuilder->groupBy('n.publish_date')->select('n.publish_date, count(n.id) as count');
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime|null $minDate
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function minDate(?\DateTime $minDate, QueryBuilder $queryBuilder): QueryBuilder
    {
        if ($minDate) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('n.publish_date', ':minDate'))
                            ->setParameter('minDate', $minDate->setTime(0, 0));
        }
        return $queryBuilder;
    }

    /**
     * @param \DateTime|null $maxDate
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    public function maxDate(?\DateTime $maxDate, QueryBuilder $queryBuilder): QueryBuilder
    {
        if ($maxDate) {
            $queryBuilder->andWhere($queryBuilder->expr()->lte('n.publish_date', ':maxDate'))
                ->setParameter('maxDate', $maxDate->setTime(0, 0));
        }
        return $queryBuilder;
    }
}
