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
        $start = new \DateTime('-1 hours');
        $end = new \DateTime();

        try {
            $data = $this->currencyDataManagementService->updateAndRetrieveData($start, $end, $fsym, $tsym);
            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


}