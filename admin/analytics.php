<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ga4.php';
pjp_start_session();
pjp_require_login();

$ga4 = ga4_dashboard_summary();

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

<?php if ($ga4): ?>
  <div class="admin-stat-grid">
    <div class="admin-stat-card">
      <div class="num"><?= number_format($ga4['activeUsers']) ?></div>
      <div class="label">Active Users (28d)</div>
    </div>
    <div class="admin-stat-card">
      <div class="num"><?= number_format($ga4['views']) ?></div>
      <div class="label">Page Views (28d)</div>
    </div>
    <div class="admin-stat-card">
      <div class="num"><?= number_format($ga4['engagedSessions']) ?></div>
      <div class="label">Engaged Sessions (28d)</div>
    </div>
    <div class="admin-stat-card">
      <div class="num"><?= number_format($ga4['eventCount']) ?></div>
      <div class="label">Events (28d)</div>
    </div>
  </div>

  <div class="admin-card">
    <h3 style="margin-bottom:1.25rem;">Top Pages (28 days)</h3>
    <?php if (!$ga4['topPages']): ?>
      <p class="admin-empty">No page view data for this period yet.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Page</th><th>Views</th></tr></thead>
        <tbody>
          <?php foreach ($ga4['topPages'] as $p): ?>
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
    Last updated <?= pjp_fmt_dt(gmdate('Y-m-d H:i:s', $ga4['generatedAt'])) ?> — refreshes automatically every 15 minutes.
    For real-time visitors, minute-by-minute detail, or deeper breakdowns (traffic sources, devices, locations),
    use <a href="https://analytics.google.com/" target="_blank" rel="noopener">analytics.google.com</a> directly.
  </p>

<?php elseif (ga4_enabled()): ?>
  <div class="admin-card">
    <h3 style="margin-bottom:0.75rem;">Connected, but couldn't fetch data</h3>
    <p class="muted" style="margin-bottom:1rem;">GA4_PROPERTY_ID and the service account key file are both set, but the last request to Google's API didn't return data. Double-check:</p>
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
