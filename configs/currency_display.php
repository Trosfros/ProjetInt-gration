<?php

function renderCurrencyAmount(float $amount, string $currency = 'CAD'): string
{
    $currency = strtoupper(trim($currency));
    if ($currency === '') {
        $currency = 'CAD';
    }

    $displayCurrency = $_COOKIE['novabank_display_currency'] ?? 'CAD';
    if (!in_array($displayCurrency, ['CAD', 'USD'], true)) {
        $displayCurrency = 'CAD';
    }

    $displayAmount = $amount;
    $formattedCurrency = $currency;
    if (in_array($currency, ['CAD', 'USD'], true)) {
        $cachedRate = getCachedCadUsdReferenceRate();
        if ($displayCurrency === $currency) {
            $formattedCurrency = $displayCurrency;
        } elseif ($cachedRate !== null) {
            $displayAmount = $currency === 'CAD'
                ? $amount * $cachedRate['rate']
                : $amount / $cachedRate['rate'];
            $formattedCurrency = $displayCurrency;
        }
    }

    $formattedAmount = number_format($displayAmount, 2, ',', ' ');
    $amountValue = number_format($amount, 8, '.', '');
    $safeCurrency = htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
    $safeAmountValue = htmlspecialchars($amountValue, ENT_QUOTES, 'UTF-8');
    $safeAmountLabel = htmlspecialchars($formattedAmount . ' ' . $formattedCurrency, ENT_QUOTES, 'UTF-8');

    return '<span class="currency-display" data-currency-value="' . $safeAmountValue
        . '" data-currency-base="' . $safeCurrency . '">' . $safeAmountLabel . '</span>';
}

function getCachedCadUsdReferenceRate(): ?array
{
    static $cacheLoaded = false;
    static $cachedResult = null;
    if ($cacheLoaded) {
        return $cachedResult;
    }
    $cacheLoaded = true;

    $cacheFile = dirname(__DIR__) . '/storage/currency_rate_cache.json';
    if (!is_file($cacheFile)) {
        return null;
    }

    $cacheContents = @file_get_contents($cacheFile);
    $cached = is_string($cacheContents) ? json_decode($cacheContents, true) : null;
    if (!is_array($cached)) {
        error_log('Currency rate cache contains invalid JSON.');
        return null;
    }

    $rate = isset($cached['rate']) && is_numeric($cached['rate']) ? (float) $cached['rate'] : 0.0;
    $date = is_string($cached['date'] ?? null) ? $cached['date'] : '';
    $fetchedAt = is_numeric($cached['fetchedAt'] ?? null) ? (int) $cached['fetchedAt'] : 0;
    $now = time();
    if (
        !is_finite($rate)
        || $rate <= 0
        || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1
        || $fetchedAt <= 0
        || $fetchedAt > $now
        || $now - $fetchedAt > 259200
    ) {
        return null;
    }

    $cachedResult = [
        'rate' => $rate,
        'date' => $date,
        'isStale' => $now - $fetchedAt > 86400,
    ];
    return $cachedResult;
}

function getCadUsdReferenceRate(): ?array
{
    $rootPath = dirname(__DIR__);
    $storagePath = $rootPath . '/storage';
    $cacheFile = $storagePath . '/currency_rate_cache.json';
    $lockFile = $storagePath . '/currency_rate_cache.lock';
    $now = time();
    $cached = null;

    if (is_file($cacheFile)) {
        $cacheContents = @file_get_contents($cacheFile);
        $decodedCache = is_string($cacheContents) ? json_decode($cacheContents, true) : null;
        if (is_array($decodedCache)) {
            $cached = $decodedCache;
        } elseif ($cacheContents !== false) {
            error_log('Currency rate cache contains invalid JSON.');
        }
    }

    $cachedRate = is_array($cached) && isset($cached['rate']) && is_numeric($cached['rate'])
        ? (float) $cached['rate']
        : null;
    $cachedDate = is_array($cached) && is_string($cached['date'] ?? null)
        ? $cached['date']
        : '';
    $cachedFetchedAt = is_array($cached) && is_numeric($cached['fetchedAt'] ?? null)
        ? (int) $cached['fetchedAt']
        : 0;
    $lastAttemptAt = is_array($cached) && is_numeric($cached['lastAttemptAt'] ?? null)
        ? (int) $cached['lastAttemptAt']
        : 0;
    $hasCachedRate = $cachedRate !== null
        && is_finite($cachedRate)
        && $cachedRate > 0
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cachedDate) === 1
        && $cachedFetchedAt > 0
        && $cachedFetchedAt <= $now;
    $cachedAge = $hasCachedRate ? $now - $cachedFetchedAt : PHP_INT_MAX;

    if ($hasCachedRate && $cachedAge <= 86400) {
        return ['rate' => $cachedRate, 'date' => $cachedDate, 'isStale' => false];
    }

    $staleResult = static function () use ($hasCachedRate, $cachedRate, $cachedDate, $cachedAge): ?array {
        return $hasCachedRate && $cachedAge <= 259200
            ? ['rate' => $cachedRate, 'date' => $cachedDate, 'isStale' => true]
            : null;
    };

    if ($lastAttemptAt > 0 && $lastAttemptAt <= $now && $now - $lastAttemptAt < 900) {
        return $staleResult();
    }

    if (is_file($lockFile)) {
        $lockModifiedAt = filemtime($lockFile);
        if ($lockModifiedAt !== false && $now - $lockModifiedAt < 60) {
            return $staleResult();
        }
        @unlink($lockFile);
    }

    $lockHandle = @fopen($lockFile, 'x');
    if ($lockHandle === false) {
        if (!is_file($lockFile)) {
            error_log('Unable to create the currency rate cache lock.');
        }
        return $staleResult();
    }
    fwrite($lockHandle, (string) $now);
    fclose($lockHandle);

    $storeCache = static function (array $cacheData) use ($storagePath, $cacheFile): void {
        if (!is_dir($storagePath) && !@mkdir($storagePath, 0755, true) && !is_dir($storagePath)) {
            error_log('Unable to create the currency rate cache directory.');
            return;
        }

        $encodedCache = json_encode($cacheData);
        if (!is_string($encodedCache) || @file_put_contents($cacheFile, $encodedCache, LOCK_EX) === false) {
            error_log('Unable to write the currency rate cache.');
        }
    };

    try {
        $curl = curl_init('https://api.frankfurter.dev/v1/latest?base=CAD&symbols=USD');
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize the currency rate request.');
        }

        $caBundlePath = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];
        if (is_string($caBundlePath) && $caBundlePath !== '') {
            $options[CURLOPT_CAINFO] = $caBundlePath;
        }
        if (!curl_setopt_array($curl, $options)) {
            curl_close($curl);
            throw new RuntimeException('Unable to configure the currency rate request.');
        }

        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if (!is_string($response) || $httpStatus !== 200) {
            throw new RuntimeException('Currency rate service returned HTTP ' . $httpStatus . ': ' . $curlError);
        }

        $rateData = json_decode($response, true);
        $rate = is_array($rateData) && isset($rateData['rates']['USD']) && is_numeric($rateData['rates']['USD'])
            ? (float) $rateData['rates']['USD']
            : 0.0;
        $date = is_array($rateData) && is_string($rateData['date'] ?? null)
            ? $rateData['date']
            : '';

        if (
            !is_array($rateData)
            || ($rateData['base'] ?? null) !== 'CAD'
            || !is_finite($rate)
            || $rate <= 0
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1
        ) {
            throw new RuntimeException('Currency rate service returned invalid CAD/USD data.');
        }

        $storeCache([
            'rate' => $rate,
            'date' => $date,
            'fetchedAt' => $now,
            'lastAttemptAt' => $now,
        ]);
        return ['rate' => $rate, 'date' => $date, 'isStale' => false];
    } catch (Throwable $e) {
        error_log('Unable to load the CAD/USD reference rate: ' . $e->getMessage());
        $storeCache([
            'rate' => $hasCachedRate ? $cachedRate : null,
            'date' => $hasCachedRate ? $cachedDate : null,
            'fetchedAt' => $hasCachedRate ? $cachedFetchedAt : 0,
            'lastAttemptAt' => $now,
        ]);
        return $staleResult();
    } finally {
        @unlink($lockFile);
    }
}
