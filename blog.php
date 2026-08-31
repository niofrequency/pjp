<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$now = pjp_now();
$posts = pjp_db()->prepare(
    "SELECT * FROM posts WHERE status = 'published'
     AND (display_start IS NULL OR display_start <= ?) AND (display_end IS NULL OR display_end >= ?)
     ORDER BY display_start DESC"
);
$posts->execute([$now, $now]);
$posts = $posts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Blog | PT. Pengembangan Jaya Papua</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="Latest updates and announcements from PT. Pengembangan Jaya Papua.">
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
        <a href="tel:+629013261089">☎ +62 901 326 1089</a>
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

  <div id="contact-popup"><a href="tel:+629013261089" aria-label="Call PJP"><i>📞</i></a></div>

  <header class="page-header">
    <div class="container">
      <div class="breadcrumb"><a href="index.html">Home</a> / <span class="current">Blog</span></div>
      <h1>Blog</h1>
    </div>
  </header>

  <section class="section-pad">
    <div class="container">
      <?php if (!$posts): ?>
        <div class="admin-empty" style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
          <p>No posts published yet — check back soon.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-3">
          <?php foreach ($posts as $p): ?>
          <div class="media-card">
            <?php if ($p['image']): ?>
              <div class="thumb"><img src="<?= h($p['image']) ?>" alt="<?= h($p['title']) ?>" loading="lazy"></div>
            <?php endif; ?>
            <div class="body">
              <?php if ($p['category']): ?><span class="tag"><?= h($p['category']) ?></span><?php endif; ?>
              <h3><?= h($p['title']) ?></h3>
              <?php if ($p['excerpt']): ?><p><?= h($p['excerpt']) ?></p><?php endif; ?>
              <a href="post.php?slug=<?= urlencode($p['slug']) ?>" class="card-link">Read More &rarr;</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <p class="text-center" style="margin-top:3rem;">
        Looking for older stories? <a href="nurdin.html" class="card-link" style="display:inline-flex;">Browse the story archive &rarr;</a>
      </p>
    </div>
  </section>

  <footer class="site-footer">
    <div class="footer-grid">
      <div class="footer-brand">
        <span class="brand"><img src="img/icon/PJP logo.png" alt="PJP logo">Pengembangan Jaya Papua</span>
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
        <p>📍 Jl. Cendrawasih-SP3 Karang Senang, Distrik Kuala Kencana, Papua 99968</p>
        <p>📞 +62 901 326 1089</p>
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
