<?php
namespace App\Service;

use App\Entity\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
/**
 * Service class for managing currency data.
 * Handles interactions with cryptocurrency API, database operations, and logging.
 */
class CurrencyDataManagementService
{
    /**
     * @var CryptoCurrencyApiService The service for interacting with the cryptocurrency API.
     */
    private $apiService;

    /**
     * @var EntityManagerInterface The entity manager for database operations.
     */
    private $entityManager;

    /**
     * @var LoggerInterface The logging service.
     */
    private $logger;

    /**
     * Constructor to initialize the service with necessary dependencies.
     *
     * @param CryptoCurrencyApiService $apiService The cryptocurrency API service.
     * @param EntityManagerInterface $entityManager The Doctrine entity manager.
     * @param LoggerInterface $logger The logger service.
     */
    public function __construct(CryptoCurrencyApiService $apiService, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Updates and retrieves currency data for a given period and currency pair.
     *
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @return array The array of currency data.
     * @throws \Exception If there is an error processing the interval.
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
     * Processes a single time interval for currency data.
     *
     * @param array $interval The interval to process.
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @throws \Exception If an error occurs during processing.
     */
    private function processInterval($interval, $fsym, $tsym): void
    {
        sleep(1);
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

    /**
     * Identifies missing intervals of data between two dates.
     *
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @return array An array of missing intervals.
     */
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
    /**
     * Retrieves data from the database for a specific currency pair and time interval.
     *
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @return array An array of CurrencyRate entities.
     */
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
     * Checks if data for a specific currency pair and time interval exists in the database.
     *
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     * @param \DateTime $start The start date of the interval.
     * @param \DateTime $end The end date of the interval.
     * @return bool True if data exists, false otherwise.
     * @throws NonUniqueResultException|NoResultException If the query result is non-unique or no result is found.
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
    /**
     * Saves currency data to the database.
     *
     * @param array $data The array of currency data to save.
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     */
    private function saveDataToDb(array $data, string $fsym, string $tsym): void
    {
        foreach ($data as $dataItem) {
            if ($this->dataExistsInDb($fsym, $tsym, (new \DateTime())->setTimestamp($dataItem['time']), (new \DateTime())->setTimestamp($dataItem['time']))) {
                $this->logger->info("Data for timestamp {$dataItem['time']} already exists. Skipping.");
                continue;
            }
            $currencyRate = new CurrencyRate();
            $currencyRate->setTime($dataItem['time'])
                ->setHigh($dataItem['high'])
                ->setLow($dataItem['low'])
                ->setOpen($dataItem['open'])
                ->setClose($dataItem['close'])
                ->setVolumeFrom($dataItem['volumefrom'])
                ->setCurrencyPair($fsym . $tsym);

            $this->entityManager->persist($currencyRate);
            $this->logger->info("Saving new data for timestamp {$dataItem['time']}");
        }
        $this->entityManager->flush();
    }
}
