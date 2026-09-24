<?php

use Finnhub\Api\DefaultApi;
use Finnhub\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . "/configs/database.php";
require_once __DIR__ . '/vendor/autoload.php';

class FinnhubMiddleware
{
    private Dotenv $dotenv;
    private string $apiKey;
    private Configuration $config;
    private DefaultApi $client;
    private $symbols = ['NVDA', 'AAPL', 'TSLA', 'MSFT'];
    private PDO $pdo;

    public function __construct()
    {
        $this->dotenv = new Dotenv();
        $this->dotenv->load(__DIR__ . '/../../.env');

        $this->apiKey = $_ENV['FINNHUB_API_KEY'] ?? getenv('FINNHUB_API_KEY') ?? '';
        $this->config = Configuration::getDefaultConfiguration()->setApiKey('token', $this->apiKey);
        $this->client = new DefaultApi(new Client(), $this->config);
        $this->pdo = getPDO();
    }

    // this should call the other function in there to update our database
    public function updateDB(): void
    {
        $quotes = $this->getStockInfo();

        if (empty($quotes)) {
            return;
        }

        $stmt = $this->pdo->prepare('CALL addStock(?, ?, ?)');
        $this->pdo->beginTransaction();
        foreach ($quotes as $symbol => $data) {
            try {

                $stmt->execute([
                    $symbol,
                    $data['c'],
                    $data['d'],
                ]);
                $stmt->closeCursor();
                $this->pdo->commit();

            } catch (PDOException $e) {
                error_log("Failed to save $symbol: " . $e->getMessage());
                $this->pdo->rollBack();
            }
        }
    }

    private function GetStockInfo(): array
    {
        $results = [];

        foreach ($this->symbols as $symbol) {
            try {
                $quote = $this->client->quote($symbol);

                $results[$symbol] = json_decode(json_encode($quote), true);
            } catch (\Exception $e) {
                error_log("Finnhub request failed for $symbol: " . $e->getMessage());
            }
        }

        return $results;
    }
    public function startPolling(int $interval = 3): void
    {
        while (true) {
            $start = microtime(true);

            try {

                $this->updateDB();


            } catch (\Exception $e) {
                error_log("" . $e->getMessage());
            }
            $remaining = $interval - (microtime(true) - $start);
            if ($remaining > 0) {
                usleep($remaining * 1000000);
            }

        }


    }
}