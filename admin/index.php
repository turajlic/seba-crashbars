<?php
declare(strict_types=1);
require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/schemas.php';
require_login();

$content = load_content();
$schemas = seba_schemas();
$user = e($_SESSION['seba_user']);
?><!doctype html>
<html lang="sr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>SEBA CMS — Uređivanje sajta</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-shell">

  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <img src="../uploads/favicon.png" alt="" class="sidebar-logo">
      <span>SEBA <strong>CMS</strong></span>
    </div>
    <nav class="sidebar-nav">
      <button type="button" class="side-link is-active" data-view="sections">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="5" rx="1"/><rect x="3" y="12" width="18" height="5" rx="1"/></svg>
        Sekcije sajta
      </button>
      <button type="button" class="side-link" data-view="settings">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a7 7 0 0 0-1.7-1l-.3-2.5h-4l-.3 2.5a7 7 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a7 7 0 0 0 1.7 1l.3 2.5h4l.3-2.5a7 7 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5a7 7 0 0 0 .1-1z"/></svg>
        Podešavanja
      </button>
      <button type="button" class="side-link" data-view="password">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        Lozinka
      </button>
      <button type="button" class="side-link" data-view="backups">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 3v5h5"/><path d="M12 8v5l3 2"/></svg>
        Sigurnosne kopije
      </button>
      <button type="button" class="side-link" data-view="uploads">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m5 17 4.5-5 3 3.5L16 11l3 6"/></svg>
        Slike
      </button>
    </nav>
    <div class="sidebar-foot">
      <a href="../" target="_blank" rel="noopener">Pogledaj sajt ↗</a>
      <a href="logout.php" class="side-logout">Odjava (<?= $user ?>)</a>
    </div>
  </aside>

  <button type="button" class="admin-menu-toggle" aria-label="Meni">☰</button>

  <main class="admin-content">

  <!-- VIEW: Sekcije -->
  <section class="view is-active" data-view="sections">
    <div class="content-head">
      <h1>Sekcije sajta</h1>
      <p>Prevuci sekcije za promenu redosleda. „Izmeni" otvara tekstove i slike. Prekidač uključuje/isključuje sekciju na sajtu.</p>
    </div>

    <ul class="sections" id="sectionList">
      <?php foreach ($content['sections'] as $sec):
          $schema = $schemas[$sec['type']] ?? [];
      ?>
      <li class="section-row" data-id="<?= e($sec['id']) ?>">
        <div class="row-bar">
        <span class="drag-handle" title="Prevuci za promenu redosleda" aria-hidden="true">⠿</span>
        <strong class="row-label"><?= e($sec['label']) ?></strong>
        <label class="switch" title="Prikaži / sakrij sekciju">
          <input type="checkbox" class="vis-toggle" <?= !empty($sec['visible']) ? 'checked' : '' ?>>
          <span class="slider"></span>
        </label>
        <button type="button" class="btn-ghost edit-toggle">Izmeni</button>
      </div>
      <form class="row-editor" hidden data-type="<?= e($sec['type']) ?>">
        <?php foreach ($schema as $key => $def):
            [$ftype, $flabel] = $def;
            $val = $sec['fields'][$key] ?? ($ftype === 'items' ? [] : '');
            if ($ftype === 'items'): $sub = $def[2]; ?>
        <fieldset class="items-field" data-field="<?= e($key) ?>">
          <legend><?= e($flabel) ?></legend>
          <div class="items-list">
            <?php foreach ((array)$val as $item): ?>
            <div class="item-card">
              <div class="item-bar"><span class="drag-handle" aria-hidden="true">⠿</span><button type="button" class="item-remove" title="Ukloni stavku">×</button></div>
              <?php foreach ($sub as $sk => $sdef): [$stype, $slabel] = $sdef; ?>
              <?php if ($stype === 'images'): ?>
              <fieldset class="images-fld" data-key="<?= e($sk) ?>">
                <legend><?= e($slabel) ?></legend>
                <div class="images-list">
                  <?php foreach ((array)($item[$sk] ?? []) as $ival): $ival = (string)$ival; ?>
                  <div class="image-row">
                    <span class="drag-handle" aria-hidden="true">⠿</span>
                    <span class="img-fld">
                      <input type="text" value="<?= e($ival) ?>" placeholder="uploads/slika.jpg ili https://…">
                      <button type="button" class="btn-ghost img-upload">Otpremi sliku</button>
                      <?php $psrc = safe_image_src($ival); $psrc = $psrc && !preg_match('#^https?://#i', $psrc) ? '../' . $psrc : $psrc; ?>
                      <img class="img-preview" src="<?= e($psrc) ?>" alt="" <?= $psrc ? '' : 'hidden' ?>>
                    </span>
                    <button type="button" class="image-remove" title="Ukloni sliku">×</button>
                  </div>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-ghost image-add">+ Dodaj sliku</button>
              </fieldset>
              <?php else: $sval = (string)($item[$sk] ?? ''); ?>
              <label class="fld"><?= e($slabel) ?>
                <?php if ($stype === 'textarea'): ?>
                <textarea data-key="<?= e($sk) ?>" rows="2"><?= e($sval) ?></textarea>
                <?php elseif ($stype === 'image'): ?>
                <span class="img-fld" data-key="<?= e($sk) ?>">
                  <input type="text" value="<?= e($sval) ?>" placeholder="uploads/slika.jpg ili https://…">
                  <button type="button" class="btn-ghost img-upload">Otpremi sliku</button>
                  <?php $psrc = safe_image_src($sval); $psrc = $psrc && !preg_match('#^https?://#i', $psrc) ? '../' . $psrc : $psrc; ?>
                  <img class="img-preview" src="<?= e($psrc) ?>" alt="" <?= $psrc ? '' : 'hidden' ?>>
                </span>
                <?php else: ?>
                <input type="text" data-key="<?= e($sk) ?>" value="<?= e($sval) ?>">
                <?php endif; ?>
              </label>
              <?php endif; ?>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <template class="item-template">
            <div class="item-card">
              <div class="item-bar"><span class="drag-handle" aria-hidden="true">⠿</span><button type="button" class="item-remove" title="Ukloni stavku">×</button></div>
              <?php foreach ($sub as $sk => $sdef): [$stype, $slabel] = $sdef; ?>
              <?php if ($stype === 'images'): ?>
              <fieldset class="images-fld" data-key="<?= e($sk) ?>">
                <legend><?= e($slabel) ?></legend>
                <div class="images-list"></div>
                <button type="button" class="btn-ghost image-add">+ Dodaj sliku</button>
              </fieldset>
              <?php else: ?>
              <label class="fld"><?= e($slabel) ?>
                <?php if ($stype === 'textarea'): ?>
                <textarea data-key="<?= e($sk) ?>" rows="2"></textarea>
                <?php elseif ($stype === 'image'): ?>
                <span class="img-fld" data-key="<?= e($sk) ?>">
                  <input type="text" value="" placeholder="uploads/slika.jpg ili https://…">
                  <button type="button" class="btn-ghost img-upload">Otpremi sliku</button>
                  <img class="img-preview" src="" alt="" hidden>
                </span>
                <?php else: ?>
                <input type="text" data-key="<?= e($sk) ?>" value="">
                <?php endif; ?>
              </label>
              <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </template>
          <button type="button" class="btn-ghost item-add">+ Dodaj stavku</button>
        </fieldset>
            <?php elseif ($ftype === 'textarea'): ?>
        <label class="fld"><?= e($flabel) ?>
          <textarea data-field="<?= e($key) ?>" rows="3"><?= e((string)$val) ?></textarea>
        </label>
            <?php elseif ($ftype === 'image'): ?>
        <label class="fld"><?= e($flabel) ?>
          <span class="img-fld" data-field="<?= e($key) ?>">
            <input type="text" value="<?= e((string)$val) ?>" placeholder="uploads/slika.jpg ili https://…">
            <button type="button" class="btn-ghost img-upload">Otpremi sliku</button>
            <?php $psrc = safe_image_src((string)$val); $psrc = $psrc && !preg_match('#^https?://#i', $psrc) ? '../' . $psrc : $psrc; ?>
            <img class="img-preview" src="<?= e($psrc) ?>" alt="" <?= $psrc ? '' : 'hidden' ?>>
          </span>
        </label>
            <?php else: ?>
        <label class="fld"><?= e($flabel) ?>
          <input type="text" data-field="<?= e($key) ?>" value="<?= e((string)$val) ?>">
        </label>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="editor-actions">
          <button type="submit" class="btn-primary">Sačuvaj sekciju</button>
          <span class="save-status" aria-live="polite"></span>
        </div>
      </form>
    </li>
    <?php endforeach; ?>
    </ul>
  </section>

  <!-- VIEW: Podešavanja -->
  <section class="view" data-view="settings">
    <div class="content-head">
      <h1>Podešavanja sajta</h1>
      <p>Osnovni podaci koji se prikazuju u zaglavlju, podnožju i kontaktu.</p>
    </div>
    <form id="settingsForm" class="panel-form">
      <?php $s = $content['settings']; ?>
      <label class="fld">Naziv sajta<input type="text" name="site_title" value="<?= e($s['site_title'] ?? '') ?>"></label>
      <label class="fld">Meta opis (za Google)<textarea name="meta_description" rows="2"><?= e($s['meta_description'] ?? '') ?></textarea></label>
      <label class="fld">Google Analytics ID (npr. G-XXXXXXXXXX)<input type="text" name="ga_id" value="<?= e($s['ga_id'] ?? '') ?>" placeholder="Ostavi prazno ako se ne koristi"></label>
      <div class="fld-row">
        <label class="fld">Telefon<input type="text" name="phone" value="<?= e($s['phone'] ?? '') ?>"></label>
        <label class="fld">E-mail<input type="text" name="email" value="<?= e($s['email'] ?? '') ?>"></label>
      </div>
      <label class="fld">Adresa<input type="text" name="address" value="<?= e($s['address'] ?? '') ?>"></label>
      <div class="fld-row">
        <label class="fld">Facebook link<input type="text" name="facebook" value="<?= e($s['facebook'] ?? '') ?>"></label>
        <label class="fld">Instagram link<input type="text" name="instagram" value="<?= e($s['instagram'] ?? '') ?>"></label>
      </div>
      <label class="fld">Tekst u podnožju<textarea name="footer_note" rows="3"><?= e($s['footer_note'] ?? '') ?></textarea></label>
      <div class="editor-actions">
        <button type="submit" class="btn-primary">Sačuvaj podešavanja</button>
        <span class="save-status" aria-live="polite"></span>
      </div>
    </form>
  </section>

  <!-- VIEW: Lozinka -->
  <section class="view" data-view="password">
    <div class="content-head">
      <h1>Promena lozinke</h1>
      <p>Preporuka: najmanje 10 znakova, kombinacija slova i brojeva.</p>
    </div>
    <form id="passwordForm" class="panel-form narrow-form">
      <label class="fld">Trenutna lozinka<input type="password" name="current" required autocomplete="current-password"></label>
      <label class="fld">Nova lozinka (najmanje 10 znakova)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
      <div class="editor-actions">
        <button type="submit" class="btn-primary">Promeni lozinku</button>
        <span class="save-status" aria-live="polite"></span>
      </div>
    </form>
  </section>

  <!-- VIEW: Sigurnosne kopije -->
  <section class="view" data-view="backups">
    <div class="content-head">
      <h1>Sigurnosne kopije</h1>
      <p>Sistem automatski sačuva kopiju sadržaja pre svake izmene (redosled, sekcije, podešavanja). Ako nešto slučajno obrišeš ili pogrešno izmeniš, ovde možeš da se vratiš na raniju verziju.</p>
    </div>
    <div class="editor-actions backup-actions">
      <a class="btn-ghost" href="backup.php?file=current" target="_blank" rel="noopener">Preuzmi trenutni sadržaj</a>
    </div>
    <?php $backups = seba_list_backups(); ?>
    <?php if (!$backups): ?>
    <p class="backup-empty">Još nema sačuvanih kopija — pojaviće se posle prve sledeće izmene bilo čega na sajtu.</p>
    <?php else: ?>
    <ul class="backup-list">
      <?php foreach ($backups as $b): ?>
      <li class="backup-row">
        <span class="backup-date"><?= e(date('d.m.Y. H:i:s', $b['mtime'])) ?></span>
        <span class="backup-size"><?= e(number_format($b['size'] / 1024, 1)) ?> KB</span>
        <a class="btn-ghost" href="backup.php?file=<?= e($b['file']) ?>" target="_blank" rel="noopener">Preuzmi</a>
        <button type="button" class="btn-ghost backup-restore" data-file="<?= e($b['file']) ?>">Vrati ovu verziju</button>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <!-- VIEW: Slike -->
  <section class="view" data-view="uploads">
    <div class="content-head">
      <h1>Slike</h1>
      <p>Sve otpremljene slike u <code>uploads/</code>. Možeš otpremiti slike unapred (za radove ili proizvode koje ćeš dodati kasnije) — „Kopiraj putanju" prebacuje putanju te slike u clipboard, pa je nalepiš u odgovarajuće polje slike kad budeš pravio/la tu sekciju. „Koristi se" znači da je slika trenutno postavljena negde — takve i zaštićene brend slike (logo, favicon, naslovna) ne mogu da se obrišu odavde.</p>
    </div>
    <div class="editor-actions backup-actions">
      <button type="button" class="btn-primary" id="uploadNewImage">+ Otpremi novu sliku</button>
      <span class="save-status" id="uploadNewStatus" aria-live="polite"></span>
    </div>
    <?php $uploadsList = seba_list_uploads($content); ?>
    <?php if (!$uploadsList): ?>
    <p class="backup-empty">Nema otpremljenih slika.</p>
    <?php else: ?>
    <ul class="upload-grid" id="uploadGrid">
      <?php foreach ($uploadsList as $u): ?>
      <li class="upload-card" data-file="<?= e($u['file']) ?>">
        <img class="upload-thumb" src="../uploads/<?= e($u['file']) ?>" alt="" loading="lazy">
        <div class="upload-meta">
          <span class="upload-name" title="<?= e($u['file']) ?>"><?= e($u['file']) ?></span>
          <span class="upload-size"><?= e(number_format($u['size'] / 1024, 1)) ?> KB</span>
        </div>
        <?php if ($u['protected']): ?>
        <span class="upload-badge upload-badge-protected">Zaštićena</span>
        <?php elseif ($u['used']): ?>
        <span class="upload-badge upload-badge-used">Koristi se</span>
        <?php else: ?>
        <span class="upload-badge upload-badge-free">Nije u upotrebi</span>
        <?php endif; ?>
        <div class="upload-actions">
          <button type="button" class="upload-copy" data-path="uploads/<?= e($u['file']) ?>" title="Kopiraj putanju u clipboard">Kopiraj putanju</button>
          <?php if (!$u['protected'] && !$u['used']): ?>
          <button type="button" class="upload-delete" data-file="<?= e($u['file']) ?>">Obriši</button>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  </main>
</div>

<div class="toast" hidden aria-live="polite"></div>

<template id="imageRowTemplate">
  <div class="image-row">
    <span class="drag-handle" aria-hidden="true">⠿</span>
    <span class="img-fld">
      <input type="text" value="" placeholder="uploads/slika.jpg ili https://…">
      <button type="button" class="btn-ghost img-upload">Otpremi sliku</button>
      <img class="img-preview" src="" alt="" hidden>
    </span>
    <button type="button" class="image-remove" title="Ukloni sliku">×</button>
  </div>
</template>

<script>window.SEBA_CSRF = <?= json_encode(csrf_token()) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="admin.js"></script>
</body>
</html>
