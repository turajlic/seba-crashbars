<?php
declare(strict_types=1);
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/render.php';
require __DIR__ . '/inc/layout.php';

$content = load_content();
$set = $content['settings'] ?? [];
$GLOBALS['seba_settings'] = $set;

$visible = array_values(array_filter($content['sections'] ?? [], fn($s) => !empty($s['visible'])));

/* kratki nazivi za navigaciju i nadnaslove sekcija */
$navNames = [
    'products' => 'Proizvodi', 'about' => 'Radionica', 'projects' => 'Radovi',
    'faq' => 'Pitanja', 'testimonials' => 'Utisci', 'social' => 'Mreže', 'contact' => 'Kontakt',
];

render_page_head(
    $set,
    ($set['site_title'] ?? 'SEBA Crash Bars') . ' — zaštitna oprema za motocikle, Beograd',
    $set['meta_description'] ?? ''
);
render_page_header($set, $visible, $navNames, false);
?>

<main id="top">
<?php foreach ($visible as $sec) render_section($sec, $navNames); ?>
</main>

<?php render_page_footer($set); ?>
<script src="assets/js/main.js" defer></script>
</body>
</html>
