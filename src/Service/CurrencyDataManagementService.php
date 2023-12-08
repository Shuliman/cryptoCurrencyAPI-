<?php
namespace App\Service;

use App\Entity\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
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
            $apiResult = $this->apiService->getHistoricalData($fsym, $tsym, new \DateTime($interval['start']), new \DateTime($interval['end']));
            if ($apiResult['success']) {
                $this->saveDataToDb($apiResult['data'], $fsym, $tsym);
            } else {
                $this->logger->error("API error: " . $apiResult['error']);
            }
        }

        return $this->getDataFromDb($fsym, $tsym, $start, $end);
    }
    private function getMissingIntervals(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        $missingIntervals = [];
        $current = clone $start;
        while ($current < $end) {
            $nextHour = (clone $current)->modify('+1 hour');
            if (!$this->dataExistsInDb($fsym, $tsym, $current, $nextHour)) {
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
    private function dataExistsInDb(string $fsym, string $tsym, \DateTime $start, \DateTime $end): bool
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(cr.id)')
            ->from(CurrencyRate::class, 'cr')
            ->where('cr.currencyPair = :pair AND cr.time BETWEEN :start AND :end')
            ->setParameter('pair', $fsym.$tsym)
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $count = $qb->getQuery()->getSingleScalarResult();
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
