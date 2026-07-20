<?php
declare(strict_types=1);

define('SEBA_ROOT', dirname(__DIR__));
define('SEBA_CONTENT', SEBA_ROOT . '/content.json');
define('SEBA_DATA', SEBA_ROOT . '/data');
define('SEBA_UPLOADS', SEBA_ROOT . '/uploads');

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
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $tmp = SEBA_CONTENT . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, SEBA_CONTENT);
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
