<?php
namespace App\Service;

use App\Entity\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;

class CurrencyDataManagementService
{
    private $apiService;
    private $entityManager;
    private $logger;

    public function __construct(CryptoCurrencyApiService $apiService, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function updateAndRetrieveData(\DateTime $start, \DateTime $end, string $fsym, string $tsym): array
    {
        $missingIntervals = $this->getMissingIntervals($fsym, $tsym, $start, $end);
        if (empty($missingIntervals)) {
            return $this->getDataFromDb($fsym, $tsym, $start, $end);
        }

        foreach ($missingIntervals as $interval) {
            try {
                $this->processInterval($interval, $fsym, $tsym);
            } catch (\Exception $e) {
                $this->logger->error("Error processing interval: " . $e->getMessage());
                throw $e;
            }
        }

        return $this->getDataFromDb($fsym, $tsym, $start, $end);
    }

    /**
     * @throws \Exception
     */
    private function processInterval($interval, $fsym, $tsym): void
    {
        $apiResult = $this->apiService->getHistoricalData($fsym, $tsym, new \DateTime($interval['start']), new \DateTime($interval['end']));
        if ($apiResult['success']) {
            $this->entityManager->beginTransaction();
            try {
                $this->saveDataToDb($apiResult['data'], $fsym, $tsym);
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $this->logger->error("Database error: " . $e->getMessage());
                throw $e;
            }
        } else {
            $this->logger->error("API error: " . $apiResult['error']);
            throw new \Exception("API error: " . $apiResult['error']);
        }
    }


    private function getMissingIntervals(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        $existingData = $this->getDataFromDb($fsym, $tsym, $start, $end);
        $existingIntervals = [];
        foreach ($existingData as $dataItem) {
            $existingIntervals[] = [
                'start' => $dataItem->getFormattedTime()->format('Y-m-d H:i:s'),
                'end' => (clone $dataItem->getFormattedTime())->modify('+1 hour')->format('Y-m-d H:i:s')
            ];
        }

        $missingIntervals = [];
        $current = clone $start;
        while ($current < $end) {
            $nextHour = (clone $current)->modify('+1 hour');
            if (!in_array(['start' => $current->format('Y-m-d H:i:s'), 'end' => $nextHour->format('Y-m-d H:i:s')], $existingIntervals)) {
                $missingIntervals[] = ['start' => $current->format('Y-m-d H:i:s'), 'end' => $nextHour->format('Y-m-d H:i:s')];
            }
            $current = $nextHour;
        }
        return $missingIntervals;
    }
    private function getDataFromDb(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $queryBuilder->select('cr')
            ->from(CurrencyRate::class, 'cr')
            ->where('cr.currencyPair = :currencyPair')
            ->andWhere('cr.time BETWEEN :start AND :end')
            ->setParameter('currencyPair', $fsym . $tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function dataExistsInDb(string $fsym, string $tsym, \DateTime $start, \DateTime $end): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(cr.id)')
            ->from(CurrencyRate::class, 'cr')
            ->where('cr.currencyPair = :pair AND cr.time BETWEEN :start AND :end')
            ->setParameter('pair', $fsym.$tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        try {
            $count = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }
        return $count > 0;
    }

    private function saveDataToDb(array $data, string $fsym, string $tsym): void
    {
        foreach ($data as $dataItem) {
            $currencyRate = new CurrencyRate();
            $currencyRate->setTime($dataItem['time'])
                ->setHigh($dataItem['high'])
                ->setLow($dataItem['low'])
                ->setOpen($dataItem['open'])
                ->setClose($dataItem['close'])
                ->setVolumeFrom($dataItem['volumefrom'])
                ->setCurrencyPair($fsym . $tsym);

            $this->entityManager->persist($currencyRate);
        }
        $this->entityManager->flush();
    }
}
