<?php 

use Composer\CaBundle\CaBundle;
function getMarketdata(string $rootPath, string $apiKey, array $symbols, int $cacheLifetime = 60):array
{
    $marketData = [];
    $cacheFile = $rootPath . '/storage/market_cache.json';
    $cacheLockFile = $rootPath . '/storage/market_cache.lock';


try {
  clearstatcache();

  if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
    $marketData = json_decode(file_get_contents($cacheFile), true) ?? [];
  } else {
    $cacheDirectory = dirname($cacheFile);
    if (!is_dir($cacheDirectory)) {
      mkdir($cacheDirectory, 0755, true);
    }

    if (is_file($cacheFile)) {
      $marketData = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    
    if (is_file($cacheLockFile) && (time() - filemtime($cacheLockFile) >= $cacheLifetime)) {
      @unlink($cacheLockFile);
    }

  
    $lockHandle = ($apiKey !== '') ? @fopen($cacheLockFile, 'x') : false;

    if ($lockHandle) {
      fwrite($lockHandle, (string) time());
      fclose($lockHandle);

      try {
        $multi   = curl_multi_init();
        $handles = [];
        $caPath  = CaBundle::getSystemCaRootBundlePath();

        foreach ($symbols as $symbol) {
          $ch = curl_init('https://finnhub.io/api/v1/quote?symbol=' . urlencode($symbol) . '&token=' . urlencode($apiKey));
          curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CAINFO         => $caPath,
          ]);
          curl_multi_add_handle($multi, $ch);
          $handles[$symbol] = $ch;
        }

        do {
          $status = curl_multi_exec($multi, $running);
          if ($running) {
            curl_multi_select($multi, 1.0);
          }
        } while ($running && $status === CURLM_OK);

        $freshData = [];
        $stockId   = 0;
        foreach ($handles as $symbol => $ch) {
          $stockId++;
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $response = curl_multi_getcontent($ch);

          if ($httpCode !== 200) {
            error_log("Finnhub $symbol: HTTP $httpCode " . curl_error($ch));
          }

          $quote = ($httpCode === 200 && $response) ? json_decode($response, true) : null;
          if (!empty($quote['c'])) {
            $freshData[] = [
              'id'     => $stockId,
              'symbol' => $symbol,
              'price'  => number_format((float) $quote['c'], 2, '.', ''),
              'change' => round((float) ($quote['dp'] ?? 0), 2),
            ];
          }

          curl_multi_remove_handle($multi, $ch);
          curl_close($ch);
        }
        curl_multi_close($multi);

        if (!empty($freshData)) {
          $marketData = $freshData;
          file_put_contents($cacheFile, json_encode($marketData), LOCK_EX);
        } else {
          touch($cacheFile); 
        }
      } finally {
        @unlink($cacheLockFile);
      }
    }
  }
} catch (Throwable $e) {
  error_log('Market cache error: ' . $e->getMessage());
}
return $marketData;
}