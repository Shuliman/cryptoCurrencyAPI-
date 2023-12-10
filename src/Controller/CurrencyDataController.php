<?php
namespace App\Controller;

use App\Service\CurrencyDataManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for handling currency data requests.
 */
class CurrencyDataController extends AbstractController
{
    private $currencyDataManagementService;

    /**
     * Constructor of the controller.
     *
     * @param CurrencyDataManagementService $currencyDataManagementService Service for managing currency data.
     */
    public function __construct(CurrencyDataManagementService $currencyDataManagementService)
    {
        $this->currencyDataManagementService = $currencyDataManagementService;
    }

    /**
     * Fetches hourly currency data.
     *
     * @Route('/currency/hourly', methods: ['GET'])
     * @param Request $request The current request object.
     * @return JsonResponse The response containing currency data or error message.
     */
    #[Route('/currency/hourly', methods: ['GET'])]
    public function getHourlyData(Request $request): JsonResponse
    {
        $fsym = $request->query->get('fsym');
        $tsym = $request->query->get('tsym');
        $hour = $request->query->get('hour', (new \DateTime())->setTime((new \DateTime())->format('H'), 0, 0)->format('Y-m-d H:00:00'));

        // Setting start and end times for data retrieval
        $start = new \DateTime($hour);
        $end = (clone $start)->modify('+1 hour');

        try {
            $data = $this->currencyDataManagementService->updateAndRetrieveData($start, $end, $fsym, $tsym);

            // Filter out data outside the specific hour, if necessary
            if (count($data) > 1) {
                array_pop($data);
            }

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Fetches daily currency data.
     *
     * @Route('/currency/daily', methods: ['GET'])
     * @param Request $request The current request object.
     * @return JsonResponse The response containing daily currency data or an error message.
     *
     * Retrieves currency data for a specific day or the current day if no specific date is provided.
     * The start and end times are set to cover the entire day.
     */
    #[Route('/currency/daily', methods: ['GET'])]
    public function getDailyData(Request $request): JsonResponse
    {
        $fsym = $request->query->get('fsym');
        $tsym = $request->query->get('tsym');
        $date = $request->query->get('date');
        if (!$date) {
            $date = (new \DateTime('today'))->format('Y-m-d');
        }

        try {
            $start = new \DateTime($date);
            $end = (clone $start)->modify('+1 day');
            $data = $this->currencyDataManagementService->updateAndRetrieveData($start, $end, $fsym, $tsym);
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    /**
     * Fetches weekly currency data.
     *
     * @Route('/currency/weekly', methods: ['GET'])
     * @param Request $request The current request object.
     * @return JsonResponse The response containing weekly currency data or an error message.
     *
     * Retrieves currency data for a specific week, starting from the specified date or the current date if none is provided.
     * The data covers a seven-day period starting three days before the specified date.
     */
    #[Route('/currency/weekly', methods: ['GET'])]
    public function getWeeklyData(Request $request): JsonResponse
    {
        $fsym = $request->query->get('fsym');
        $tsym = $request->query->get('tsym');
        $weekDate = $request->query->get('week', (new \DateTime('today'))->format('Y-m-d'));

        try {
            // Setting the start and end of the weekly interval
            $start = (new \DateTime($weekDate))->modify('-3 day');
            $end = (clone $start)->modify('+7 day');

            // Retrieving and returning the data
            $data = $this->currencyDataManagementService->updateAndRetrieveData($start, $end, $fsym, $tsym);
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
