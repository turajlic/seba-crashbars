# Deploy na cPanel (Git)

Koraci za deploy ovog repoa na cPanel hosting preko ugrađenog Git Version Control alata.

## 1. Prvi deploy

1. cPanel → **Git Version Control** → **Create**.
   - Repository URL: URL ovog GitHub repoa (posle push-a).
   - Repository Path: npr. `public_html` (ako je ovo jedini sajt na nalogu) ili poddirektorijum pa symlink/alias na domen.
   - Branch: `main`.
2. Sačekaj da cPanel klonira repo u zadati folder.
3. Proveri dozvole (cPanel File Manager ili SSH):
   ```
   chmod 755 data uploads
   chmod 664 content.json
   ```
   `data/` i `uploads/` moraju biti upisivi za PHP (owner/group write), a `content.json`
   mora ostati upisiv jer admin panel u njega piše pri svakoj izmeni.
4. Pošto `data/users.json` i `data/attempts.json` **nisu** u repou (namerno isključeni,
   vidi `.gitignore`), napravi ih ručno na serveru pri prvom postavljanju:
   - Najlakše: otvori `https://domen.rs/admin/` — ako `data/users.json` ne postoji,
     proveri da li aplikacija ima ugrađen inicijalni setup (pogledaj `inc/functions.php`);
     ako ne, ručno kopiraj lokalni `data/users.json` na server preko File Manager-a
     (SFTP/upload), NE preko gita.
   - `data/attempts.json` nije obavezan unapred — kreira ga aplikacija sama pri prvom
     neuspelom pokušaju prijave (proveri da je `data/` upisiv da bi to uspelo).
5. Otvori `https://domen.rs/admin/`, prijavi se, **odmah promeni lozinku**
   (podrazumevana je `admin` / `SebaAdmin2026!` — vidi README.md).

## 2. VAŽNO — posle prvog deploya zaključaj content.json

`content.json` je namerno uključen u prvi commit da server dobije početni sadržaj sajta.
Ali čim je sajt živ, klijent (Seba) menja sadržaj kroz admin panel direktno na serveru —
taj fajl se više ne sme prepisivati sa gita.

Odmah posle prvog uspešnog deploya:

1. Otvori `.gitignore` u repou i otkomentariši/dodaj liniju:
   ```
   content.json
   ```
2. Ukloni ga iz praćenja (ali ostavi fajl na disku):
   ```
   git rm --cached content.json
   git commit -m "Prestani da pratiš content.json posle prvog deploya"
   git push
   ```
3. Na serveru, posle sledećeg `git pull`, cPanel Git alat neće više dirati
   `content.json` — Sebine izmene kroz admin panel ostaju netaknute.

Ako ovaj korak preskočiš, sledeći `git pull` na serveru može prepisati živi sadržaj
sa verzijom iz repoa i obrisati sve što je klijent uneo kroz CMS.

## 3. Naredni update-i (deploy izmena koda)

U cPanel Git Version Control → izaberi repo → **Pull or Deploy** → **Update from Remote**,
ili preko SSH:
```
git pull origin main
```
Pošto `content.json`, `data/users.json`, `data/attempts.json` i korisnički upload-ovani
fajlovi nisu praćeni (posle koraka 2), pull menja samo kod — sadržaj i uploads ostaju
netaknuti.

## Dozvole — rezime

| Putanja              | Dozvola | Zašto |
|-----------------------|---------|-------|
| `content.json`        | 664     | Upisiv za PHP (admin čuva izmene), čitljiv za web server |
| `data/`                | 755     | PHP mora da piše `users.json`/`attempts.json` unutra |
| `uploads/`             | 755     | PHP mora da čuva otpremljene slike unutra |
| ostali fajlovi/folderi | podrazumevano (644/755) | nema potrebe za posebnim izmenama |

Ako shared hosting koristi drugačijeg PHP korisnika od onog koji poseduje fajlove
(retko na cPanel-u, ali proveri), možda će trebati `775`/`664` sa odgovarajućom grupom
umesto `755`/`664` — proveri grešku "Permission denied" u PHP error logu ako upload ili
čuvanje sadržaja ne rade.
