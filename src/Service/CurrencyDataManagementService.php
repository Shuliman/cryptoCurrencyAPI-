<?php
namespace App\Service;

use App\Service\Interface\CurrencyDataUpdaterInterface;
use App\Repository\CurrencyRateRepository;
/**
 * Service class for managing currency data.
 * Handles interactions with cryptocurrency API, database operations, and logging.
 */
class CurrencyDataManagementService
{
    /**
     * Constructor to initialize the service with necessary dependencies.
     *
     * @param CurrencyRateRepository $currencyRateRepository The Repository data getting manager.
     * @param CurrencyDataUpdaterInterface $dataUpdater The updating data service.
     */
    public function __construct(
        private CurrencyRateRepository       $currencyRateRepository,
        private CurrencyDataUpdaterInterface $dataUpdater,
    )
    {
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
        $this->dataUpdater->updateData($start, $end, $fsym, $tsym);
        return $this->currencyRateRepository->findCurrencyData($fsym, $tsym, $start, $end);
    }
}
