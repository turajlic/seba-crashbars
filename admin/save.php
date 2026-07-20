<?php
declare(strict_types=1);
/* ovaj endpoint uvek vraća JSON — greške se i dalje beleže (log_errors), samo se ne ispisuju u telo odgovora */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();
require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/schemas.php';
seba_session_start();

if (!is_logged_in()) json_out(['ok' => false, 'error' => 'Niste prijavljeni.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Pogrešan metod.'], 405);

$action = (string)($_POST['action'] ?? '');
$csrf = $_POST['csrf'] ?? null;
if (!csrf_check($csrf)) json_out(['ok' => false, 'error' => 'Sesija je istekla. Osvežite stranicu.'], 403);

$content = load_content();

switch ($action) {

    /* redosled + vidljivost sekcija */
    case 'reorder': {
        $order = json_decode((string)($_POST['order'] ?? '[]'), true);
        if (!is_array($order)) json_out(['ok' => false, 'error' => 'Neispravan zahtev.'], 400);
        $byId = [];
        foreach ($content['sections'] as $s) $byId[$s['id']] = $s;
        $new = [];
        foreach ($order as $row) {
            $id = (string)($row['id'] ?? '');
            if (!isset($byId[$id])) continue;
            $sec = $byId[$id];
            $sec['visible'] = !empty($row['visible']);
            $new[] = $sec;
            unset($byId[$id]);
        }
        foreach ($byId as $left) $new[] = $left; // ništa se ne gubi
        $content['sections'] = $new;
        if (!save_content($content)) json_out(['ok' => false, 'error' => 'Upis nije uspeo.'], 500);
        json_out(['ok' => true]);
    }

    /* sadržaj jedne sekcije */
    case 'save_section': {
        $id = (string)($_POST['section_id'] ?? '');
        $data = json_decode((string)($_POST['data'] ?? ''), true);
        if (!is_array($data)) json_out(['ok' => false, 'error' => 'Neispravni podaci.'], 400);
        foreach ($content['sections'] as &$sec) {
            if ($sec['id'] === $id) {
                $clean = validate_section_fields($sec['type'], $data);
                if ($clean === null) json_out(['ok' => false, 'error' => 'Nepoznat tip sekcije.'], 400);
                $sec['fields'] = $clean;
                if (!save_content($content)) json_out(['ok' => false, 'error' => 'Upis nije uspeo.'], 500);
                json_out(['ok' => true]);
            }
        }
        json_out(['ok' => false, 'error' => 'Sekcija nije pronađena.'], 404);
    }

    /* globalna podešavanja */
    case 'save_settings': {
        $allowed = ['site_title', 'meta_description', 'ga_id', 'phone', 'email', 'address', 'facebook', 'instagram', 'footer_note'];
        foreach ($allowed as $k) {
            if (isset($_POST[$k])) $content['settings'][$k] = mb_substr(trim((string)$_POST[$k]), 0, 1000);
        }
        $ga = $content['settings']['ga_id'];
        if ($ga !== '' && !preg_match('/^(G|UA|AW|GT)-[A-Z0-9\-]{4,20}$/i', $ga)) {
            json_out(['ok' => false, 'error' => 'GA ID nije u ispravnom formatu (očekivano npr. G-XXXXXXXXXX).'], 400);
        }
        if (!save_content($content)) json_out(['ok' => false, 'error' => 'Upis nije uspeo.'], 500);
        json_out(['ok' => true]);
    }

    /* promena lozinke */
    case 'change_password': {
        $users = load_users();
        $u = (string)$_SESSION['seba_user'];
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        if (!isset($users[$u]) || !password_verify($cur, $users[$u]['hash'])) {
            json_out(['ok' => false, 'error' => 'Trenutna lozinka nije tačna.'], 400);
        }
        if (mb_strlen($new) < 10) json_out(['ok' => false, 'error' => 'Nova lozinka mora imati najmanje 10 znakova.'], 400);
        $users[$u]['hash'] = password_hash($new, PASSWORD_DEFAULT);
        if (!save_users($users)) json_out(['ok' => false, 'error' => 'Upis nije uspeo.'], 500);
        json_out(['ok' => true]);
    }

    /* upload slike: whitelist + provera sadržaja + resize */
    case 'upload': {
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            json_out(['ok' => false, 'error' => 'Slika nije primljena.'], 400);
        }
        $f = $_FILES['image'];
        if ($f['size'] > 10 * 1024 * 1024) json_out(['ok' => false, 'error' => 'Slika je veća od 10 MB.'], 400);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) json_out(['ok' => false, 'error' => 'Dozvoljeni formati: JPG, PNG, WEBP.'], 400);

        $info = @getimagesize($f['tmp_name']);
        if ($info === false) json_out(['ok' => false, 'error' => 'Datoteka nije ispravna slika.'], 400);

        /* re-enkodiranje kroz GD: uklanja metapodatke i eventualni ugrađen sadržaj */
        /* switch umesto match() — match je PHP 8.0+ sintaksa, ne bi se ni parsirala na 7.4 */
        switch ($mime) {
            case 'image/jpeg': $src = @imagecreatefromjpeg($f['tmp_name']); break;
            case 'image/png':  $src = @imagecreatefrompng($f['tmp_name']); break;
            default:           $src = @imagecreatefromwebp($f['tmp_name']); break;
        }
        if (!$src) json_out(['ok' => false, 'error' => 'Slika ne može da se obradi.'], 400);

        $w = imagesx($src); $h = imagesy($src);
        $max = 1920;
        if ($w > $max || $h > $max) {
            $ratio = min($max / $w, $max / $h);
            $nw = (int)round($w * $ratio); $nh = (int)round($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $src = $dst;
        }

        $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
        $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = SEBA_UPLOADS . '/' . $name;
        switch ($ext) {
            case 'jpg':  $ok = imagejpeg($src, $dest, 84); break;
            case 'png':  $ok = imagepng($src, $dest, 7); break;
            default:     $ok = imagewebp($src, $dest, 84); break;
        }
        if (!$ok) json_out(['ok' => false, 'error' => 'Čuvanje slike nije uspelo.'], 500);
        @chmod($dest, 0644);
        json_out(['ok' => true, 'path' => 'uploads/' . $name]);
    }

    default:
        json_out(['ok' => false, 'error' => 'Nepoznata akcija.'], 400);
}
