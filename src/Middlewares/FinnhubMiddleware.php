<?php

use Finnhub\Api\DefaultApi;
use Finnhub\Configuration;
use GuzzleHttp\Client;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

class FinnhubMiddleware
{
    private Dotenv $dotenv;
    private string $apiKey;
    private Configuration $config;
    private DefaultApi $client;
    private $symbols;

    public function __construct()
    {
        $this->dotenv = new Dotenv();
        $this->dotenv->load(__DIR__ . '/../../.env');

        $this->apiKey = $_ENV['FINNHUB_API_KEY'] ?? getenv('FINNHUB_API_KEY') ?? '';
        $this->config = Configuration::getDefaultConfiguration()->setApiKey('token', $this->apiKey);
        $this->client = new DefaultApi(new Client(), $this->config);
    }

    // this should call the other function in there to update our database
    public function updateDB(): void
    {
    }

    private function GetStockInfo(): array
    {
        if (isset($client))
        {
          $results = $client-­­>quote() 
        }
        

        
        return [];
    }
}
