<?php
namespace App\Controller;

use App\Service\CurrencyDataManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyDataController extends AbstractController
{
    private $currencyDataManagementService;

    public function __construct(CurrencyDataManagementService $currencyDataManagementService)
    {
        $this->currencyDataManagementService = $currencyDataManagementService;
    }

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
