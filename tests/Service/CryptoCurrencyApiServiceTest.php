<?php
namespace App\Tests\Service;

use App\Factory\MockAPIDataFactory;
use App\Service\CryptoCurrencyApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CryptoCurrencyApiServiceTest extends TestCase
{
    private $clientMock;
    private $loggerMock;
    private $service;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->service = new CryptoCurrencyApiService($this->clientMock, $this->loggerMock, 'https://api.example.com');
    }

    public function testSuccessfulApiResponse()
    {
        $mockApiResponse = MockAPIDataFactory::createMockApiResponse(5);
        $responseBody = json_encode($mockApiResponse);
        $response = new Response(200, [], $responseBody);
        $this->clientMock->method('request')->willReturn($response);

        $result = $this->service->getHistoricalData('BTC', 'USD', new \DateTime('-1 day'), new \DateTime());

        $this->assertTrue($result['success']);
        $this->assertCount(5, $result['data']);
        foreach ($result['data'] as $item) {
            $this->assertArrayHasKey('time', $item);
            $this->assertArrayHasKey('high', $item);
            $this->assertArrayHasKey('low', $item);
            $this->assertArrayHasKey('open', $item);
            $this->assertArrayHasKey('close', $item);
            $this->assertArrayHasKey('volumefrom', $item);
            $this->assertEquals('BTCUSD', $item['currency_pair']);
        }
    }




    public function testApiError()
    {
        $responseBody = json_encode(['Response' => 'Error', 'Message' => 'Test error']);
        $response = new Response(400, [], $responseBody);
        $this->clientMock->method('request')->willReturn($response);

        $result = $this->service->getHistoricalData('BTC', 'USD', new \DateTime('-1 day'), new \DateTime());

        $this->assertFalse($result['success']);
        $this->assertEquals('API error: Test error', $result['error']);
    }

    public function testHttpError()
    {
        $this->clientMock->method('request')->willThrowException(new \GuzzleHttp\Exception\RequestException('Test HTTP error', new \GuzzleHttp\Psr7\Request('GET', 'test')));
        $this->loggerMock->expects($this->once())->method('error')->with($this->equalTo('HTTP request failed: Test HTTP error'));

        $result = $this->service->getHistoricalData('BTC', 'USD', new \DateTime('-1 day'), new \DateTime());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('HTTP request failed', $result['error']);
    }
    public function testInvalidTimeInterval()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getHistoricalData('BTC', 'USD', new \DateTime(), new \DateTime('-1 day'));
    }
    public function testLogging()
    {
        $responseBody = json_encode(['Response' => 'Error', 'Message' => 'Test error']);
        $response = new Response(400, [], $responseBody);
        $this->clientMock->method('request')->willReturn($response);
        $this->loggerMock->expects($this->once())->method('error')->with($this->equalTo('API error: Test error'));

        $this->service->getHistoricalData('BTC', 'USD', new \DateTime('-1 day'), new \DateTime());
    }
    public function testValidationOfInputData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getHistoricalData('', '', new \DateTime('-1 day'), new \DateTime());
    }
}
