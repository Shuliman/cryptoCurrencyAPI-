<?php

namespace App\Service;

use App\Entity\CurrencyRate;
use App\Repository\CurrencyRateRepository;
use App\Service\Interface\DataSaverInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DataSaverService implements DataSaverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CurrencyRateRepository $currencyRateRepository,
        private LoggerInterface        $logger
    )
    {
    }

    public function saveData(array $data, string $fsym, string $tsym): bool
    {
        try {
            foreach ($data as $dataItem) {
                $timestamp = (new \DateTime())->setTimestamp($dataItem['time']);
                if (!$this->currencyRateRepository->dataExists($fsym, $tsym, $timestamp, $timestamp)) {
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
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error in data save process : " . $e->getMessage());
            return false;
        }
    }
}
