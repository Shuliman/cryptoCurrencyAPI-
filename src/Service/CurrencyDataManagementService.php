<?php
namespace App\Service;

use App\Entity\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Log\LoggerInterface;
use App\Repository\CurrencyRateRepository;
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
    private $currencyRateRepository;

    /**
     * @var LoggerInterface The logging service.
     */
    private $logger;

    /**
     * Constructor to initialize the service with necessary dependencies.
     *
     * @param CryptoCurrencyApiService $apiService The cryptocurrency API service.
     * @param CurrencyRateRepository $currencyRateRepository The Repository data getting manager.
     * @param LoggerInterface $logger The logger service.
     */
    public function __construct(
        CryptoCurrencyApiService $apiService,
        CurrencyRateRepository $currencyRateRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->currencyRateRepository = $currencyRateRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
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
    public function updateData(\DateTime $start, \DateTime $end, string $fsym, string $tsym): void {
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
     * Gets currency data from the database for a given period and currency pair.
     * Returns an array of currency data from the database.
     *
     * @param \DateTime $start Start date of the interval.
     * @param \DateTime $end End date of the interval.
     * @param string $fsym Source currency symbol.
     * @param string $tsym Target currency symbol.
     * @return array Array of currency data.
     */
    public function retrieveData(\DateTime $start, \DateTime $end, string $fsym, string $tsym): array {
        return $this->currencyRateRepository->findCurrencyData($fsym, $tsym, $start, $end);
    }
    /**
     * getCurrencys that coordinates updating and retrieving currency data.
     * First updates the data, then retrieves it from the database.
     *
     * @param \DateTime $start Start date of the interval.
     * @param \DateTime $end End date of the interval.
     * @param string $fsym Source currency symbol.
     * @param string $tsym Target currency symbol.
     * @return array Array of currency data.
     * @throws \Exception If an error occurs while processing or extracting the currency data.
     */
    public function getCurrencys(\DateTime $start, \DateTime $end, string $fsym, string $tsym): array {
        $this->updateData($start, $end, $fsym, $tsym);
        return $this->retrieveData($start, $end, $fsym, $tsym);
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
    private function getDataFromDb(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array {
        return $this->currencyRateRepository->findCurrencyData($fsym, $tsym, $start, $end);
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
        return $this->currencyRateRepository->dataExists($fsym, $tsym, $start, $end);
    }
    /**
     * Saves currency data to the database.
     *
     * @param array $data The array of currency data to save.
     * @param string $fsym The symbol of the from currency.
     * @param string $tsym The symbol of the to currency.
     */
    private function saveDataToDb(array $data, string $fsym, string $tsym): void {
        foreach ($data as $dataItem) {
            if (!$this->dataExistsInDb($fsym, $tsym, (new \DateTime())->setTimestamp($dataItem['time']), (new \DateTime())->setTimestamp($dataItem['time']))) {
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
            } else {
                $this->logger->info("Data for timestamp {$dataItem['time']} already exists. Skipping.");
            }
        }
        $this->entityManager->flush();
    }
}
