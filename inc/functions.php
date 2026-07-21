<?php
declare(strict_types=1);

define('SEBA_ROOT', dirname(__DIR__));
define('SEBA_CONTENT', SEBA_ROOT . '/content.json');
define('SEBA_DATA', SEBA_ROOT . '/data');
define('SEBA_UPLOADS', SEBA_ROOT . '/uploads');
define('SEBA_BACKUPS', SEBA_DATA . '/backups');
define('SEBA_BACKUP_KEEP', 15);
/* Kanonski domen sajta — koristi se za apsolutne URL-ove u meta tagovima
   (Open Graph, canonical) i JSON-LD schema, koje moraju biti apsolutne
   bez obzira odakle se stranica lokalno testira. */
define('SEBA_SITE_URL', 'https://crashbars.rs');

/* ---------- osnovno ---------- */

/* fallback ako hosting nema mbstring ekstenziju */
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $len = null): string {
        return $len === null ? substr($s, $start) : substr($s, $start, $len);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return strlen($s); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $s): string {
        return strtr(strtolower($s), ['Č' => 'č', 'Ć' => 'ć', 'Ž' => 'ž', 'Š' => 'š', 'Đ' => 'đ']);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $s): string {
        return strtr(strtoupper($s), ['č' => 'Č', 'ć' => 'Ć', 'ž' => 'Ž', 'š' => 'Š', 'đ' => 'Đ']);
    }
}

/* fallback za PHP < 8.0 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function load_content(): array {
    $raw = @file_get_contents(SEBA_CONTENT);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        http_response_code(500);
        exit('Greška: content.json nije čitljiv.');
    }
    return $data;
}

function save_content(array $data): bool {
    seba_backup_content();
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $tmp = SEBA_CONTENT . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, SEBA_CONTENT);
}

/* ---------- sigurnosne kopije sadržaja ----------
 * content.json je namerno van gita (živi sadržaj, deploy ga ne dira), pa nema
 * git istoriju kao zaštitu od greške. Zato pravimo sopstvenu: pre SVAKE izmene
 * (reorder, čuvanje sekcije, podešavanja) sačuva se vremenski obeležena kopija
 * TRENUTNOG stanja u data/backups/ — van gita, van weba (data/.htaccess već
 * blokira ceo folder), automatski, bez ikakve akcije admina. */

/* Kopira trenutni content.json u data/backups/ pre nego što se prepiše, i
   briše najstarije kopije preko SEBA_BACKUP_KEEP. */
function seba_backup_content(): void {
    if (!is_file(SEBA_CONTENT)) return;
    if (!is_dir(SEBA_BACKUPS)) @mkdir(SEBA_BACKUPS, 0755, true);
    if (!is_dir(SEBA_BACKUPS)) return;

    @copy(SEBA_CONTENT, SEBA_BACKUPS . '/content-' . date('Ymd-His') . '.json');

    $files = glob(SEBA_BACKUPS . '/content-*.json') ?: [];
    sort($files); // imena su "content-Ymd-His.json" — abecedno sortiranje = hronoloski
    $excess = count($files) - SEBA_BACKUP_KEEP;
    for ($i = 0; $i < $excess; $i++) {
        @unlink($files[$i]);
    }
}

/* Lista sačuvanih kopija, najnovija prva. */
function seba_list_backups(): array {
    $files = glob(SEBA_BACKUPS . '/content-*.json') ?: [];
    sort($files);
    $files = array_reverse($files);
    $out = [];
    foreach ($files as $f) {
        $out[] = ['file' => basename($f), 'size' => (int)filesize($f), 'mtime' => (int)filemtime($f)];
    }
    return $out;
}

/* Validira ime backup fajla (sprečava path traversal — dozvoljen samo tačan
   obrazac imena koji sami generišemo) i vraća punu putanju, ili null. */
function seba_backup_path(string $filename): ?string {
    if (!preg_match('/^content-\d{8}-\d{6}\.json$/', $filename)) return null;
    $path = SEBA_BACKUPS . '/' . $filename;
    return is_file($path) ? $path : null;
}

/* ---------- upravljanje slikama u uploads/ ----------
 * uploads/ samo raste — svaka otpremljena slika ostaje zauvek, čak i posle
 * zamene u CMS-u. Ovo daje pregled šta se stvarno koristi na sajtu (skeniranjem
 * content.json) da bi se neiskorišćene slike mogle bezbedno obrisati. */

/* Brend slike koje su deo git repoa i/ili se koriste van content.json
   (favicon/logo su hardkodovani u inc/layout.php) — nikad ih ne nudimo za
   brisanje kroz ovaj alat, bez obzira na rezultat skeniranja upotrebe. */
function seba_protected_uploads(): array {
    return [
        'favicon.png', 'seba-logo_2x.png', 'seba-crashbar.png',
        'Seba-Crashbar-home-slider2.jpg', 'index.html',
    ];
}

/* Rekurzivno prikuplja sve lokalne (uploads/...) putanje slika iz proizvoljne
   strukture polja sekcije — radi i za pojedinačno 'image' polje i za 'items'/
   'images' nizove, bez da zna imena konkretnih polja. */
function seba_collect_image_paths($value, array &$used): void {
    if (is_string($value)) {
        $src = safe_image_src($value);
        if ($src !== '' && str_starts_with($src, 'uploads/')) $used[$src] = true;
        return;
    }
    if (is_array($value)) {
        foreach ($value as $v) seba_collect_image_paths($v, $used);
    }
}

/* Skenira ceo content.json i vraća set (uploads/ime.jpg => true) svih slika
   koje se trenutno stvarno koriste na sajtu. */
function seba_used_uploads(array $content): array {
    $used = [];
    foreach (seba_protected_uploads() as $p) $used['uploads/' . $p] = true;
    foreach ($content['sections'] ?? [] as $sec) {
        seba_collect_image_paths($sec['fields'] ?? [], $used);
    }
    return $used;
}

/* Lista svih slika u uploads/ sa statusom (koristi se / nije u upotrebi / zaštićena). */
function seba_list_uploads(array $content): array {
    $used = seba_used_uploads($content);
    $protected = array_flip(seba_protected_uploads());
    /* obican glob('*') + rucni filter po ekstenziji, umesto GLOB_BRACE —
       ta zastavica nije garantovano dostupna na svim hostinzima */
    $allExt = ['jpg', 'jpeg', 'png', 'webp'];
    $files = glob(SEBA_UPLOADS . '/*') ?: [];
    $out = [];
    foreach ($files as $f) {
        if (!is_file($f)) continue;
        $ext = mb_strtolower((string)pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allExt, true)) continue;
        $name = basename($f);
        $rel = 'uploads/' . $name;
        $out[] = [
            'file' => $name,
            'size' => (int)filesize($f),
            'mtime' => (int)filemtime($f),
            'used' => isset($used[$rel]),
            'protected' => isset($protected[$name]),
        ];
    }
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $out;
}

/* Validira ime fajla za brisanje (samo goli naziv fajla, bez putanje) i
   potvrđuje da fajl stvarno postoji u uploads/, vraća punu putanju ili null. */
function seba_upload_path(string $filename): ?string {
    if ($filename === '' || $filename !== basename($filename)) return null;
    if (!preg_match('/^[A-Za-z0-9_.-]+\.(jpg|jpeg|png|webp)$/i', $filename)) return null;
    $path = SEBA_UPLOADS . '/' . $filename;
    return is_file($path) ? $path : null;
}

/* URL-bezbedan slug (npr. za filter po marki: "Can-Am" -> "can-am"). */
function seba_slug(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = (string)preg_replace('/[^a-z0-9]+/u', '-', $s);
    return trim($s, '-');
}

/* Dozvoljene putanje slika: lokalni upload ili https URL. */
function safe_image_src(?string $src): string {
    $src = trim((string)$src);
    if ($src === '') return '';
    if (preg_match('#^https?://#i', $src)) return $src;
    $src = ltrim(str_replace(['..', '\\'], '', $src), '/');
    if (str_starts_with($src, 'uploads/') || str_starts_with($src, 'assets/')) return $src;
    return '';
}

/* Pretvara lokalnu putanju (npr. "uploads/x.jpg") u apsolutan URL na kanonskom
   domenu — potreban za og:image/twitter:image, jer Facebook/Viber/Twitter ne
   mogu da učitaju relativnu putanju. Već apsolutan http(s) URL vraća nepromenjen. */
function seba_abs_url(string $path): string {
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    return SEBA_SITE_URL . '/' . ltrim($path, '/');
}

/* ---------- sesija / auth ---------- */

function seba_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
        'path'     => '/',
    ]);
    session_name('SEBASESS');
    session_start();
}

function load_users(): array {
    $raw = @file_get_contents(SEBA_DATA . '/users.json');
    $u = $raw ? json_decode($raw, true) : null;
    return is_array($u) ? $u : [];
}

function save_users(array $users): bool {
    return file_put_contents(
        SEBA_DATA . '/users.json',
        json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}

function is_logged_in(): bool {
    seba_session_start();
    return !empty($_SESSION['seba_user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------- CSRF ---------- */

function csrf_token(): string {
    seba_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    seba_session_start();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- rate limit prijave ---------- */

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function login_attempts_path(): string {
    return SEBA_DATA . '/attempts.json';
}

/* max 5 pokušaja u 10 minuta po IP adresi */
function login_blocked(): bool {
    $raw = @file_get_contents(login_attempts_path());
    $all = $raw ? json_decode($raw, true) : [];
    $ip = client_ip();
    $now = time();
    $recent = array_filter($all[$ip] ?? [], fn($t) => $t > $now - 600);
    return count($recent) >= 5;
}

function login_record_failure(): void {
    $raw = @file_get_contents(login_attempts_path());
    $all = $raw ? json_decode($raw, true) : [];
    if (!is_array($all)) $all = [];
    $ip = client_ip();
    $now = time();
    $all[$ip] = array_values(array_filter($all[$ip] ?? [], fn($t) => $t > $now - 600));
    $all[$ip][] = $now;
    file_put_contents(login_attempts_path(), json_encode($all), LOCK_EX);
}

function login_clear_failures(): void {
    $raw = @file_get_contents(login_attempts_path());
    $all = $raw ? json_decode($raw, true) : [];
    if (!is_array($all)) $all = [];
    unset($all[client_ip()]);
    file_put_contents(login_attempts_path(), json_encode($all), LOCK_EX);
}

/* ---------- JSON odgovor za admin AJAX ---------- */

/* bez return tipa namerno — "never" je PHP 8.1+, ova funkcija uvek zove exit() */
function json_out(array $payload, int $code = 200) {
    /* odbaci sve što je eventualno već ispisano (notice/warning/deprecated, whitespace...)
       da odgovor ostane čist JSON bez obzira šta se dogodilo ranije u skriptu */
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
