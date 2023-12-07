<?php
namespace App\Factory;

class MockAPIDataFactory
{
    public static function createMockApiResponse(int $numItems = 1): array
    {
        $data = [];
        for ($i = 0; $i < $numItems; $i++) {
            $data[] = [
                'time' => time() - 3600 * $i,
                'high' => mt_rand(30000, 40000),
                'low' => mt_rand(28000, 30000),
                'open' => mt_rand(29000, 31000),
                'close' => mt_rand(29500, 30500),
                'volumefrom' => mt_rand(50, 150),
            ];
        }

        return ['Data' => $data];
    }
}
