<?php

namespace App\Service;

use App\Service\Interface\CurrencyDataUpdaterInterface;
use App\Service\Interface\DataSaverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Set;
use Psr\Log\LoggerInterface;
use App\Repository\CurrencyRateRepository;

/**
 * Service class for managing currency data.
 * Handles interactions with cryptocurrency API, database operations, and logging.
 */
class CurrencyDataUpdaterService implements CurrencyDataUpdaterInterface
{
    /**
     * Constructor to initialize the service with necessary dependencies.
     *
     * @param CryptoCurrencyApiService $apiService The cryptocurrency API service.
     * @param CurrencyRateRepository $currencyRateRepository The Repository data getting manager.
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param LoggerInterface $logger The logger service.
     */
    public function __construct(
        private CryptoCurrencyApiService $apiService,
        private CurrencyRateRepository   $currencyRateRepository,
        private EntityManagerInterface   $entityManager,
        private DataSaverInterface       $dataSaver,
        private LoggerInterface          $logger
    )
    {
    }

    /**
     * Updates currency data for the given period and currency pair.
     * This method calls `processInterval` for each missing time interval.
     *
     * @param \DateTime $start The starting date of the interval.
     * @param \DateTime $end End date of the interval.
     * @param string $fsym Source currency symbol.
     * @param string $tsym Target currency symbol.
     * @throws \Exception If an error occurs while processing the interval.
     */
    public function updateData(\DateTime $start, \DateTime $end, string $fsym, string $tsym): void
    {
        $missingIntervals = $this->getMissingIntervals($fsym, $tsym, $start, $end);
        foreach ($missingIntervals as $interval) {
            try {
                $this->processInterval($interval, $fsym, $tsym);
            } catch (\Exception $e) {
                $this->logger->error("Error processing interval: " . $e->getMessage());
                throw $e;
            }
        }
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
                $this->dataSaver->saveData($apiResult['data'], $fsym, $tsym);
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
        // Getting data from the database
        $existingData = $this->currencyRateRepository->findCurrencyData($fsym, $tsym, $start, $end);

        // Initialize hash set for existing intervals
        $existingIntervals = new Set();
        foreach ($existingData as $dataItem) {
            // Add a formatted time to the hash set
            $existingIntervals->add($dataItem->getFormattedTime()->format('Y-m-d H:i:s'));
        }

        $missingIntervals = [];
        // Create an interval of one hour
        $interval = new \DateInterval('PT1H');
        // Create a time period from start to end in one hour increments
        $period = new \DatePeriod($start, $interval, $end);

        // Check every hour interval
        foreach ($period as $dt) {
            $startStr = $dt->format('Y-m-d H:i:s');
            $endStr = $dt->add($interval)->format('Y-m-d H:i:s');

            // Check if there is no interval in the existing data
            if (!$existingIntervals->contains($startStr)) {
                $missingIntervals[] = ['start' => $startStr, 'end' => $endStr];
            }
        }

        return $missingIntervals;
    }
}