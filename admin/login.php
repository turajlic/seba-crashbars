<?php
declare(strict_types=1);
require __DIR__ . '/../inc/functions.php';
seba_session_start();

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesija je istekla. Pokušajte ponovo.';
    } elseif (login_blocked()) {
        $error = 'Previše neuspešnih pokušaja. Sačekajte 10 minuta.';
    } else {
        $users = load_users();
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if (isset($users[$u]) && password_verify($p, $users[$u]['hash'])) {
            session_regenerate_id(true);
            $_SESSION['seba_user'] = $u;
            login_clear_failures();
            header('Location: index.php');
            exit;
        }
        login_record_failure();
        $error = 'Pogrešno korisničko ime ili lozinka.';
    }
}
?><!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Prijava — SEBA CMS</title>
<link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
<form class="login-card" method="post" autocomplete="off">
  <p class="brand-mark">SEBA <span>CMS</span></p>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <label>Korisničko ime
    <input type="text" name="username" required autofocus autocomplete="username">
  </label>
  <label>Lozinka
    <input type="password" name="password" required autocomplete="current-password">
  </label>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <button type="submit" class="btn-primary">Prijavi se</button>
</form>
</body>
</html>
