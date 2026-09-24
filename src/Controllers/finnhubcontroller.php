<?php
use Symfony\Component\Dotenv\Dotenv;
//OK THIS FILE HAS A WORKER TOKEN TO PROTECT IT 
//THIS IS OUR WHOLE INFRASTRUCTURE I MEAN IT'S COOLER WITH SECURITY
//this is ms btw
require_once __DIR__ ."/src/Middlewares/FinnhubMiddleware.php";
$finnhub = new FinnhubMiddleware();
$intervall = 3000000;
require_once __DIR__ . "/vendor/autoload.php";
(new Dotenv())->load(__DIR__ . "/../../.env");

$token = $_ENV['WORKER_TOKEN'] ?? '';
if ($token === '' || !hash_equals($toke, $_POST['token'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['error' => 'forbidden']));
}


function startBackground($scriptlocation = ""): void
{
    if (!file_exists($scriptlocation)) {
        error_log("file doesn't exist ");
        return;
    }
    $php = escapeshellarg($_ENV['PHP_CLI_PATH'] ?? PHP_BINARY);
    $script = escapeshellarg($scriptlocation);
    //checking for os cuz background function is different on each fucking os
    if (PHP_OS_FAMILY === 'Windows') {
        pclose(popen("start /B \"\" $php $script", 'r'));
    } else {
      exec("nohup $php $script > /dev/null 2>&1 &");
    }

}

switch ($_POST['action'] ?? 'status') {
    case 'start':
        if (!$finnhub->isRunning()) { 
            //add script path over there 
            startBackground(__DIR__ . '/src/workers/finnhubworker.php');
            usleep($intervall);
           }
           break;
           case 'stop':
            touch(__DIR__ . '/src/workers/finnhubworker.php');
            break;

}
echo json_encode(['running'=> $finnhub->isRunning()]);