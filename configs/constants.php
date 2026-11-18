<?php 
require_once dirname(__DIR__,1)."/src/DAL/stockDAL.php";
$symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA'];

$stockDal = new StockDAL();
 