<?php
declare(strict_types=1);

/*
 * Šema polja po tipu sekcije.
 * Tipovi polja: text, textarea, image, link, items (lista stavki sa pod-poljima).
 * Pod-polje unutar items može biti i 'images' (galerija — niz slika, prva je naslovna).
 * Admin iz ovoga generiše forme; save.php iz ovoga validira šta sme da se upiše.
 */
function seba_schemas(): array {
    return [
        'hero' => [
            'image'    => ['image', 'Glavna slika'],
            'kicker'   => ['text', 'Nadnaslov (mala linija iznad naslova)'],
            'title'    => ['textarea', 'Naslov'],
            'subtitle' => ['textarea', 'Podnaslov'],
            'cta_text' => ['text', 'Tekst dugmeta'],
            'cta_link' => ['link', 'Link dugmeta (npr. #kontakt)'],
            'cta2_text' => ['text', 'Tekst drugog dugmeta (opciono)'],
            'cta2_link' => ['link', 'Link drugog dugmeta'],
        ],
        'intro' => [
            'title'    => ['textarea', 'Naslov'],
            'subtitle' => ['textarea', 'Podnaslov'],
            'text'     => ['textarea', 'Tekst'],
        ],
        'products' => [
            'title' => ['text', 'Naslov sekcije'],
            'items' => ['items', 'Proizvodi', [
                'name' => ['text', 'Naziv'],
                'desc' => ['textarea', 'Opis'],
            ]],
        ],
        'about' => [
            'title'      => ['text', 'Naslov'],
            'text'       => ['textarea', 'Tekst o radionici'],
            'image'      => ['image', 'Slika radionice'],
            'tech_title' => ['text', 'Naslov — tehnologija'],
            'tech_text'  => ['textarea', 'Tekst — tehnologija'],
            'bend_title' => ['text', 'Naslov — savijanje'],
            'bend_text'  => ['textarea', 'Tekst — savijanje'],
            'specs'      => ['text', 'Specifikacije cevi (linija sa Ø merama)'],
        ],
        'projects' => [
            'title' => ['text', 'Naslov sekcije'],
            'items' => ['items', 'Projekti', [
                'name'   => ['text', 'Model motora'],
                'brand'  => ['text', 'Marka motora (npr. Honda, BMW)'],
                'desc'   => ['text', 'Šta je rađeno'],
                'images' => ['images', 'Fotografije'],
            ]],
        ],
        'faq' => [
            'title' => ['text', 'Naslov sekcije'],
            'items' => ['items', 'Pitanja', [
                'q' => ['text', 'Pitanje'],
                'a' => ['textarea', 'Odgovor'],
            ]],
        ],
        'testimonials' => [
            'title' => ['text', 'Naslov sekcije'],
            'items' => ['items', 'Utisci', [
                'quote' => ['textarea', 'Citat'],
                'name'  => ['text', 'Ime'],
                'role'  => ['text', 'Firma / uloga (opciono)'],
            ]],
        ],
        'social' => [
            'title'    => ['text', 'Naslov sekcije'],
            'text'     => ['textarea', 'Tekst (poziv da zaprate)'],
            'instagram'=> ['link', 'Instagram link'],
            'facebook' => ['link', 'Facebook link'],
            'ig_handle'=> ['text', 'Instagram korisničko ime (npr. @crashbarsseba)'],
            'ig_stat'  => ['text', 'Broj pratilaca / objava (opciono, npr. 1.700+ pratilaca)'],
        ],
        'contact' => [
            'title' => ['text', 'Naslov sekcije'],
            'text'  => ['textarea', 'Uvodni tekst'],
        ],
    ];
}

/* Normalizacija marke motora: trim + Veliko Slovo Svake Reči (i dela posle crte, npr. Can-Am),
   sem poznatih skraćenica koje ostaju velikim slovima (BMW, KTM). */
function normalize_brand(string $brand): string {
    $brand = trim((string)preg_replace('/\s+/', ' ', $brand));
    if ($brand === '') return '';
    $upper = ['BMW', 'KTM'];
    $words = explode(' ', $brand);
    foreach ($words as &$w) {
        $known = null;
        foreach ($upper as $u) {
            if (mb_strtolower($w) === mb_strtolower($u)) { $known = $u; break; }
        }
        if ($known !== null) {
            $w = $known;
            continue;
        }
        $parts = explode('-', $w);
        foreach ($parts as &$p) {
            if ($p === '') continue;
            $p = mb_strtoupper(mb_substr($p, 0, 1)) . mb_strtolower(mb_substr($p, 1));
        }
        unset($p);
        $w = implode('-', $parts);
    }
    unset($w);
    return implode(' ', $words);
}

/* Validacija podataka sekcije prema šemi: propušta samo poznata polja, sve svodi na stringove. */
function validate_section_fields(string $type, array $input): ?array {
    $schemas = seba_schemas();
    if (!isset($schemas[$type])) return null;
    $out = [];
    foreach ($schemas[$type] as $key => $def) {
        [$ftype] = $def;
        if ($ftype === 'items') {
            $sub = $def[2];
            $items = [];
            foreach ((array)($input[$key] ?? []) as $item) {
                if (!is_array($item)) continue;
                $clean = [];
                foreach ($sub as $sk => $sdef) {
                    if ($sdef[0] === 'images') {
                        $imgs = [];
                        foreach ((array)($item[$sk] ?? []) as $imgVal) {
                            $src = safe_image_src(trim((string)$imgVal));
                            if ($src !== '') $imgs[] = $src;
                        }
                        $clean[$sk] = array_slice($imgs, 0, 20);
                    } else {
                        $val = trim((string)($item[$sk] ?? ''));
                        if ($sdef[0] === 'image') {
                            $val = safe_image_src($val);
                        } elseif ($sk === 'brand') {
                            $val = normalize_brand($val);
                        }
                        $clean[$sk] = $val;
                    }
                }
                $hasContent = false;
                foreach ($clean as $v) {
                    if (is_array($v) ? !empty($v) : $v !== '') { $hasContent = true; break; }
                }
                if ($hasContent) $items[] = $clean;
            }
            $out[$key] = array_slice($items, 0, 60);
        } else {
            $val = trim((string)($input[$key] ?? ''));
            if ($ftype === 'image') $val = safe_image_src($val);
            if ($ftype === 'link' && $val !== '' && !preg_match('#^(https?://|\#|mailto:|tel:|/)#i', $val)) $val = '#';
            $out[$key] = mb_substr($val, 0, 5000);
        }
    }
    return $out;
}
