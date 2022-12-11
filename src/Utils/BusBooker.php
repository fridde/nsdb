<?php

namespace App\Utils;

use App\Settings;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BusBooker
{
    private const BUS_ROUTES_PATH = '/data/bus_routes.json';

    public function __construct(
        private HttpClientInterface $client,
        private Settings $settings,
        private string $baseUrl,
        private string $apiKey,
        private int $customerNr
    )
    {
    }

    public function getBookedTrips(): array
    {
        $query = [
            'auth' => $this->apiKey,
            'head' => [
                'customerNo' => $this->customerNr,
                'id' => $this->settings->get('last_bus_order_id')
            ]
        ];

        $response = $this->client->request('GET', $this->baseUrl . '/api/syncRequest', ['query' => $query]);
    }


}