<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
/**
 * Class CryptoCurrencyApiService
 *
 * This service is used to fetch historical data of a cryptocurrency pair from an API.
 *
 * @package App\Service
 */
class CryptoCurrencyApiService
{
    /**
     * Fetches historical data of a cryptocurrency pair from an API.
     *
     * @param string $fsym The symbol of the first currency in the pair.
     * @param string $tsym The symbol of the second currency in the pair.
     * @param \DateTime $start The start date and time of the data.
     * @param \DateTime $end The end date and time of the data.
     *
     * @return array An array containing the success status, the data, and any error message.
     *
     * @throws \Exception If the time difference between $start and $end is less than 1 hour, caused by API restrictions.
     *
     */
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
            'limit' => $hoursDiff > 1 ? (int) $hoursDiff - 1 : 1
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

            $currencyPair = $fsym . $tsym;
            $results['data'] = array_map(function ($item) use ($currencyPair) {
                return [
                    'time' => $item['time'],
                    'high' => $item['high'],
                    'low' => $item['low'],
                    'open' => $item['open'],
                    'close' => $item['close'],
                    'volumefrom' => $item['volumefrom'],
                    'currency_pair' => $currencyPair,
                ];
            }, $data['Data']);
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
