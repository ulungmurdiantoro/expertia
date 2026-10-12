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

`deploy.sh` memeriksa konfigurasi, membackup database ke `storage/app/backups` (10 terakhir), memasang mode maintenance, `git merge --ff-only`, `composer install --no-dev`, build aset (dilewati bila frontend tidak berubah), `migrate --force`, cache konfigurasi/route/view, lalu membuka situs. Bila satu langkah gagal, situs tetap dalam maintenance dan script mencetak lokasi backup serta cara kembali ke commit sebelumnya.

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
- **`.env` produksi**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://expertia.id` (dipakai untuk URL canonical dan og:image), serta konfigurasi email agar reset password berfungsi.
- **Document root** web server diarahkan ke folder `public/`, bukan ke root proyek.
- Queue worker belum dibutuhkan; belum ada job yang di-queue.
- **Versi PHP**: server produksi memakai PHP 8.3, dan `composer.json` mengunci `config.platform.php` ke `8.3.31` agar `composer update` di komputer dengan PHP lebih baru tidak memasang paket yang tidak jalan di server. Bila PHP server dinaikkan, ubah nilai itu juga.

Jadwal yang terdaftar bisa dicek dengan `php artisan schedule:list`.
