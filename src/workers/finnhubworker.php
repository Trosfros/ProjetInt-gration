<?php 
require_once dirname(__DIR__,2) . "/src/Middlewares/FinnhubMiddleware.php";
//this file is simply to start the finnhub worker

$finnhub = new FinnhubMiddleware();
//interaval in seconds

$finnhub->startPolling(60);