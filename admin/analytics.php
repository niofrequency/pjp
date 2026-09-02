<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ga4.php';
pjp_start_session();
pjp_require_login();

/** Validate a YYYY-MM-DD date string; returns it unchanged if valid, else null. */
function ga4_valid_date(?string $value): ?string {
    if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

$today = date('Y-m-d');
$defaultStart = date('Y-m-d', strtotime('-27 days'));

$start = ga4_valid_date($_GET['start'] ?? null) ?? $defaultStart;
$end = ga4_valid_date($_GET['end'] ?? null) ?? $today;
// Keep the range sane: clamp both to "today" first, then order them —
// clamping only $end before the swap could leave $start after it again
// for a fully-future range.
if ($start > $today) {
    $start = $today;
}
if ($end > $today) {
    $end = $today;
}
if ($start > $end) {
    [$start, $end] = [$end, $start];
}

$summary = null;
$series = null;
if (ga4_enabled()) {
    $summary = ga4_range_summary($start, $end);
    $series = ga4_timeseries($start, $end);
}

$page_title = 'Analytics';
$active_nav = 'analytics';
require __DIR__ . '/partials/header.php';
?>
<div class="admin-header-row">
  <div>
    <h1>Analytics</h1>
    <p>Live Google Analytics data for pjp.co.id, pulled directly from GA4.</p>
  </div>
</div>

<div id="ga4LoadingOverlay" class="ga4-loading-overlay" hidden>
  <div class="ga4-spinner"></div>
  <p>Loading analytics&hellip;</p>
</div>

<?php if (ga4_enabled()): ?>
  <form method="GET" id="ga4DateForm" class="admin-card" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap; margin-bottom:2rem;">
    <div class="field" style="margin-bottom:0;">
      <label for="start">From</label>
      <input type="date" id="start" name="start" value="<?= h($start) ?>" max="<?= h($today) ?>" style="width:auto; padding:0.7rem 1rem; border-radius:12px; border:1.5px solid var(--border-soft); font-family:inherit;">
    </div>
    <div class="field" style="margin-bottom:0;">
      <label for="end">To</label>
      <input type="date" id="end" name="end" value="<?= h($end) ?>" max="<?= h($today) ?>" style="width:auto; padding:0.7rem 1rem; border-radius:12px; border:1.5px solid var(--border-soft); font-family:inherit;">
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <div class="admin-table-actions" style="margin-left:auto;">
      <a href="analytics.php?start=<?= h(date('Y-m-d', strtotime('-6 days'))) ?>&amp;end=<?= h($today) ?>" class="btn btn-outline btn-sm ga4-quick-range">Last 7 days</a>
      <a href="analytics.php?start=<?= h(date('Y-m-d', strtotime('-27 days'))) ?>&amp;end=<?= h($today) ?>" class="btn btn-outline btn-sm ga4-quick-range">Last 28 days</a>
      <a href="analytics.php?start=<?= h(date('Y-m-01')) ?>&amp;end=<?= h($today) ?>" class="btn btn-outline btn-sm ga4-quick-range">This month</a>
      <a href="analytics.php?start=<?= h(date('Y-m-d', strtotime('-89 days'))) ?>&amp;end=<?= h($today) ?>" class="btn btn-outline btn-sm ga4-quick-range">Last 90 days</a>
    </div>
  </form>
  <script>
    (function () {
      var overlay = document.getElementById('ga4LoadingOverlay');
      var form = document.getElementById('ga4DateForm');
      function showLoading() { overlay.hidden = false; }
      if (form) form.addEventListener('submit', showLoading);
      document.querySelectorAll('.ga4-quick-range').forEach(function (a) {
        a.addEventListener('click', showLoading);
      });
      // Browsers can restore the previous page from cache (bfcache) when
      // navigating back here — make sure a stuck-visible overlay doesn't
      // persist onto that restored view.
      window.addEventListener('pageshow', function () { overlay.hidden = true; });
    })();
  </script>
<?php endif; ?>

<?php if ($summary): ?>
  <div class="admin-stat-grid" id="ga4StatGrid">
    <div class="admin-stat-card ga4-metric-card active" data-metric="activeUsers" role="button" tabindex="0">
      <div class="num"><?= number_format($summary['activeUsers']) ?></div>
      <div class="label">Active Users</div>
    </div>
    <div class="admin-stat-card ga4-metric-card" data-metric="views" role="button" tabindex="0">
      <div class="num"><?= number_format($summary['views']) ?></div>
      <div class="label">Page Views</div>
    </div>
    <div class="admin-stat-card ga4-metric-card" data-metric="engagedSessions" role="button" tabindex="0">
      <div class="num"><?= number_format($summary['engagedSessions']) ?></div>
      <div class="label">Engaged Sessions</div>
    </div>
    <div class="admin-stat-card ga4-metric-card" data-metric="eventCount" role="button" tabindex="0">
      <div class="num"><?= number_format($summary['eventCount']) ?></div>
      <div class="label">Events</div>
    </div>
  </div>

  <?php if ($series): ?>
    <div class="admin-card" style="margin-bottom:2rem;">
      <p class="muted" style="margin-bottom:1rem; font-size:0.85rem;">Click a card above to switch which metric the chart shows.</p>
      <svg id="ga4Chart" viewBox="0 0 1000 300" style="width:100%; height:auto; overflow:visible;"></svg>
    </div>
    <script>
      (function () {
        var series = <?= json_encode($series) ?>;
        var svg = document.getElementById('ga4Chart');
        var cards = document.querySelectorAll('.ga4-metric-card');
        var W = 1000, H = 300, PAD_L = 44, PAD_B = 28, PAD_T = 12, PAD_R = 8;

        function render(metric) {
          var values = series.map(function (d) { return d[metric]; });
          var max = Math.max.apply(null, values.concat([1]));
          var innerW = W - PAD_L - PAD_R;
          var innerH = H - PAD_T - PAD_B;
          var n = series.length;
          var stepX = n > 1 ? innerW / (n - 1) : 0;

          function x(i) { return PAD_L + i * stepX; }
          function y(v) { return PAD_T + innerH - (v / max) * innerH; }

          var points = values.map(function (v, i) { return x(i) + ',' + y(v); }).join(' ');
          var areaPoints = points + ' ' + x(n - 1) + ',' + (PAD_T + innerH) + ' ' + x(0) + ',' + (PAD_T + innerH);

          // Y-axis gridlines/labels (4 bands)
          var gridLines = '';
          var yLabels = '';
          for (var g = 0; g <= 4; g++) {
            var gv = Math.round(max * g / 4);
            var gy = y(gv);
            gridLines += '<line x1="' + PAD_L + '" y1="' + gy + '" x2="' + (W - PAD_R) + '" y2="' + gy + '" stroke="rgba(15,41,66,0.08)" stroke-width="1"/>';
            yLabels += '<text x="' + (PAD_L - 8) + '" y="' + (gy + 4) + '" text-anchor="end" font-size="11" fill="#566D85">' + gv.toLocaleString('en-US') + '</text>';
          }

          // X-axis labels: first, middle, last (avoids crowding on wide ranges)
          var xLabels = '';
          [0, Math.floor((n - 1) / 2), n - 1].forEach(function (i, idx, arr) {
            if (i < 0 || (idx > 0 && i === arr[idx - 1])) return;
            xLabels += '<text x="' + x(i) + '" y="' + (H - 6) + '" text-anchor="' + (i === 0 ? 'start' : i === n - 1 ? 'end' : 'middle') + '" font-size="11" fill="#566D85">' + series[i].label + '</text>';
          });

          svg.innerHTML =
            gridLines + yLabels + xLabels +
            '<polygon points="' + areaPoints + '" fill="rgba(26,62,97,0.10)"/>' +
            '<polyline points="' + points + '" fill="none" stroke="#0F2942" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>' +
            values.map(function (v, i) {
              return '<circle cx="' + x(i) + '" cy="' + y(v) + '" r="3" fill="#0F2942"><title>' + series[i].label + ': ' + v.toLocaleString('en-US') + '</title></circle>';
            }).join('');
        }

        cards.forEach(function (card) {
          function activate() {
            cards.forEach(function (c) { c.classList.remove('active'); });
            card.classList.add('active');
            render(card.getAttribute('data-metric'));
          }
          card.addEventListener('click', activate);
          card.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); } });
        });

        render('activeUsers');
      })();
    </script>
  <?php endif; ?>

  <div class="admin-card">
    <h3 style="margin-bottom:1.25rem;">Top Pages</h3>
    <?php if (!$summary['topPages']): ?>
      <p class="admin-empty">No page view data for this period yet.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Page</th><th>Views</th></tr></thead>
        <tbody>
          <?php foreach ($summary['topPages'] as $p): ?>
          <tr>
            <td><?= h($p['path']) ?></td>
            <td><?= number_format($p['views']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <p class="muted" style="margin-top:1rem; font-size:0.85rem;">
    Showing <?= h(date('d M Y', strtotime($start))) ?> &ndash; <?= h(date('d M Y', strtotime($end))) ?>.
    For real-time visitors, minute-by-minute detail, or deeper breakdowns (traffic sources, devices, locations),
    use <a href="https://analytics.google.com/" target="_blank" rel="noopener">analytics.google.com</a> directly.
  </p>

<?php elseif (ga4_enabled()): ?>
  <div class="admin-card">
    <h3 style="margin-bottom:0.75rem;">Connected, but couldn't fetch data</h3>
    <p class="muted" style="margin-bottom:1rem;">GA4_PROPERTY_ID and the service account key file are both set, but the last request to Google's API didn't return data for this date range. Double-check:</p>
    <ul style="padding-left:1.5rem; color:var(--text-muted);">
      <li style="margin-bottom:0.5rem;">The service account's email (inside the JSON key file, field <code>client_email</code>) has been added as a <strong>Viewer</strong> under GA4 Admin &rarr; Property Access Management.</li>
      <li style="margin-bottom:0.5rem;"><code>GA4_PROPERTY_ID</code> in <code>includes/config.php</code> is the plain numeric Property ID (GA4 Admin &rarr; Property Settings) — not the <code>G-XXXXXXX</code> measurement ID.</li>
      <li style="margin-bottom:0.5rem;">The "Google Analytics Data API" is enabled in the Google Cloud project that owns the service account.</li>
      <li>Your host allows outbound HTTPS requests from PHP (cURL to googleapis.com) — rare to block, but some hosts do.</li>
    </ul>
  </div>

<?php else: ?>
  <div class="admin-card">
    <h3 style="margin-bottom:0.75rem;">Not connected yet</h3>
    <p class="muted" style="margin-bottom:1.25rem;">Shows your site's Google Analytics data right here once set up — a one-time process on Google's side, about 10 minutes:</p>
    <ol style="padding-left:1.5rem; color:var(--text-muted); line-height:1.9;">
      <li><a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a> &rarr; create or pick a project &rarr; <strong>APIs &amp; Services &rarr; Library</strong> &rarr; enable <strong>"Google Analytics Data API"</strong>.</li>
      <li>Same project &rarr; <strong>APIs &amp; Services &rarr; Credentials &rarr; Create Credentials &rarr; Service Account</strong>. Any name is fine; no roles needed.</li>
      <li>Open that service account &rarr; <strong>Keys &rarr; Add Key &rarr; Create new key &rarr; JSON</strong>. This downloads a <code>.json</code> file — keep it safe, it's a credential.</li>
      <li><a href="https://analytics.google.com/" target="_blank" rel="noopener">Google Analytics</a> &rarr; Admin &rarr; your GA4 property &rarr; <strong>Property Access Management &rarr; "+"</strong> &rarr; add the service account's email (the <code>client_email</code> field inside that JSON file) as a <strong>Viewer</strong>.</li>
      <li>Still in GA4 Admin &rarr; <strong>Property Settings</strong> &rarr; copy the <strong>Property ID</strong> (a plain number, e.g. <code>123456789</code> — not the <code>G-XXXXXXX</code> measurement ID already on your site).</li>
      <li>Upload the downloaded JSON file via cPanel File Manager to <strong>one level above <code>public_html</code></strong> (e.g. <code>/home/YOURCPANELUSER/ga4-service-account.json</code>) — never inside <code>public_html</code>, since anything there is reachable by URL.</li>
      <li>Open <code>includes/config.php</code>, set <code>GA4_PROPERTY_ID</code> to the number from step 5, save, and reload this page.</li>
    </ol>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
