<?php
/**
 * Minimal Google Analytics 4 Data API client.
 *
 * Deliberately dependency-free (no Composer/vendor folder) — just cURL
 * and OpenSSL, both of which ship with virtually every PHP build,
 * because this needs to run on plain cPanel shared hosting where
 * installing packages isn't always possible.
 *
 * Every function here returns null on any failure (missing config,
 * network error, bad credentials, GA4 API error) rather than throwing,
 * so the admin dashboard always renders — it just hides the analytics
 * section if something's wrong instead of showing a fatal error.
 */

function ga4_enabled(): bool {
    return defined('GA4_PROPERTY_ID') && GA4_PROPERTY_ID !== ''
        && defined('GA4_SERVICE_ACCOUNT_PATH') && is_file(GA4_SERVICE_ACCOUNT_PATH);
}

/** Base64url encode, per the JWT spec (no padding, URL-safe alphabet). */
function ga4_b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Exchanges the service account's private key for a short-lived OAuth2
 * access token, by signing a JWT assertion ourselves (RS256) — this is
 * the same flow Google's official client libraries perform, just
 * written out by hand so no library install is required.
 */
function ga4_access_token(): ?string {
    static $cached = null;
    static $cachedAt = 0;
    if ($cached && (time() - $cachedAt) < 3000) {
        return $cached;
    }

    if (!ga4_enabled()) {
        return null;
    }

    $raw = @file_get_contents(GA4_SERVICE_ACCOUNT_PATH);
    $keyData = $raw ? json_decode($raw, true) : null;
    if (!$keyData || empty($keyData['private_key']) || empty($keyData['client_email'])) {
        return null;
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => $keyData['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];
    $unsigned = ga4_b64url(json_encode($header)) . '.' . ga4_b64url(json_encode($claims));

    $signature = '';
    $signed = @openssl_sign($unsigned, $signature, $keyData['private_key'], 'sha256WithRSAEncryption');
    if (!$signed) {
        return null;
    }
    $jwt = $unsigned . '.' . ga4_b64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) {
        return null;
    }

    $data = json_decode($resp, true);
    if (empty($data['access_token'])) {
        return null;
    }

    $cached = $data['access_token'];
    $cachedAt = time();
    return $cached;
}

/**
 * Calls GA4's runReport endpoint directly.
 * $metrics / $dimensions are GA4 API names, e.g. 'activeUsers', 'screenPageViews', 'pagePath'.
 * Returns the raw decoded JSON response, or null on failure.
 */
function ga4_run_report(array $metrics, array $dimensions = [], string $startDate = '28daysAgo', string $endDate = 'today', int $limit = 10, bool $orderByMetric = true): ?array {
    $token = ga4_access_token();
    if (!$token) {
        return null;
    }

    $body = [
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'metrics' => array_map(fn($m) => ['name' => $m], $metrics),
        'limit' => (string) $limit,
    ];
    if ($dimensions) {
        $body['dimensions'] = array_map(fn($d) => ['name' => $d], $dimensions);
        if ($orderByMetric) {
            $body['orderBys'] = [[
                'metric' => ['metricName' => $metrics[0]],
                'desc' => true,
            ]];
        }
    }

    $ch = curl_init('https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runReport');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) {
        return null;
    }

    return json_decode($resp, true);
}

/**
 * Everything the admin dashboard needs in one call, cached to a temp
 * file for 15 minutes so the dashboard doesn't hit the GA4 API (and
 * its own rate limits) on every single page load.
 */
function ga4_dashboard_summary(): ?array {
    if (!ga4_enabled()) {
        return null;
    }

    $cacheFile = sys_get_temp_dir() . '/pjp_ga4_cache_' . md5(GA4_PROPERTY_ID) . '.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 900) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if ($cached) {
            return $cached;
        }
    }

    $totals = ga4_run_report(['activeUsers', 'screenPageViews', 'engagedSessions', 'eventCount']);
    if (!$totals || empty($totals['rows'])) {
        return null;
    }
    $v = $totals['rows'][0]['metricValues'] ?? [];

    $topPagesRaw = ga4_run_report(['screenPageViews'], ['pagePath'], '28daysAgo', 'today', 8);
    $topPages = [];
    foreach ($topPagesRaw['rows'] ?? [] as $row) {
        $topPages[] = [
            'path' => $row['dimensionValues'][0]['value'] ?? '',
            'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
        ];
    }

    $summary = [
        'activeUsers' => (int) ($v[0]['value'] ?? 0),
        'views' => (int) ($v[1]['value'] ?? 0),
        'engagedSessions' => (int) ($v[2]['value'] ?? 0),
        'eventCount' => (int) ($v[3]['value'] ?? 0),
        'topPages' => $topPages,
        'generatedAt' => time(),
    ];

    @file_put_contents($cacheFile, json_encode($summary));
    return $summary;
}

/**
 * Totals + top pages for an arbitrary, admin-picked date range (used by
 * the Analytics page's date filter). Not cached, since the whole point
 * is picking a specific range on demand — GA4's own per-property quota
 * is generous enough for occasional admin-driven lookups like this.
 */
function ga4_range_summary(string $startDate, string $endDate): ?array {
    $totals = ga4_run_report(['activeUsers', 'screenPageViews', 'engagedSessions', 'eventCount'], [], $startDate, $endDate);
    if (!$totals || empty($totals['rows'])) {
        return null;
    }
    $v = $totals['rows'][0]['metricValues'] ?? [];

    $topPagesRaw = ga4_run_report(['screenPageViews'], ['pagePath'], $startDate, $endDate, 8);
    $topPages = [];
    foreach ($topPagesRaw['rows'] ?? [] as $row) {
        $topPages[] = [
            'path' => $row['dimensionValues'][0]['value'] ?? '',
            'views' => (int) ($row['metricValues'][0]['value'] ?? 0),
        ];
    }

    return [
        'activeUsers' => (int) ($v[0]['value'] ?? 0),
        'views' => (int) ($v[1]['value'] ?? 0),
        'engagedSessions' => (int) ($v[2]['value'] ?? 0),
        'eventCount' => (int) ($v[3]['value'] ?? 0),
        'topPages' => $topPages,
    ];
}

/**
 * Day-by-day values for the same four headline metrics over a date
 * range, for the traffic chart. GA4 returns the 'date' dimension as
 * 'YYYYMMDD' strings and doesn't guarantee row order, so this sorts
 * chronologically before returning.
 */
function ga4_timeseries(string $startDate, string $endDate): ?array {
    $report = ga4_run_report(
        ['activeUsers', 'screenPageViews', 'engagedSessions', 'eventCount'],
        ['date'],
        $startDate,
        $endDate,
        1000,
        false // keep natural/date order rather than sorting by metric value
    );
    if (!$report || empty($report['rows'])) {
        return null;
    }

    $rows = [];
    foreach ($report['rows'] as $row) {
        $raw = $row['dimensionValues'][0]['value'] ?? ''; // 'YYYYMMDD'
        if (strlen($raw) !== 8) {
            continue;
        }
        $rows[] = [
            'date' => $raw,
            'label' => date('j M', strtotime($raw)),
            'activeUsers' => (int) ($row['metricValues'][0]['value'] ?? 0),
            'views' => (int) ($row['metricValues'][1]['value'] ?? 0),
            'engagedSessions' => (int) ($row['metricValues'][2]['value'] ?? 0),
            'eventCount' => (int) ($row['metricValues'][3]['value'] ?? 0),
        ];
    }
    usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));
    return $rows;
}
