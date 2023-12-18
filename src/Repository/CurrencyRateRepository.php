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

    /**
     * Retrieves data from the database for a specific currency pair and time interval.
     *
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @return array An array of CurrencyRate entities.
     */
    public function findCurrencyData(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->where('cr.currencyPair = :currencyPair')
            ->andWhere('cr.time BETWEEN :start AND :end')
            ->setParameter('currencyPair', $fsym . $tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp())
            ->orderBy('cr.time', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Checks if data for a specific currency pair and time interval exists in the database.
     *
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @return bool True if data exists, false otherwise.
     * @throws NonUniqueResultException|NoResultException If the query result is non-unique or no result is found.
     */
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