# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A flat-file PHP CMS for a single client site (SEBA Crash Bars — Serbian-language motorcycle protection equipment shop, Belgrade). No database, no build step, no package manager — plain PHP 8.1+ rendered server-side. All site content lives in `content.json`; the client edits it through a custom admin panel at `/admin/`. This is production code deployed directly to shared hosting (see README.md for hosting/deploy requirements), not a framework-based app.

## Running / testing locally

There is no build step and no test suite. To work on this locally, run any PHP dev server from the repo root, e.g.:

```
php -S localhost:8000
```

Then visit `http://localhost:8000/` for the public site and `http://localhost:8000/admin/` for the CMS (default login `admin` / `SebaAdmin2026!`, see README.md — change immediately on any real deploy). `content.json`, `data/`, and `uploads/` must be writable by the PHP process.

There are no automated tests, linter, or formatter configured in this repo — verify changes by exercising the site/admin panel directly.

## Architecture

**Content model:** `content.json` is the single source of truth for everything editable — `settings` (site-wide fields like phone, email, GA id) and an ordered `sections` array. Each section has `id`, `type`, `label`, `visible`, and a `fields` object whose shape is defined per-type in `inc/schemas.php`. The public page (`index.php`) filters to visible sections and renders them in array order; the admin drag-reorder writes back that same order.

**Adding a new section type** touches three places, in order:
1. `inc/schemas.php` — declare the field schema (`seba_schemas()`). Field types: `text`, `textarea`, `image`, `link`, `items` (repeatable sub-objects, e.g. products/projects/FAQ/testimonials list rows). The admin edit form and the save-time validator are both generated from this schema — there's no separate admin form markup to write per field.
2. `inc/render.php` — add a `case` to `render_section()` with the section's HTML output (`switch` on `$sec['type']`).
3. `content.json` — add the section object itself (or the client adds it via admin once the type exists).

**Request flow:**
- `index.php` — public site. Loads content, filters/orders visible sections, renders header/nav/footer plus each section via `render_section()`.
- `admin/index.php` — CMS shell (sections list, settings form, password form), server-rendered from the same schema. Client-side interactivity (drag reorder, edit-toggle, items add/remove) lives in `admin/admin.js`.
- `admin/save.php` — single AJAX endpoint for all admin mutations, dispatched by `action` param: `reorder`, `save_section`, `save_settings`, `change_password`, `upload`. Every action requires a logged-in session and a valid CSRF token (`csrf_check`). This is the only place content is written — always via `validate_section_fields()` (schema-driven whitelist, everything reduced to trimmed/length-capped strings) so arbitrary keys can never reach `content.json`.
- `admin/login.php` / `logout.php` — bcrypt password auth against `data/users.json`, with IP-based rate limiting (5 attempts / 10 min, tracked in `data/attempts.json`).
- `inc/functions.php` — shared helpers: content/user load-save (atomic write via tmp file + rename), session setup (`SEBASESS` cookie, httponly/samesite/secure), CSRF, rate limiting, `e()` for HTML-escaping, `safe_image_src()` for path/URL whitelisting, `json_out()` for admin JSON responses.

**Security posture already built in** (see README.md "Bezbednost" for full detail — don't re-derive or relax these without reason): all output escaped via `e()`, no raw HTML from content ever rendered; uploads restricted to JPG/PNG/WEBP, verified by real content (`finfo` + `getimagesize`) not extension, re-encoded through GD (strips metadata/embedded payloads) and renamed randomly; `data/`, `inc/`, and `content.json` blocked from direct HTTP access via `.htaccess`; CSRF token required on every admin mutation; only schema-defined fields can ever be written to `content.json`.

**Images:** `safe_image_src()` only accepts `https?://` URLs or local paths starting with `uploads/` or `assets/` — anything else is dropped. Client uploads go through `admin/save.php`'s `upload` action, get resized to max 1920px, and land in `uploads/` with a randomized filename.

## Language note

All user-facing strings, admin UI copy, and content are in Serbian. Match that when adding new UI text, labels, or error messages.
