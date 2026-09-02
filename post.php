<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;
try {
    $now = pjp_now();
    $stmt = pjp_db()->prepare(
        "SELECT * FROM posts WHERE slug = ? AND status = 'published'
         AND (display_start IS NULL OR display_start <= ?) AND (display_end IS NULL OR display_end >= ?)"
    );
    $stmt->execute([$slug, $now, $now]);
    $post = $stmt->fetch();
} catch (Throwable $e) {
    $post = null;
}

if (!$post) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title><?= $post ? h($post['title']) . ' | ' : '' ?>PT. Pengembangan Jaya Papua</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="<?= $post ? h($post['excerpt']) : 'Post not found.' ?>">
  <link href="img/icon/PJP Favicon.ico" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="css/design.css" rel="stylesheet">
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-GMH01K43SG"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', 'G-GMH01K43SG');
  </script>
</head>

<body>

  <div class="topbar">
    <div class="container-row">
      <div class="topbar-contact">
        <a href="tel:+629013261088">☎ +62 901 326 1088</a>
        <a href="mailto:marketing@pjp.co.id">✉ marketing@pjp.co.id</a>
      </div>
      <div class="topbar-links">
        <a href="index.html">Home</a><span>/</span>
        <a href="terms.html">Terms</a><span>/</span>
        <a href="privacy.html">Privacy</a><span>/</span>
        <a href="contact.html">Support</a>
      </div>
    </div>
  </div>

  <nav class="main-nav" id="navbar">
    <a href="index.html" class="brand"><img src="img/icon/PJP logo.png" alt="PJP logo">PT. PJP</a>
    <div class="nav-links" id="navLinks">
      <a href="index.html">Home</a>
      <a href="blog.php" class="active">Blog</a>
      <a href="about.html">About Us</a>
      <a href="service.html">Our Services</a>
      <a href="relations.html">Relations</a>
      <a href="galleries.html">Galleries</a>
      <a href="contact.html">Contact Us</a>
      <div class="lang-dropdown">
        <button class="lang-btn">Language ▾</button>
        <div class="lang-menu"><a href="index-indonesian.html">Bahasa Indonesia</a></div>
      </div>
    </div>
    <div class="nav-cta">
      <a href="contact.html" class="btn btn-primary btn-sm">Get A Quote</a>
      <button class="nav-toggle" id="navToggle" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </nav>

  <div id="contact-popup"><a href="tel:+629013261088" aria-label="Call PJP"><i>📞</i></a></div>

  <?php if (!$post): ?>
    <div class="status-page">
      <div>
        <div class="code">404</div>
        <h1>Post Not Found</h1>
        <p>This post may have expired or been removed.</p>
        <a href="blog.php" class="btn btn-primary">Back To Blog</a>
      </div>
    </div>
  <?php else: ?>
    <header class="page-header">
      <div class="container">
        <div class="breadcrumb"><a href="index.html">Home</a> / <a href="blog.php">Blog</a> / <span class="current"><?= h($post['title']) ?></span></div>
        <h1><?= h($post['title']) ?></h1>
        <div class="post-meta">
          <span>📅 <?= date('d M Y', strtotime($post['display_start'] . ' UTC') ?: time()) ?></span>
          <?php if ($post['category']): ?><span>🏷️ <?= h($post['category']) ?></span><?php endif; ?>
        </div>
      </div>
    </header>

    <section class="section-pad">
      <div class="container content-block">
        <?php if ($post['image']): ?>
          <figure><img src="<?= h($post['image']) ?>" alt="<?= h($post['title']) ?>"></figure>
        <?php endif; ?>
        <?= $post['body'] ?>
      </div>
    </section>

    <div class="text-center" style="padding-bottom:4rem;">
      <a href="blog.php" class="card-link" style="display:inline-flex; justify-content:center;">&larr; Back to Blog</a>
    </div>
  <?php endif; ?>

  <footer class="site-footer">
    <div class="footer-grid">
      <div class="footer-brand">
        <span class="brand"><img src="img/icon/PJP logo.png" alt="PJP logo">Pengembangan Jaya Papua</span>
        <p class="footer-tagline" style="color:#A3B5C9;font-weight:700;font-size:0.85rem;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:0.75rem;">Excellent Service for Excellent Customer</p>
        <p><em>"To be the big catering business company on a national scale, competitive, reliable in carrying out requests, desires and expectations to achieve customer satisfaction."</em></p>
        <div class="footer-newsletter">
          <form action="newsletter.php" method="POST">
            <input name="email" type="email" placeholder="Your email" required>
            <button type="submit">Sign Up</button>
          </form>
        </div>
      </div>
      <div class="footer-col">
        <h4>Get In Touch</h4>
        <p>📍 Jl. C. Heatubun No. 1, Timika, Papua Tengah 99910</p>
        <p>📞 +62 901 326 1088</p>
        <p>✉️ marketing@pjp.co.id</p>
      </div>
      <div class="footer-col">
        <h4>Our Services</h4>
        <a href="industrial-catering.html">Food Production &amp; Catering</a>
        <a href="in-flight-catering.html">In-Flight Catering</a>
        <a href="event-catering.html">Event Catering</a>
        <a href="labor-supply.html">Papuan Manpower Development</a>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <a href="about.html">About Us</a>
        <a href="contact.html">Contact Us</a>
        <a href="service.html">Our Services</a>
        <a href="terms.html">Terms &amp; Condition</a>
        <div class="social-row" style="margin-top:1.25rem;">
          <a href="https://www.tiktok.com/@pjp_timika" aria-label="TikTok">🎵</a>
          <a href="https://web.facebook.com/people/Pengembangan-Jaya-Papua/pfbid0RxkqtmRfekScsiSTLCp7HpqcV9NhUag1waWGqdRMzGtbGsHSi4RsVpccSyjcG9FVl/" aria-label="Facebook">f</a>
          <a href="https://www.instagram.com/pt.pjpcatering/" aria-label="Instagram">📷</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div>&copy; 2026 <a href="index.html">PT. Pengembangan Jaya Papua</a>. All Rights Reserved.</div>
      <div class="links">
        <a href="privacy.html">Privacy Policy</a>
        <a href="terms.html">Terms of Service</a>
        <a href="https://industrial-infrastructure-integration.vercel.app/" target="_blank" rel="noopener">Design by III</a>
      </div>
    </div>
  </footer>

  <button class="back-to-top" aria-label="Back to top">&uarr;</button>
  <script src="js/main.js"></script>
</body>

</html>
