<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class CryptoCurrencyApiService
{
    private $client;
    private $apiBaseUrl;
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger, string $apiBaseUrl)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiBaseUrl = $apiBaseUrl;
    }

    public function getHistoricalData(string $fsym, string $tsym, \DateTime $start, \DateTime $end): array
    {
        if (empty($fsym) || empty($tsym)) {
            throw new \InvalidArgumentException("Currency symbols 'fsym' and 'tsym' cannot be empty.");
        }

        if ($start >= $end) {
            throw new \InvalidArgumentException("Start date must be earlier than end date.");
        }
        $results = [
            'success' => false,
            'data' => [],
            'error' => ''
        ];

        // Computing amount of hours for setting limit
        $hoursDiff = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
        if ($hoursDiff < 1) {
            throw new \Exception("The time difference must be at least 1 hour.");
        }
        $queryParams = [
            'fsym' => $fsym,
            'tsym' => $tsym,
            'toTs' => $end->getTimestamp(),
            'limit' => (int) $hoursDiff - 1
        ];

        try {
            $response = $this->client->request('GET', $this->apiBaseUrl . '/data/histohour', [
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['Response']) && $data['Response'] === 'Error') {
                $this->logger->error("API error: " . $data['Message']);
                $results['error'] = "API error: " . $data['Message'];
                return $results;
            }

            $results['data'] = $data['Data'];
            $results['success'] = true;
        } catch (GuzzleException $e) {
            $this->logger->error("HTTP request failed: " . $e->getMessage());
            $results['error'] = "HTTP request failed: " . $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->error("Error processing API data: " . $e->getMessage());
            $results['error'] = "Error processing API data: " . $e->getMessage();
        }

        return $results;
    }
}
