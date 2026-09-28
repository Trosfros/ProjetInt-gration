<?php 
require_once  "/ProjetInt-gration/src/Middlewares/FinnhubMiddleware.php";
//this file is simply to start the finnhub worker

$finnhub = new FinnhubMiddleware();
//interaval in seconds

$finnhub->startPolling(3);