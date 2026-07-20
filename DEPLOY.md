# Deploy na cPanel (Git)

Sajt je live na **crashbars.rs**, Telekom cPanel hosting (`crashbar` nalog), deploy ide
preko cPanel-ovog Git Version Control alata i `.cpanel.yml` u root-u repoa.

## Kako radi automatski deploy (`.cpanel.yml`)

Pri svakom "Update from Remote" / "Deploy HEAD Commit" u cPanel Git Version Control,
cPanel iz `.cpanel.yml` pokreće `rsync -a` (bez `--delete`) iz repoa u
`/home/crashbar/public_html/`. To znači:

- Kopira/ažurira samo fajlove koji postoje u repou — kod, `.htaccess` fajlove, brend
  slike iz `uploads/`.
- **Ne briše i ne dira** ništa što je na serveru a nije u repou: `data/users.json`,
  `data/attempts.json`, sve klijentski otpremljene slike u `uploads/` (nasumična imena),
  i `content.json`.
- `content.json` je eksplicitno izuzet iz kopiranja (`--exclude`) — i nije praćen u gitu
  (vidi `.gitignore`). Sadržaj sajta menja se isključivo preko admin panela direktno na
  serveru; git deploy ga nikad ne dodiruje.
- `README.md`, `DEPLOY.md`, `CLAUDE.md` su takođe izuzeti iz kopiranja — to su interni
  dev dokumenti (README dokumentuje i podrazumevanu admin lozinku pre promene), nema
  razloga da budu javno dostupni na `public_html/`.

## Prvi deploy na novom nalogu (ako se ikad ponavlja od nule)

1. cPanel → **Git Version Control** → **Create** → Repository URL ovog GitHub repoa,
   Repository Path tako da `.cpanel.yml` deploy cilja `/home/crashbar/public_html/`,
   Branch `main`.
2. Posle prvog uspešnog deploya, proveri dozvole (cPanel File Manager ili SSH):
   ```
   chmod 755 data uploads
   chmod 664 content.json
   ```
   `data/` i `uploads/` moraju biti upisivi za PHP, `content.json` takođe (admin panel
   u njega piše pri svakoj izmeni).
3. Pošto `data/users.json` ne postoji u repou, prvi put ga ručno postavi preko File
   Manager-a / SFTP-a (van gita) — vidi prethodnu prepisku za generisanje bcrypt hash-a.
4. Ako `content.json` ne postoji na serveru, ubaci ga ručno (File Manager) da sajt ima
   početni sadržaj — git deploy ga posle toga više ne dira.
5. Otvori `https://crashbars.rs/admin/`, prijavi se, **odmah promeni lozinku**.

## Naredni update-i (deploy izmena koda)

U cPanel Git Version Control → izaberi repo → **Pull or Deploy** → **Update from Remote**,
zatim **Deploy HEAD Commit** (ili ekvivalentno dugme koje pokreće `.cpanel.yml`).
`content.json`, `data/users.json`, `data/attempts.json` i klijentski uploadi ostaju
netaknuti — `.cpanel.yml` ih ne kopira i rsync bez `--delete` ih ne briše.

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
