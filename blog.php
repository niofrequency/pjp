<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Dynamic, admin-written posts. Wrapped defensively: if the database
// backend has any trouble (e.g. a hosting misconfiguration), the page
// still renders normally with the story archive below rather than
// erroring out for every visitor.
$posts = [];
try {
    $now = pjp_now();
    $stmt = pjp_db()->prepare(
        "SELECT * FROM posts WHERE status = 'published'
         AND (display_start IS NULL OR display_start <= ?) AND (display_end IS NULL OR display_end >= ?)
         ORDER BY display_start DESC"
    );
    $stmt->execute([$now, $now]);
    $posts = $stmt->fetchAll();
} catch (Throwable $e) {
    $posts = [];
}

// Story archive: the site's existing news/blog articles, always shown so
// there's something to browse even before any admin post exists.
$archive = [
    ['img' => 'img/nurdin19.jpg', 'tag' => 'Community', 'title' => "Nurdin's Farewell", 'href' => 'nurdin.html'],
    ['img' => 'img/fire01.jpg', 'tag' => 'Safety', 'title' => 'Fire Safety', 'href' => 'fire.html'],
    ['img' => 'img/christmas3.JPG', 'tag' => 'Culture', 'title' => 'Christmas', 'href' => 'christmas.html'],
    ['img' => 'img/jobfair-7.jpeg', 'tag' => 'Careers', 'title' => 'Job Fair', 'href' => 'job-fair.html'],
    ['img' => 'img/airfast-audit.jpeg', 'tag' => 'Aviation', 'title' => 'Airfast Audit', 'href' => 'in-flight-audit.html'],
    ['img' => 'img/7mosez.jpg', 'tag' => 'Safety', 'title' => 'Mozes Airport Food Check', 'href' => 'airport-security.html'],
    ['img' => 'gallery/gedung PJK3 thumbnail.JPG', 'tag' => 'Facilities', 'title' => 'JPP New Training Center', 'href' => 'gedungJPP.html'],
    ['img' => 'img/futsal9-thumbnail.JPG', 'tag' => 'Community', 'title' => 'PJP Futsal Club', 'href' => 'futsal.html'],
    ['img' => 'img/papuan manpower thumbnail.JPG', 'tag' => 'Careers', 'title' => 'Papuan Job Opportunity', 'href' => 'papuan-manpower-development.html'],
    ['img' => 'img/thumbnail-iso.png', 'tag' => 'Quality', 'title' => 'ISO Certifications', 'href' => 'iso-certifications.html'],
    ['img' => 'img/thumbnail-indpendence.png', 'tag' => 'Culture', 'title' => 'Independence Day', 'href' => 'festival.html'],
    ['img' => 'img/thumbnail-freeportvisit.png', 'tag' => 'Compliance', 'title' => 'Freeport Inspection', 'href' => 'freeport-inspection.html'],
    ['img' => 'img/PJP 18 Juli Apresiasi 6.png', 'tag' => 'Welfare', 'title' => 'Social Security Program', 'href' => 'program-jaminan-sosial.html'],
    ['img' => 'img/fv2.jpg', 'tag' => 'Relations', 'title' => "Freeport's Visit", 'href' => 'blog.html'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Blog | PT. Pengembangan Jaya Papua</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="Latest updates, announcements, and stories from PT. Pengembangan Jaya Papua.">
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

  <header class="page-header">
    <div class="container">
      <div class="breadcrumb"><a href="index.html">Home</a> / <span class="current">Blog</span></div>
      <h1>Blog</h1>
    </div>
  </header>

  <?php if ($posts): ?>
  <section class="section-pad" style="padding-bottom: 0;">
    <div class="container">
      <div class="section-header">
        <span class="eyebrow">Latest</span>
        <h2>Recent Updates</h2>
      </div>
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
    </div>
  </section>
  <?php endif; ?>

  <section class="section-pad">
    <div class="container">
      <div class="section-header">
        <span class="eyebrow">Archive</span>
        <h2>Stories From PJP</h2>
        <p>News, milestones, and moments from our kitchens, camps, and communities across Papua.</p>
      </div>
      <div class="grid grid-3">
        <?php foreach ($archive as $s): ?>
        <div class="media-card">
          <div class="thumb"><img src="<?= h($s['img']) ?>" alt="<?= h($s['title']) ?>" loading="lazy"></div>
          <div class="body">
            <span class="tag"><?= h($s['tag']) ?></span>
            <h3><?= h($s['title']) ?></h3>
            <a href="<?= h($s['href']) ?>" class="card-link">Read Story &rarr;</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

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
