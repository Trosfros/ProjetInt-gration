<?php

use Finnhub\Api\DefaultApi;
use Finnhub\Configuration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Dotenv\Dotenv;

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
//this is a very complicated class to handle please bring a lot of coffee if you do want to touch it
class FinnhubMiddleware
{
    private Dotenv $dotenv;
    private string $apiKey;
    private Configuration $config;
    private DefaultApi $client;
    private $symbols = ['NVDA', 'AAPL', 'TSLA', 'MSFT'];
    private PDO $pdo;
    private array $tasks = [];
    private string $lockPath;
    private string $pidPath;
    private string $stopPath;
    private string $logPath;

    public function __construct()
    {
        $this->dotenv = Dotenv::createImmutable(dirname(__DIR__,2));
        $this->dotenv->load(__DIR__ . '/../../.env');

        $this->apiKey = $_ENV['FINNHUB_API_KEY'] ?? getenv('FINNHUB_API_KEY') ?? '';
        $this->config = Configuration::getDefaultConfiguration()->setApiKey('token', $this->apiKey);
        $this->client = new DefaultApi(new Client(), $this->config);
        $this->pdo = getPDO();
 
        $this->lockPath = __DIR__ . '/worker.lock';
        $this->logPath = __DIR__ . '/logs/finnlog.log';
        $this->pidPath = __DIR__ . '/worker.pid';
        $this->stopPath = __DIR__ . '/worker.stop';
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
                $this->log("Failed to save $symbol: " . $e->getMessage());
                $this->pdo->rollBack();
            }
        }
    }
    public function isRunning(): bool {
        $lock = fopen(__DIR__ . '/worker.lock', 'c');
        if (flock($lock, LOCK_EX | LOCK_NB)) {
            flock($lock, LOCK_UN);
            fclose($lock);
            return false;
        }
        fclose($lock);
        return true;

    }
    public function requestStop():void {
        touch($this->stopPath);
    }
    
    public function addTask(callable $task): void {
        $this->tasks[] = $task;
    }

    private function GetStockInfo(): array {
        $results = [];

        foreach ($this->symbols as $symbol) {
            try {
                $quote = $this->client->quote($symbol);

                $results[$symbol] = json_decode(json_encode($quote), true);
            } catch (\Exception $e) {
                $this->log("Finnhub request failed for $symbol: " . $e->getMessage());
            }
        }

        return $results;
    }
    public function startPolling(int $intervalseconds = 3): void {
        if (PHP_SAPI !== 'cli')
        {
            throw new RuntimeException('must run startPolling() from CLI not a web request');
        }
        $lock =fopen($this->lockPath,'c');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $this->log("already running exiting");
            return;
        }
        $this->log("worker started");
        $failures = 0;
        while (true)
            {
                if (file_exists($this->stopPath)) {
                    @unlink($this->stopPath);
                    $this->log("stop requested shutting down");
                    break;
                }
                $start= microtime(true);
                try 
                {
                    $this->updateDB();
                    foreach ($this->tasks as $task){
                        $task();
                    }
                    $failures = 0;
                    $this->log("refresh is ok!");
                }catch (Exception $e){
                    $failures++;
                    $this->log("refresh failed " . $e->getMessage());

                    if ($failure >= 3) {
                        $this->pdo = getPDO(); //reconnecting
                        $failure = 0;
                        $this->log("reconnected to the DB");
                    }
                }
                $timer = $start + $intervalseconds;
                while (microtime(true) < $timer && !file_exists($this->stopPath)) {
                    sleep(1);
                }

                flock($lock, LOCK_UN);
                fclose($lock);
                $this->log("worker stopped");
            }


    }
    private function log(string $message): void
    {
        file_put_contents($this->lockPath, $message . PHP_EOL, FILE_APPEND|LOCK_EX);
    }
}