<?php
namespace App\Repository;

use App\Entity\CurrencyRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class CurrencyRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyRate::class);
    }

    public function findCurrencyData(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->where('cr.currencyPair = :currencyPair')
            ->andWhere('cr.time BETWEEN :start AND :end')
            ->setParameter('currencyPair', $fsym . $tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        return $qb->getQuery()->getResult();
    }

    public function dataExists(string $fsym, string $tsym, \DateTime $start, \DateTime $end): bool
    {
        $qb = $this->createQueryBuilder('cr')
            ->select('count(cr.id)')
            ->where('cr.currencyPair = :pair AND cr.time BETWEEN :start AND :end')
            ->setParameter('pair', $fsym.$tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        try {
            $count = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return false;
        }
        return $count > 0;
    }
}