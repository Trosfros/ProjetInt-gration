<?php 
require_once dirname(__DIR__,1)."/src/DAL/stockDAL.php";
$symbols = ['NVDA', 'AAPL', 'TSLA', 'MSFT'];

$stockDal = new StockDAL();