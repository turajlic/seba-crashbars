# SEBA Crash Bars — sajt + CMS

Jednostavan flat-file CMS (bez baze podataka). Ceo sadržaj sajta je u `content.json`,
a klijent ga menja kroz admin panel na `/admin/`.

## Zahtevi hostinga

- PHP 8.1 ili noviji
- GD ekstenzija (za obradu slika) — standard na svakom shared hostingu
- Apache sa `.htaccess` podrškom (ili ekvivalentna nginx pravila, vidi dole)

## Instalacija

1. Prebaci ceo sadržaj ovog foldera u web root (npr. `public_html/`).
2. Proveri da su `content.json`, folder `data/` i folder `uploads/` upisivi za PHP
   (na većini shared hostinga već jesu; u suprotnom `chmod 664 content.json` i `chmod 775 data uploads`).
3. Otvori `https://domen.rs/admin/` i prijavi se:
   - korisničko ime: `admin`
   - lozinka: `SebaAdmin2026!`
4. **ODMAH promeni lozinku** u Podešavanja → Promena lozinke.
5. U Podešavanjima unesi Google Analytics ID (`G-XXXXXXXXXX`) ako se koristi.

## Slike

Logo, favicon, hero fotografija i slika proizvoda su lokalno u `uploads/`. Fotografije
u sekcijama Radovi i Vesti još uvek pokazuju na stari sajt (crashbars.rs) — pre gašenja
starog sajta otpremi ih kroz CMS (dugme „Otpremi sliku"). CMS ih automatski smanjuje
na max 1920px.

## Šta klijent može

- **Redosled sekcija**: prevlačenjem u listi (čuva se automatski)
- **Uključivanje/isključivanje sekcija**: prekidač pored naziva
- **Tekstovi i slike**: dugme „Izmeni" kod svake sekcije
- **Stavke u listama** (proizvodi, radovi, pitanja, utisci, vesti): dodavanje,
  uklanjanje i prevlačenje redosleda
- **Podešavanja**: naziv sajta, meta opis, GA ID, telefon, mejl, adresa, Facebook

## Bezbednost (šta je ugrađeno)

- Lozinke: bcrypt hash (`password_hash`), nikad u čistom tekstu
- Prijava: rate limit 5 pokušaja / 10 min po IP adresi
- CSRF tokeni na svim izmenama
- Upload: samo JPG/PNG/WEBP, provera stvarnog sadržaja fajla (finfo + getimagesize),
  re-enkodiranje kroz GD (briše metapodatke i eventualni ugrađeni kod),
  nasumično ime fajla, `.htaccess` zabrana izvršavanja u `uploads/`
- Svi tekstovi se escapuju pri prikazu (nema unosa HTML-a)
- `data/` i `inc/` blokirani za direktan pristup; `content.json` takođe
- Šema polja: u JSON može da se upiše samo ono što je definisano u `inc/schemas.php`

Preporuka: uključi HTTPS na hostingu (Let's Encrypt) — session cookie automatski
postaje `Secure`.

## nginx (ako hosting nije Apache)

```
location ~ ^/(data|inc)/ { deny all; }
location ~ ^/content\.json { deny all; }
location ~* ^/uploads/.*\.php$ { deny all; }
```

## Struktura

```
index.php          — javni sajt (renderuje sekcije po redosledu iz content.json)
content.json       — SAV sadržaj sajta
inc/functions.php  — pomoćne funkcije, auth, CSRF, rate limit
inc/schemas.php    — definicija polja po tipu sekcije (dodavanje novih tipova ovde)
inc/render.php     — HTML šabloni sekcija
admin/             — CMS panel
data/users.json    — nalozi (hash lozinki)
uploads/           — slike koje klijent otprema
```

## Dodavanje novog tipa sekcije (za developera)

1. Dodaj šemu polja u `inc/schemas.php`
2. Dodaj `case` u `inc/render.php` sa HTML-om
3. Dodaj objekat sekcije u `content.json`
Admin forma se generiše automatski iz šeme.
