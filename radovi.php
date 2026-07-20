<?php
declare(strict_types=1);
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/render.php';
require __DIR__ . '/inc/layout.php';

$content = load_content();
$set = $content['settings'] ?? [];
$GLOBALS['seba_settings'] = $set;

$visible = array_values(array_filter($content['sections'] ?? [], fn($s) => !empty($s['visible'])));
$navNames = [
    'products' => 'Proizvodi', 'about' => 'Radionica', 'projects' => 'Radovi',
    'faq' => 'Pitanja', 'testimonials' => 'Utisci', 'social' => 'Mreže', 'contact' => 'Kontakt',
];

/* radovi se čitaju iz iste 'projects' sekcije kao i početna — jedan izvor istine,
   nezavisno od toga da li je ta sekcija trenutno vidljiva na početnoj */
$projectsSection = null;
foreach ($content['sections'] ?? [] as $s) {
    if ($s['type'] === 'projects') { $projectsSection = $s; break; }
}
$items = $projectsSection['fields']['items'] ?? [];

/* jedinstvene marke (abecedno) za dugmad filtera */
$brands = [];
foreach ($items as $it) {
    $b = trim((string)($it['brand'] ?? ''));
    if ($b !== '' && !in_array($b, $brands, true)) $brands[] = $b;
}
sort($brands, SORT_FLAG_CASE | SORT_STRING);
$brandSlugs = array_map('seba_slug', $brands);

/* ?marka=... iz URL-a — validiraj protiv poznatih marki, inače tretiraj kao "Sve" */
$requestedMarka = seba_slug((string)($_GET['marka'] ?? ''));
$activeMarka = in_array($requestedMarka, $brandSlugs, true) ? $requestedMarka : '';

$pageTitle = 'Svi radovi — ' . ($set['site_title'] ?? 'SEBA Crash Bars');
$pageDesc = 'Pregled svih projekata radionice SEBA Crash Bars — filtriraj po marki motora ili pretraži po modelu.';

render_page_head($set, $pageTitle, $pageDesc);
render_page_header($set, $visible, $navNames, true);
?>

<main id="top">
<section class="sec reveal">
  <div class="wrap">
    <header class="sec-head">
      <span class="kicker">Portfolio radionice</span>
      <h1 class="sec-title">Svi radovi</h1>
    </header>

    <?php if ($items): ?>
    <div class="radovi-toolbar">
      <div class="radovi-filters" id="radoviFilters">
        <a href="radovi.php" class="filter-btn<?= $activeMarka === '' ? ' is-active' : '' ?>" data-brand="">Sve</a>
        <?php foreach ($brands as $b): $slug = seba_slug($b); ?>
        <a href="radovi.php?marka=<?= e($slug) ?>" class="filter-btn<?= $slug === $activeMarka ? ' is-active' : '' ?>" data-brand="<?= e($slug) ?>"><?= e($b) ?></a>
        <?php endforeach; ?>
      </div>
      <label class="radovi-search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" id="radoviSearch" placeholder="Pretraži po modelu (npr. GS, Africa…)" autocomplete="off">
      </label>
    </div>

    <div class="grid projects" id="radoviGrid">
      <?php render_project_cards($items, $activeMarka); ?>
    </div>
    <p class="radovi-empty" id="radoviEmpty" hidden>Nema radova za izabrani filter.</p>
    <?php else: ?>
    <p class="radovi-empty">Trenutno nema dodatih radova.</p>
    <?php endif; ?>
  </div>
</section>
</main>

<?php render_page_footer($set); ?>
<script src="assets/js/main.js" defer></script>
<script src="assets/js/radovi.js" defer></script>
</body>
</html>
