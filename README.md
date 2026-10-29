# Expertia.id

Media kepakaran Indonesia: riset, paten, inovasi, dan perspektif para pakar yang diolah redaksi menjadi tulisan populer.

Laravel 12 + Inertia (Vue 3) + Tailwind. Role dan permission memakai `spatie/laravel-permission`.

## Menjalankan di komputer lokal

Butuh PHP 8.2+, Composer, dan Node 20+.

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed      # membuat tabel, role, akun demo, dan artikel contoh
npm install
npm run dev                     # biarkan berjalan
php artisan serve               # http://127.0.0.1:8000
```

Akun demo dari seeder (password `password123`): `superadmin@example.com`, `admin@example.com`, `editor@example.com`, `moderator@example.com`, `verified.expert@example.com`, `author@example.com`, `user@example.com`.

Test: `php artisan test` (memakai SQLite di memori, tidak menyentuh database lokal).

## Alur redaksi

```
draft ──submit──▶ in_review / pending_review ──approve──▶ published (atau scheduled bila ada jadwal)
  ▲                                │
  └──── revision_requested ◀───────┤
                                   └──reject──▶ rejected (bisa diperbaiki lalu diajukan lagi)
```

- Semua perubahan status lewat `App\Services\ArticleWorkflow`. Transisi yang tidak sah (misalnya approve draft yang belum diajukan) ditolak dengan pesan di kunci `status`.
- Author hanya bisa mengubah naskah berstatus `draft` atau `rejected` (`App\Policies\ArticlePolicy`). Setelah diajukan atau tayang, perubahan dilakukan editor.
- Slug dibekukan setelah artikel disetujui (`Article::hasFrozenSlug()`), jadi mengganti judul tidak mengubah URL yang sudah dibagikan.
- Artikel terjadwal ditayangkan oleh scheduler (lihat bagian Deploy).

## Kontributor

Pendaftaran author publik ditutup. Kontributor mendaftar lewat formulir program kontributor (`/kontributor`), lalu redaksi membuat akunnya di **Dashboard → System → Tambah Kontributor** (`/admin/contributors/create`, butuh permission `users.assign_role`). Pilih status *Kontributor Terverifikasi* (`verified-expert`) bila identitas dan afiliasi sudah diperiksa.

Pembaca biasa tetap bisa mendaftar di `/register` (role `user`).

## Aturan penting

1. **Jangan kirim model Eloquent mentah ke halaman publik.** Pakai resource di `app/Http/Resources` (`ArticleCardResource`, `ArticleDetailResource`, `PublicAuthorResource`). Model `User` berisi email dan nomor WhatsApp; Kebijakan Privasi menjanjikan keduanya tidak pernah tampil.
2. **Isi artikel selalu disaring** oleh mutator `content` di model `Article` (`App\Support\ArticleHtmlSanitizer`). Jangan menyimpan isi artikel lewat `DB::table()` karena itu melewati penyaringan.
3. **Meta tag (title, description, og:\*)** dicetak server di `resources/views/app.blade.php` dari prop `meta` (`App\Support\PageMeta`). Halaman publik baru wajib mengirim prop ini, karena WhatsApp dan LinkedIn tidak menjalankan JavaScript.
4. **Kontak situs** (WhatsApp, email, Instagram, link formulir) ada di `resources/js/site.js`.
5. **CSS halaman publik** ada di `resources/css/public.css` dan hanya berlaku di dalam wrapper `.xp` (`PublicLayout`, `GuestLayout`), jadi dashboard tidak terpengaruh.
6. **Permission** yang berlaku hanya yang dicek kode, tercantum di `RoleAndPermissionSeeder::PERMISSIONS`. Tambahkan permission baru di sana bersamaan dengan kode yang memeriksanya.
7. **Halaman publik baru** diberi nama komponen `Public/...` agar ikut di-render server (SSR), mengirim prop `meta` lewat `PageMeta::make()` (dengan `jsonLd` bila relevan), dan ditambahkan ke sitemap di `DiscoveryController`.
8. **Kolom statistik artikel** (`view_count`, `like_count`, `score_*`, dll., lihat `Article::STATISTIC_COLUMNS`) tidak menggeser `updated_at`, karena `updated_at` dipakai sebagai tanggal ubah di JSON-LD, sitemap, dan meta. Kolom statistik baru harus ditambahkan ke daftar itu.

## SEO dan akses AI

- **Render server (SSR)**: halaman publik (`Welcome` dan `Public/*`) dirender di server oleh `resources/js/ssr.js`, sehingga isi artikel ada di HTML awal untuk mesin pencari dan crawler AI yang tidak menjalankan JavaScript. Dashboard tetap dirender di browser (`App\Support\PublicPagesSsrGateway`). Bila server SSR mati, situs tetap tampil (render di browser) dan dashboard admin menampilkan peringatan.
- **Meta dan structured data**: setiap halaman publik mencetak description, canonical, robots, Open Graph, `article:*`, dan JSON-LD (`Article`, `BreadcrumbList`, `ProfilePage`/`Person`, `NewsMediaOrganization`, `WebSite`) dari server. Hasil pencarian, feed pribadi, dan profil tanpa karya diberi `noindex`; login dan dashboard `noindex, nofollow`.
- **Satu domain**: di produksi semua URL buatan aplikasi memakai `APP_URL`, dan host lain (`www.`) dialihkan 301. Pengalihan http ke https diatur nginx (`deploy/nginx-redirects.conf`).
- **Berkas untuk mesin pencari dan AI**:
  - `/sitemap.xml` (dengan gambar), `/feed.xml` (RSS berisi artikel lengkap)
  - `/robots.txt`: konten publik terbuka untuk mesin pencari, asisten AI (OAI-SearchBot, ChatGPT-User, Claude-SearchBot, PerplexityBot, ...), dan crawler pelatihan AI (GPTBot, ClaudeBot, Google-Extended, ...). Crawler pelatihan bisa ditutup dengan `AI_ALLOW_TRAINING=false` di `.env`. Di luar produksi, semua crawler ditolak.
  - `/llms.txt` (ringkasan situs untuk model bahasa) dan `/llms-full.txt` (semua artikel dalam satu berkas Markdown)
  - Versi Markdown tiap artikel: tambahkan `.md` di akhir URL artikel.
- **Gambar**: unggahan thumbnail dan avatar diperkecil ke WebP bila ekstensi GD terpasang (`App\Support\ImageOptimizer`). Gambar lama: `php artisan images:optimize`.

Setelah deploy pertama, daftarkan situs dan kirim `https://expertia.id/sitemap.xml` di Google Search Console dan Bing Webmaster Tools.

## Batas dan data

- Rate limit (lihat `AppServiceProvider`): registrasi 10/jam per IP, komentar 5/menit dan 100/hari, laporan 20/jam, reaksi/bookmark/follow 60/menit, share 10/menit per IP. Share hanya dihitung sekali per sesi per artikel.
- View tidak dihitung untuk bot dan pratinjau tautan (`App\Support\BotDetector`), dan dicatat setelah respons terkirim.
- Data kunjungan (`article_views`, `tracking_events`) disimpan 1 tahun, log audit (`activity_logs`) 2 tahun, lalu dipangkas `model:prune`.

## Deploy

Di server (Debian 13, folder `/var/www/expertia.id`), setelah perubahan di-push ke `origin/main`:

```sh
cd /var/www/expertia.id
DRY_RUN=1 bash deploy.sh    # opsional: lihat langkahnya dulu
bash deploy.sh
```

`deploy.sh` memeriksa konfigurasi, membackup database ke `storage/app/backups` (10 terakhir), memasang mode maintenance, `git merge --ff-only`, `composer install --no-dev`, build aset dan bundle SSR (dilewati bila frontend tidak berubah), `migrate --force`, cache konfigurasi/route/view, menulis `public/robots.txt`, memuat ulang SSR, lalu membuka situs. Bila satu langkah gagal, situs tetap dalam maintenance dan script mencetak lokasi backup serta cara kembali ke commit sebelumnya.

Pertama kali, `deploy.sh` belum ada di server; ambil langsung dari origin:

```sh
cd /var/www/expertia.id
git fetch origin main
git show origin/main:deploy.sh > /tmp/deploy.sh
APP_DIR=/var/www/expertia.id bash /tmp/deploy.sh
```

Wajib di server:

- **Cron scheduler** (tanpa ini artikel terjadwal tidak pernah tayang dan log tidak dipangkas):
  ```
  * * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
  ```
  Dashboard admin menampilkan peringatan merah bila scheduler tidak berjalan lebih dari 10 menit.
- **`.env` produksi**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://expertia.id` (dipakai untuk URL canonical dan og:image), serta konfigurasi email (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Reset password lewat email. Verifikasi email untuk akun baru **nonaktif secara bawaan**; nyalakan dengan `REQUIRE_EMAIL_VERIFICATION=true` lalu `php artisan config:cache` setelah email terbukti terkirim. Saat aktif, akun baru harus memverifikasi alamat emailnya sebelum bisa berkomentar, menyimpan artikel, mengikuti penulis, atau membuka dashboard, dan `deploy.sh` menolak deploy bila `MAIL_MAILER` masih `log`/`array` (lewati dengan `ALLOW_NO_MAIL=1`).
- **Document root** web server diarahkan ke folder `public/`, bukan ke root proyek.
- Queue worker belum dibutuhkan; belum ada job yang di-queue.
- **Render server (SSR)**, sekali pasang (`deploy.sh` memuat ulang bundle-nya setiap deploy):
  ```sh
  sudo cp deploy/expertia-ssr.service /etc/systemd/system/
  sudo systemctl daemon-reload && sudo systemctl enable --now expertia-ssr
  php artisan inertia:check-ssr
  ```
  Service ini membatasi lalu lintas SSR ke localhost (server SSR Inertia mendengarkan di semua antarmuka pada port 13714 dan punya endpoint `/shutdown`).
- **Ekstensi GD** untuk memperkecil gambar: `sudo apt install php8.3-gd && sudo systemctl reload php8.3-fpm`, lalu sekali `php artisan images:optimize` untuk gambar lama (`--delete-originals` untuk menghapus file aslinya).
- **Pengalihan nginx** http dan www ke `https://expertia.id`: lihat `deploy/nginx-redirects.conf`.
- **Versi PHP**: server produksi memakai PHP 8.3, dan `composer.json` mengunci `config.platform.php` ke `8.3.31` agar `composer update` di komputer dengan PHP lebih baru tidak memasang paket yang tidak jalan di server. Bila PHP server dinaikkan, ubah nilai itu juga.

Jadwal yang terdaftar bisa dicek dengan `php artisan schedule:list`.
