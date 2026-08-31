/* ===========================================================
   PT. Pengembangan Jaya Papua — Site Scripts (vanilla JS)
   =========================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Sticky nav shadow on scroll ---------- */
  var nav = document.querySelector('nav.main-nav');
  if (nav) {
    var onScroll = function () {
      if (window.scrollY > 20) nav.classList.add('scrolled');
      else nav.classList.remove('scrolled');
    };
    window.addEventListener('scroll', onScroll);
    onScroll();
  }

  /* ---------- Mobile nav toggle ---------- */
  var toggle = document.querySelector('.nav-toggle');
  var links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
  }

  /* ---------- Language dropdown (mobile tap support) ---------- */
  var langDropdown = document.querySelector('.lang-dropdown');
  if (langDropdown) {
    var langBtn = langDropdown.querySelector('.lang-btn');
    if (langBtn) {
      langBtn.addEventListener('click', function (e) {
        if (window.innerWidth <= 980) {
          e.preventDefault();
          langDropdown.classList.toggle('open');
        }
      });
    }
  }

  /* ---------- Hero carousel ---------- */
  var heroSlides = document.querySelectorAll('.hero-slide');
  var heroDots = document.querySelectorAll('.hero-dots button');
  if (heroSlides.length > 1) {
    var current = 0;
    var showSlide = function (i) {
      heroSlides.forEach(function (s, idx) { s.classList.toggle('active', idx === i); });
      heroDots.forEach(function (d, idx) { d.classList.toggle('active', idx === i); });
      current = i;
    };
    heroDots.forEach(function (dot, idx) {
      dot.addEventListener('click', function () { showSlide(idx); resetAutoplay(); });
    });
    var autoplay;
    var resetAutoplay = function () {
      clearInterval(autoplay);
      autoplay = setInterval(function () { showSlide((current + 1) % heroSlides.length); }, 6000);
    };
    resetAutoplay();
  }

  /* ---------- Animated stat counters ---------- */
  var stats = document.querySelectorAll('.stat-num[data-count]');
  if (stats.length) {
    var animateCount = function (el) {
      var target = parseFloat(el.getAttribute('data-count'));
      var suffix = el.getAttribute('data-suffix') || '';
      var duration = 1400;
      var start = null;
      var step = function (ts) {
        if (!start) start = ts;
        var progress = Math.min((ts - start) / duration, 1);
        var value = Math.floor(progress * target);
        el.textContent = value.toLocaleString('en-US') + suffix;
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = target.toLocaleString('en-US') + suffix;
      };
      requestAnimationFrame(step);
    };
    var statObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          statObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    stats.forEach(function (el) { statObserver.observe(el); });
  }

  /* ---------- Horizontal news/blog scroller controls ---------- */
  document.querySelectorAll('.news-scroll').forEach(function (scroller) {
    var wrap = scroller.closest('.news-block') || scroller.parentElement;
    var prev = wrap.querySelector('.news-prev');
    var next = wrap.querySelector('.news-next');
    var scrollAmount = 300;
    if (prev) prev.addEventListener('click', function () { scroller.scrollBy({ left: -scrollAmount, behavior: 'smooth' }); });
    if (next) next.addEventListener('click', function () { scroller.scrollBy({ left: scrollAmount, behavior: 'smooth' }); });
  });

  /* ---------- Gallery filter tabs ---------- */
  var tabs = document.querySelectorAll('.filter-tabs button');
  var photos = document.querySelectorAll('.photo-grid .photo');
  if (tabs.length && photos.length) {
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var filter = tab.getAttribute('data-filter');
        photos.forEach(function (p) {
          var show = filter === 'all' || p.getAttribute('data-category') === filter;
          p.style.display = show ? '' : 'none';
        });
      });
    });
  }

  /* ---------- Lightbox for gallery photos ---------- */
  var lightbox = document.getElementById('lightbox');
  if (lightbox) {
    var lightboxImg = lightbox.querySelector('img');
    var visiblePhotos = function () {
      return Array.prototype.filter.call(photos, function (p) { return p.style.display !== 'none'; });
    };
    var lbIndex = 0;
    var openLightbox = function (index) {
      var list = visiblePhotos();
      lbIndex = index;
      var img = list[lbIndex].querySelector('img');
      lightboxImg.src = img.src;
      lightboxImg.alt = img.alt;
      lightbox.classList.add('open');
    };
    photos.forEach(function (photo, idx) {
      photo.addEventListener('click', function () { openLightbox(Array.prototype.indexOf.call(visiblePhotos(), photo)); });
    });
    var closeBtn = lightbox.querySelector('.lightbox-close');
    if (closeBtn) closeBtn.addEventListener('click', function () { lightbox.classList.remove('open'); });
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lightbox.classList.remove('open'); });
    var prevBtn = lightbox.querySelector('.lightbox-prev');
    var nextBtn = lightbox.querySelector('.lightbox-next');
    if (prevBtn) prevBtn.addEventListener('click', function () {
      var list = visiblePhotos();
      openLightbox((lbIndex - 1 + list.length) % list.length);
    });
    if (nextBtn) nextBtn.addEventListener('click', function () {
      var list = visiblePhotos();
      openLightbox((lbIndex + 1) % list.length);
    });
    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') lightbox.classList.remove('open');
      if (e.key === 'ArrowRight' && nextBtn) nextBtn.click();
      if (e.key === 'ArrowLeft' && prevBtn) prevBtn.click();
    });
  }

  /* ---------- Back to top ---------- */
  var backToTop = document.querySelector('.back-to-top');
  if (backToTop) {
    window.addEventListener('scroll', function () {
      backToTop.classList.toggle('show', window.scrollY > 500);
    });
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- Contact popup (tap-to-call) ---------- */
  var contactPopup = document.getElementById('contact-popup');
  if (contactPopup) {
    var link = contactPopup.querySelector('a');
    contactPopup.addEventListener('click', function () {
      window.location.href = link.getAttribute('href');
    });
  }

  /* ---------- Quote form & contact form -> real submission to contact.php ----------
     Progressive enhancement: the <form> already has action="contact.php"
     method="POST" as a plain HTML fallback (works even with JS off). When
     JS is available, we intercept submit, send it via fetch so the visitor
     gets an inline confirmation without leaving the page, and fall back to
     a normal form submission if the request fails for any reason. */
  function wireAjaxForm(form) {
    form.addEventListener('submit', function (e) {
      var action = form.getAttribute('action');
      if (!action) return; // no action set — let it no-op as before, nothing to do

      e.preventDefault();
      var submitBtn = form.querySelector('button[type="submit"]');
      var originalLabel = submitBtn ? submitBtn.textContent : '';
      if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Sending…'; }

      var data = new FormData(form);
      data.append('source_page', window.location.pathname);

      fetch(action, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
        .then(function (result) {
          if (result.ok && result.json && result.json.ok) {
            showFormNotice(form, 'success', "Thanks — your message has been sent. We'll be in touch soon.");
            form.reset();
          } else {
            showFormNotice(form, 'error', (result.json && result.json.error) || 'Something went wrong — please try again.');
          }
        })
        .catch(function () {
          // Network/JS failure: fall back to a real, non-AJAX submission.
          form.submit();
        })
        .finally(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalLabel; }
        });
    });
  }

  function showFormNotice(form, type, text) {
    var notice = form.querySelector('.form-notice');
    if (!notice) {
      notice = document.createElement('div');
      notice.className = 'form-notice';
      notice.style.marginTop = '1rem';
      notice.style.padding = '0.85rem 1.1rem';
      notice.style.borderRadius = '12px';
      notice.style.fontSize = '0.9rem';
      notice.style.fontWeight = '600';
      form.appendChild(notice);
    }
    notice.style.background = type === 'success' ? '#DFF5E1' : '#FCE4E4';
    notice.style.color = type === 'success' ? '#1E7A34' : '#A32626';
    notice.textContent = text;
  }

  document.querySelectorAll('[data-quote-form], [data-contact-form]').forEach(wireAjaxForm);

  /* ---------- Site notification banner (fetched from api/notifications.php) ----------
     Injected at the very top of <body> — no per-page HTML changes needed.
     Dismissing it hides that specific notification (by id) for this visitor
     until it changes, remembered in localStorage. */
  (function loadSiteNotification() {
    fetch('/api/notifications.php', { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (notif) {
        if (!notif || !notif.id) return;
        var dismissedId = null;
        try { dismissedId = localStorage.getItem('pjp_dismissed_notification'); } catch (e) {}
        if (dismissedId && String(dismissedId) === String(notif.id)) return;

        var bar = document.createElement('div');
        bar.className = 'site-notification ' + (notif.style || 'info');
        var text = document.createElement('span');
        text.textContent = notif.message;
        bar.appendChild(text);
        if (notif.link_url) {
          var link = document.createElement('a');
          link.href = notif.link_url;
          link.textContent = notif.link_text || 'Learn more';
          bar.appendChild(link);
        }
        var close = document.createElement('button');
        close.className = 'site-notification-close';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () {
          bar.remove();
          try { localStorage.setItem('pjp_dismissed_notification', String(notif.id)); } catch (e) {}
        });
        bar.appendChild(close);
        document.body.insertBefore(bar, document.body.firstChild);
      })
      .catch(function () { /* silently ignore — banner is a non-essential enhancement */ });
  })();

});
