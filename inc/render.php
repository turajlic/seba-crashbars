<?php
declare(strict_types=1);

function section_head(string $kicker, string $title): string {
    return '<header class="sec-head">'
        . '<span class="kicker">' . e($kicker) . '</span>'
        . '<h2 class="sec-title">' . e($title) . '</h2>'
        . '</header>';
}

/*
 * Renderuje kartice radova (bez omotača <div class="grid projects">, poziva se unutar njega).
 * Koristi se i na početnoj (najnoviji radovi) i na radovi.php (svi radovi + filter).
 * $activeBrandSlug: ako je zadat, kartice čija marka (seba_slug) ne odgovara dobijaju
 * atribut hidden već na serveru — radovi.php koristi ovo za ispravan prikaz i bez JS-a
 * pri direktnom otvaranju linka sa ?marka=..., JS posle preuzima filtriranje bez reload-a.
 */
function render_project_cards(array $items, string $activeBrandSlug = ''): void {
    foreach ($items as $it) {
        $imgs = [];
        foreach ((array)($it['images'] ?? []) as $iv) {
            $src = safe_image_src((string)$iv);
            if ($src !== '') $imgs[] = $src;
        }
        if (!$imgs && !empty($it['image'])) { // legacy podatak pre migracije na galeriju
            $legacy = safe_image_src((string)$it['image']);
            if ($legacy !== '') $imgs[] = $legacy;
        }
        $count = count($imgs);
        $name = (string)($it['name'] ?? '');
        $desc = (string)($it['desc'] ?? '');
        $brand = trim((string)($it['brand'] ?? ''));
        $brandSlug = seba_slug($brand);
        $search = mb_strtolower(trim($brand . ' ' . $name . ' ' . $desc));
        $hidden = $activeBrandSlug !== '' && $brandSlug !== $activeBrandSlug;
        ?>
      <article class="proj" data-brand="<?= e($brandSlug) ?>" data-search="<?= e($search) ?>"<?= $hidden ? ' hidden' : '' ?>>
        <?php if ($count): ?>
        <a class="proj-media" href="<?= e($imgs[0]) ?>" data-gallery="<?= e(json_encode($imgs)) ?>" data-gallery-name="<?= e($name) ?>">
          <img src="<?= e($imgs[0]) ?>" alt="<?= e($name) ?>" loading="lazy">
          <?php if ($count > 1): ?><span class="proj-count">1/<?= $count ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <div class="proj-body">
          <?php if ($brand !== ''): ?><span class="proj-brand"><?= e($brand) ?></span><?php endif; ?>
          <h3><?= e($name) ?></h3>
          <p class="proj-desc"><?= e($desc) ?></p>
        </div>
      </article>
        <?php
    }
}

function render_section(array $sec, array $kickers): void {
    $f = $sec['fields'] ?? [];
    $id = e($sec['id']);
    $kick = $kickers[$sec['type']] ?? '';
    switch ($sec['type']) {

        case 'hero': ?>
<section class="hero" id="<?= $id ?>">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="kicker"><?= e($f['kicker'] ?? '') ?></span>
      <h1 class="hero-title"><?= nl2br(e($f['title'] ?? '')) ?></h1>
      <p class="hero-sub"><?= nl2br(e($f['subtitle'] ?? '')) ?></p>
      <div class="hero-actions">
        <a class="btn" href="<?= e($f['cta_link'] ?? '#kontakt') ?>"><?= e($f['cta_text'] ?? 'Kontakt') ?></a>
        <?php if (!empty($f['cta2_text'])): ?>
        <a class="btn-ghost" href="<?= e($f['cta2_link'] ?? '#radovi') ?>"><?= e($f['cta2_text']) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($img = safe_image_src($f['image'] ?? '')): ?>
    <div class="hero-photo"><img src="<?= e($img) ?>" alt="Motociklista sa SEBA crash barom" fetchpriority="high"></div>
    <?php endif; ?>
  </div>
</section>
<?php break;

        case 'intro': ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <span class="kicker"><?= e($f['subtitle'] ?? '') ?></span>
    <h2 class="statement-title"><?= nl2br(e($f['title'] ?? '')) ?></h2>
    <p class="statement-text"><?= nl2br(e($f['text'] ?? '')) ?></p>
  </div>
</section>
<?php break;

        case 'products': ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <?= section_head($kick, $f['title'] ?? 'Proizvodi') ?>
    <div class="grid products">
      <?php foreach (($f['items'] ?? []) as $i => $it): ?>
      <article class="card">
        <span class="card-idx"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <h3><?= e($it['name'] ?? '') ?></h3>
        <p><?= nl2br(e($it['desc'] ?? '')) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php break;

        case 'about': ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <?= section_head($kick, $f['title'] ?? 'Radionica') ?>
    <div class="about-grid">
      <div class="about-text">
        <p class="lead"><?= nl2br(e($f['text'] ?? '')) ?></p>
        <h3 class="sub-h"><?= e($f['tech_title'] ?? '') ?></h3>
        <p><?= nl2br(e($f['tech_text'] ?? '')) ?></p>
        <h3 class="sub-h"><?= e($f['bend_title'] ?? '') ?></h3>
        <p><?= nl2br(e($f['bend_text'] ?? '')) ?></p>
      </div>
      <figure class="about-media">
        <?php if ($img = safe_image_src($f['image'] ?? '')): ?>
        <img src="<?= e($img) ?>" alt="SEBA crash bar — proizvod" loading="lazy">
        <?php endif; ?>
        <figcaption><?= e($f['specs'] ?? '') ?></figcaption>
      </figure>
    </div>
  </div>
</section>
<?php break;

        case 'projects':
            $allItems = $f['items'] ?? [];
            $latest = array_reverse(array_slice($allItems, -6)); // poslednjih 6 dodatih, najnoviji prvi
        ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <?= section_head($kick, $f['title'] ?? 'Radovi') ?>
    <div class="grid projects">
      <?php render_project_cards($latest); ?>
    </div>
    <div class="sec-more">
      <a class="btn-ghost" href="radovi.php">Svi radovi</a>
    </div>
  </div>
</section>
<?php break;

        case 'faq': ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap narrow">
    <?= section_head($kick, $f['title'] ?? 'Pitanja') ?>
    <div class="faq">
      <?php foreach (($f['items'] ?? []) as $it): ?>
      <details class="faq-item">
        <summary><?= e($it['q'] ?? '') ?></summary>
        <p><?= nl2br(e($it['a'] ?? '')) ?></p>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php break;

        case 'testimonials': ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <?= section_head($kick, $f['title'] ?? 'Utisci') ?>
    <div class="grid quotes">
      <?php foreach (($f['items'] ?? []) as $it): ?>
      <blockquote class="quote">
        <p><?= e($it['quote'] ?? '') ?></p>
        <footer><strong><?= e($it['name'] ?? '') ?></strong><?php if (!empty($it['role'])): ?> — <?= e($it['role']) ?><?php endif; ?></footer>
      </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php break;

        case 'social':
            $iglink = trim((string)($f['instagram'] ?? ''));
            $fblink = trim((string)($f['facebook'] ?? '')); ?>
<section class="sec sec-social reveal" id="<?= $id ?>">
  <div class="wrap">
    <div class="social-box">
      <div class="social-copy">
        <?= section_head($kick, $f['title'] ?? 'Zapratite nas') ?>
        <p class="social-text"><?= nl2br(e($f['text'] ?? '')) ?></p>
        <?php if (!empty($f['ig_stat'])): ?><p class="social-stat"><?= e($f['ig_stat']) ?></p><?php endif; ?>
        <div class="social-actions">
          <?php if ($iglink): ?>
          <a class="btn social-ig" href="<?= e($iglink) ?>" rel="noopener" target="_blank">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            Instagram
          </a>
          <?php endif; ?>
          <?php if ($fblink): ?>
          <a class="btn-ghost social-fb" href="<?= e($fblink) ?>" rel="noopener" target="_blank">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M14 9h3l.5-3H14V4.5c0-.9.3-1.5 1.6-1.5H17V.2C16.7.1 15.6 0 14.4 0 11.9 0 10 1.5 10 4.3V6H7v3h3v9h4V9z"/></svg>
            Facebook
          </a>
          <?php endif; ?>
        </div>
        <?php if (!empty($f['ig_handle'])): ?><p class="social-handle"><?= e($f['ig_handle']) ?></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php break;

        case 'contact':
            $set = $GLOBALS['seba_settings'] ?? [];
            $tel = preg_replace('/[^+\d]/', '', $set['phone'] ?? ''); ?>
<section class="sec reveal" id="<?= $id ?>">
  <div class="wrap">
    <?= section_head($kick, $f['title'] ?? 'Kontakt') ?>
    <div class="contact-grid">
      <div>
        <p class="lead"><?= nl2br(e($f['text'] ?? '')) ?></p>
        <ul class="contact-list">
          <li><span class="contact-label">Tel</span><a href="tel:<?= e($tel) ?>"><?= e($set['phone'] ?? '') ?></a></li>
          <li><span class="contact-label">Mail</span><a href="mailto:<?= e($set['email'] ?? '') ?>"><?= e($set['email'] ?? '') ?></a></li>
          <li><span class="contact-label">Adresa</span><span><?= e($set['address'] ?? '') ?></span></li>
          <?php if (!empty($set['facebook'])): ?>
          <li><span class="contact-label">FB</span><a href="<?= e($set['facebook']) ?>" rel="noopener" target="_blank">Facebook stranica</a></li>
          <?php endif; ?>
        </ul>
        <a class="btn" href="tel:<?= e($tel) ?>">Pozovi radionicu</a>
      </div>
      <div class="map-box">
        <iframe title="Mapa — lokacija radionice" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
          src="https://www.google.com/maps?q=<?= rawurlencode($set['address'] ?? 'Beograd') ?>&output=embed"></iframe>
      </div>
    </div>
  </div>
</section>
<?php break;
    }
}
