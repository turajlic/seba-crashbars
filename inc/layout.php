<?php
declare(strict_types=1);

/*
 * Zajednički <head>, header/nav i footer za sve stranice sajta.
 * Jedan izvor istine za layout — index.php i radovi.php ga oba pozivaju
 * da se markup ne duplira.
 */

function render_page_head(array $set, string $title, string $desc): void {
    ?><!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="icon" type="image/png" href="uploads/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<?php if (!empty($set['ga_id'])): $ga = e($set['ga_id']); ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $ga ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= $ga ?>', { anonymize_ip: true });
</script>
<?php endif; ?>
</head>
<body>
<?php
}

/*
 * $visible  — vidljive sekcije početne (za stavke menija).
 * $navNames — mapa tip sekcije -> naziv u meniju.
 * $onRadovi — true kad poziva radovi.php: linkovi ka sekcijama početne postaju
 *             apsolutni (index.php#sekcija), a "Radovi" ostaje na radovi.php
 *             i markira se kao aktivna stavka menija.
 */
function render_page_header(array $set, array $visible, array $navNames, bool $onRadovi = false): void {
    $tel = preg_replace('/[^+\d]/', '', $set['phone'] ?? '');
    $homeHref = $onRadovi ? 'index.php' : '#top';
    ?>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="<?= e($homeHref) ?>" aria-label="SEBA Crash Bars — početak">
      <img src="uploads/seba-logo_2x.png" alt="SEBA Crash Bars">
    </a>
    <div class="topbar-right">
      <a class="nav-tel" href="tel:<?= e($tel) ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .7-.2 1l-2.3 2.2z"/></svg>
        <span><?= e($set['phone'] ?? '') ?></span>
      </a>
      <button class="nav-toggle" aria-expanded="false" aria-controls="mainnav" aria-label="Otvori meni">
        <span class="nav-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
    </div>
    <div class="nav-backdrop" hidden></div>
    <nav id="mainnav" class="nav" aria-label="Glavni meni" hidden>
      <button class="nav-close" aria-label="Zatvori meni">×</button>
      <?php foreach ($visible as $s): if (!isset($navNames[$s['type']])) continue;
          $isProjects = $s['type'] === 'projects';
          $href = $isProjects ? 'radovi.php' : ($onRadovi ? ('index.php#' . $s['id']) : ('#' . $s['id']));
          $active = $onRadovi && $isProjects;
      ?>
      <a href="<?= e($href) ?>"<?= $active ? ' class="is-active"' : '' ?>><?= e($navNames[$s['type']]) ?></a>
      <?php endforeach; ?>
      <a class="nav-tel-inline" href="tel:<?= e($tel) ?>"><?= e($set['phone'] ?? '') ?></a>
    </nav>
  </div>
</header>
<?php
}

function render_page_footer(array $set): void {
    $tel = preg_replace('/[^+\d]/', '', $set['phone'] ?? '');
    ?>
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <img class="footer-logo" src="uploads/favicon.png" alt="SEBA Crash Bars logo">
      <p class="footer-note"><?= e($set['footer_note'] ?? '') ?></p>
    </div>
    <div class="footer-meta">
      <p><?= e($set['address'] ?? '') ?></p>
      <p><a href="tel:<?= e($tel) ?>"><?= e($set['phone'] ?? '') ?></a></p>
      <p><a href="mailto:<?= e($set['email'] ?? '') ?>"><?= e($set['email'] ?? '') ?></a></p>
      <?php if (!empty($set['facebook']) || !empty($set['instagram'])): ?>
      <div class="footer-social">
        <?php if (!empty($set['facebook'])): ?>
        <a href="<?= e($set['facebook']) ?>" class="footer-social-link" rel="noopener" target="_blank" aria-label="Facebook">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.5 9.9v-7H7.9V12h2.6V9.8c0-2.6 1.5-4 3.9-4 1.1 0 2.3.2 2.3.2v2.5h-1.3c-1.3 0-1.7.8-1.7 1.6V12h2.9l-.5 2.9h-2.4v7A10 10 0 0 0 22 12Z"/></svg>
        </a>
        <?php endif; ?>
        <?php if (!empty($set['instagram'])): ?>
        <a href="<?= e($set['instagram']) ?>" class="footer-social-link" rel="noopener" target="_blank" aria-label="Instagram">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.2"></circle><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"></circle></svg>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="wrap footer-legal">
    <span>© 2010–<?= date('Y') ?> SEBA Crash Bars, Beograd</span>
    <span>TIG zavarivanje · hladno savijanje cevi · ručna izrada</span>
  </div>
</footer>

<div class="lightbox" hidden>
  <button class="lb-close" aria-label="Zatvori">×</button>
  <button class="lb-prev" aria-label="Prethodna slika" hidden>‹</button>
  <img alt="">
  <button class="lb-next" aria-label="Sledeća slika" hidden>›</button>
  <span class="lb-count" hidden></span>
</div>
<?php
}
