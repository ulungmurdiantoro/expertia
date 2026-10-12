#!/usr/bin/env bash
#
# Deploy Expertia.id: ambil commit terbaru dari origin, backup database, pasang
# dependensi, build aset, migrasi, lalu cache konfigurasi.
#
# Jalankan di server, dari folder aplikasi:
#
#   bash deploy.sh                 deploy commit terbaru origin/main
#   DRY_RUN=1 bash deploy.sh       tampilkan langkah tanpa mengubah apa pun
#   FORCE=1 bash deploy.sh         jalankan walau tidak ada commit baru
#   SKIP_BUILD=1 bash deploy.sh    lewati npm ci && npm run build
#   RELOAD_FPM=1 bash deploy.sh    reload php-fpm di akhir (butuh sudo)
#
# Variabel lain: APP_DIR (default folder script ini), BRANCH (default main),
# KEEP_BACKUPS (default 10), FPM_SERVICE (default php8.3-fpm).
#
# Situs masuk mode maintenance sejak `git merge` sampai `php artisan up`
# (biasanya 1-3 menit, terutama untuk build aset).

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
BRANCH="${BRANCH:-main}"
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"
DRY_RUN="${DRY_RUN:-0}"
FORCE="${FORCE:-0}"
SKIP_BUILD="${SKIP_BUILD:-0}"
RELOAD_FPM="${RELOAD_FPM:-0}"
FPM_SERVICE="${FPM_SERVICE:-php8.3-fpm}"
MIN_PHP="8.2.0"
MIN_NODE="20.19.0"

BACKUP_DIR="$APP_DIR/storage/app/backups"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
STARTED_AT="$(date +%s)"

IN_MAINTENANCE=0
PREVIOUS_COMMIT=""
DB_BACKUP_FILE=""
SQLITE_PATH=""
SQLITE_PARKED=()

# Berkas yang, bila berubah, menandakan aset frontend perlu di-build ulang.
FRONTEND_PATHS=(resources/js resources/css package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js)

# ---------------------------------------------------------------------------
# Bantuan
# ---------------------------------------------------------------------------

info() { printf '\n\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '    \033[32m✓\033[0m %s\n' "$*"; }
warn() { printf '    \033[33m!\033[0m %s\n' "$*" >&2; }
fail() { printf '\n\033[1;31mGAGAL:\033[0m %s\n' "$*" >&2; exit 1; }

# Menjalankan perintah, atau hanya menampilkannya saat DRY_RUN=1.
run() {
    if [[ "$DRY_RUN" == "1" ]]; then
        printf '    [dry-run] %s\n' "$*"
    else
        printf '    $ %s\n' "$*"
        "$@"
    fi
}

# Membaca satu nilai dari .env (tanpa tanda kutip pembungkus).
env_get() {
    local line
    line="$(grep -E "^${1}=" "$APP_DIR/.env" | tail -n 1 || true)"
    line="${line#*=}"
    line="${line%$'\r'}"
    if [[ "$line" == \"*\" || "$line" == \'*\' ]]; then
        line="${line:1:${#line}-2}"
    fi
    printf '%s' "$line"
}

on_error() {
    local exit_code=$? line=$1 command_text=$2 file
    trap - ERR
    printf '\n\033[1;31mDeploy berhenti di baris %s (kode keluar %s): %s\033[0m\n' "$line" "$exit_code" "$command_text" >&2

    # File database yang sedang dipindah selama merge harus kembali ke tempatnya.
    for file in "${SQLITE_PARKED[@]}"; do
        if [[ -f "$file.parked-$TIMESTAMP" && ! -e "$file" ]]; then
            mv "$file.parked-$TIMESTAMP" "$file" && printf '  - Database dikembalikan ke %s\n' "$file" >&2
        fi
    done
    if [[ "$IN_MAINTENANCE" == "1" ]]; then
        printf '  - Situs MASIH dalam mode maintenance supaya pengunjung tidak melihat halaman error.\n' >&2
        printf '    Perbaiki masalahnya lalu jalankan ulang: bash deploy.sh  (atau buka situs: php artisan up)\n' >&2
    fi
    if [[ -n "$DB_BACKUP_FILE" ]]; then
        printf '  - Backup database sebelum deploy: %s\n' "$DB_BACKUP_FILE" >&2
    fi
    if [[ -n "$PREVIOUS_COMMIT" ]]; then
        printf '  - Commit sebelum deploy: %s\n' "$PREVIOUS_COMMIT" >&2
        printf '    Kembali ke versi itu: git reset --hard %s && composer install --no-dev -o && npm ci && npm run build\n' "$PREVIOUS_COMMIT" >&2
    fi
}
trap 'on_error "$LINENO" "$BASH_COMMAND"' ERR

# Path absolut file SQLite sesuai .env (kosong bila bukan SQLite).
resolve_sqlite_path() {
    local connection database
    connection="$(env_get DB_CONNECTION)"
    [[ "${connection:-sqlite}" == "sqlite" ]] || return 0

    database="$(env_get DB_DATABASE)"
    database="${database:-database/database.sqlite}"
    [[ "$database" == /* ]] || database="$APP_DIR/$database"
    SQLITE_PATH="$database"
}

backup_database() {
    local connection
    connection="$(env_get DB_CONNECTION)"
    connection="${connection:-sqlite}"
    run mkdir -p "$BACKUP_DIR"

    case "$connection" in
        sqlite)
            if [[ ! -f "$SQLITE_PATH" ]]; then
                warn "File SQLite $SQLITE_PATH tidak ditemukan; backup dilewati."
                return 0
            fi
            DB_BACKUP_FILE="$BACKUP_DIR/db-$TIMESTAMP.sqlite"
            if command -v sqlite3 >/dev/null 2>&1; then
                # .backup aman walau database sedang dipakai.
                run sqlite3 "$SQLITE_PATH" ".backup '$DB_BACKUP_FILE'"
            else
                run cp -p "$SQLITE_PATH" "$DB_BACKUP_FILE"
            fi
            ;;
        mysql|mariadb)
            # Debian memakai MariaDB; nama mysqldump di sana sudah usang.
            local dump_command
            dump_command="$(command -v mariadb-dump || command -v mysqldump || true)"
            [[ -n "$dump_command" ]] || fail "mariadb-dump/mysqldump tidak ada. Pasang dengan: sudo apt install mariadb-client"
            DB_BACKUP_FILE="$BACKUP_DIR/db-$TIMESTAMP.sql.gz"
            if [[ "$DRY_RUN" == "1" ]]; then
                printf '    [dry-run] %s %s | gzip > %s\n' "$(basename "$dump_command")" "$(env_get DB_DATABASE)" "$DB_BACKUP_FILE"
            else
                # Kredensial lewat file sementara agar password tidak terlihat di daftar proses.
                local credentials
                credentials="$(mktemp)"
                chmod 600 "$credentials"
                {
                    printf '[client]\n'
                    printf 'user="%s"\n' "$(env_get DB_USERNAME | sed 's/["\\]/\\&/g')"
                    printf 'password="%s"\n' "$(env_get DB_PASSWORD | sed 's/["\\]/\\&/g')"
                    printf 'host="%s"\n' "$(env_get DB_HOST)"
                    [[ -n "$(env_get DB_PORT)" ]] && printf 'port=%s\n' "$(env_get DB_PORT)"
                } > "$credentials"
                printf '    $ %s %s | gzip > %s\n' "$(basename "$dump_command")" "$(env_get DB_DATABASE)" "$DB_BACKUP_FILE"
                if ! "$dump_command" --defaults-extra-file="$credentials" --single-transaction --quick --routines --no-tablespaces \
                    "$(env_get DB_DATABASE)" | gzip > "$DB_BACKUP_FILE"; then
                    rm -f "$credentials" "$DB_BACKUP_FILE"
                    fail "Backup MySQL gagal. Deploy dibatalkan sebelum ada perubahan."
                fi
                rm -f "$credentials"
            fi
            ;;
        pgsql)
            command -v pg_dump >/dev/null 2>&1 || fail "pg_dump tidak ada. Pasang postgresql-client atau backup manual dulu."
            DB_BACKUP_FILE="$BACKUP_DIR/db-$TIMESTAMP.sql.gz"
            if [[ "$DRY_RUN" == "1" ]]; then
                printf '    [dry-run] pg_dump %s | gzip > %s\n' "$(env_get DB_DATABASE)" "$DB_BACKUP_FILE"
            else
                printf '    $ pg_dump %s | gzip > %s\n' "$(env_get DB_DATABASE)" "$DB_BACKUP_FILE"
                PGPASSWORD="$(env_get DB_PASSWORD)" pg_dump -h "$(env_get DB_HOST)" -p "$(env_get DB_PORT)" \
                    -U "$(env_get DB_USERNAME)" "$(env_get DB_DATABASE)" | gzip > "$DB_BACKUP_FILE"
            fi
            ;;
        *)
            warn "Backup otomatis belum mendukung koneksi '$connection'. Pastikan Anda sudah backup manual."
            return 0
            ;;
    esac

    if [[ "$DRY_RUN" != "1" ]]; then
        chmod 600 "$DB_BACKUP_FILE"
        ok "Backup database: $DB_BACKUP_FILE ($(du -h "$DB_BACKUP_FILE" | cut -f1))"

        # Simpan hanya KEEP_BACKUPS backup terbaru.
        local old_backups
        old_backups="$(ls -1t "$BACKUP_DIR"/db-* 2>/dev/null | tail -n +"$((KEEP_BACKUPS + 1))" || true)"
        if [[ -n "$old_backups" ]]; then
            echo "$old_backups" | xargs rm -f
            ok "Backup lama dihapus; tersisa $KEEP_BACKUPS terbaru."
        fi
    fi
}

# Database SQLite dulu sempat ikut ter-commit (file `expertia`), lalu commit
# 54755e5 mengeluarkannya dari git. Merge yang melewati commit itu akan menolak
# berjalan (file berubah di server) atau menghapus file database server. Karena
# itu file database dan journal-nya dipindah sementara selama merge (situs sudah
# dalam maintenance) lalu dikembalikan.
park_sqlite_database() {
    [[ -n "$SQLITE_PATH" ]] || return 0

    local relative="${SQLITE_PATH#"$APP_DIR"/}" file
    git ls-files --error-unmatch "$relative" >/dev/null 2>&1 || return 0

    warn "Database SQLite ($relative) masih tercatat di git; dipindah sementara selama merge agar tidak terhapus."
    run git rm --cached --quiet --ignore-unmatch -- "$relative" "$relative-journal"
    for file in "$SQLITE_PATH" "$SQLITE_PATH-journal"; do
        if [[ -f "$file" ]]; then
            run mv "$file" "$file.parked-$TIMESTAMP"
            SQLITE_PARKED+=("$file")
        fi
    done
}

unpark_sqlite_database() {
    local file
    for file in "${SQLITE_PARKED[@]}"; do
        if [[ "$DRY_RUN" != "1" && -e "$file" ]]; then
            # Commit tujuan ternyata masih membawa file database: simpan versi git
            # di samping, pakai kembali data server.
            warn "Commit tujuan masih membawa $file; versi dari git disimpan sebagai $file.from-git-$TIMESTAMP."
            run mv "$file" "$file.from-git-$TIMESTAMP"
        fi
        run mv "$file.parked-$TIMESTAMP" "$file"
    done
    if [[ ${#SQLITE_PARKED[@]} -gt 0 ]]; then
        ok "Database SQLite dikembalikan utuh: $SQLITE_PATH"
    fi
    SQLITE_PARKED=()
}

frontend_changed() {
    [[ "$FORCE" == "1" ]] && return 0
    [[ -f "$APP_DIR/public/build/manifest.json" ]] || return 0
    ! git diff --quiet "$PREVIOUS_COMMIT" HEAD -- "${FRONTEND_PATHS[@]}"
}

# ---------------------------------------------------------------------------
# 1. Pemeriksaan awal (belum mengubah apa pun)
# ---------------------------------------------------------------------------

cd "$APP_DIR"
[[ "$DRY_RUN" == "1" ]] && info "DRY RUN: langkah hanya ditampilkan, tidak ada yang diubah."

info "Pemeriksaan awal ($APP_DIR)"

[[ -f artisan ]] || fail "File artisan tidak ada di $APP_DIR. Jalankan dari folder aplikasi atau set APP_DIR."
command -v git >/dev/null 2>&1 || fail "Perintah 'git' tidak ditemukan."
if ! git_error="$(git rev-parse --is-inside-work-tree 2>&1)"; then
    # Paling sering "dubious ownership": folder milik www-data, deploy dijalankan user lain.
    fail "Git menolak folder ini: $git_error
       Bila pesannya 'dubious ownership', jalankan: git config --global --add safe.directory $APP_DIR"
fi

required_commands=(git php composer)
[[ "$SKIP_BUILD" == "1" ]] || required_commands+=(node npm)
for command_name in "${required_commands[@]}"; do
    command -v "$command_name" >/dev/null 2>&1 || fail "Perintah '$command_name' tidak ditemukan."
done

php -r "exit(version_compare(PHP_VERSION, '$MIN_PHP', '>=') ? 0 : 1);" \
    || fail "Butuh PHP >= $MIN_PHP (terpasang $(php -r 'echo PHP_VERSION;'))."
ok "PHP $(php -r 'echo PHP_VERSION;')"

if [[ "$SKIP_BUILD" != "1" ]]; then
    # Vite 7 butuh Node 20.19+ (nodejs bawaan Debian 13 sudah 20.19).
    node_version="$(node -v 2>/dev/null | sed 's/^v//')"
    php -r "exit(version_compare('$node_version', '$MIN_NODE', '>=') ? 0 : 1);" \
        || fail "Butuh Node.js >= $MIN_NODE untuk build aset (terpasang ${node_version:-tidak ada}). Di Debian: sudo apt install nodejs npm"
    ok "Node.js $node_version"
fi

[[ -f .env ]] || fail "File .env tidak ada."
[[ -n "$(env_get APP_KEY)" ]] || fail "APP_KEY di .env kosong. Jangan membuat key baru bila sebelumnya ada (sesi dan data terenkripsi akan rusak); pulihkan .env yang benar."
ok ".env ditemukan, APP_KEY terisi"

[[ "$(env_get APP_ENV)" == "production" ]] || warn "APP_ENV=$(env_get APP_ENV) (seharusnya production)."
[[ "$(env_get APP_DEBUG)" == "false" ]] || warn "APP_DEBUG=$(env_get APP_DEBUG). Di produksi harus false agar detail error tidak tampil ke pengunjung."
[[ "$(env_get APP_URL)" == https://* ]] || warn "APP_URL=$(env_get APP_URL). Gunakan https://expertia.id agar canonical dan og:image benar."

for writable_dir in storage storage/framework/views storage/logs bootstrap/cache public; do
    [[ -w "$writable_dir" ]] || fail "Folder $writable_dir tidak bisa ditulis oleh user $(whoami). Perbaiki izin, misalnya: sudo chown -R $(whoami):www-data storage bootstrap/cache public && sudo chmod -R ug+rwX storage bootstrap/cache"
done
ok "Folder storage, bootstrap/cache, dan public bisa ditulis"

resolve_sqlite_path
sqlite_relative="${SQLITE_PATH#"$APP_DIR"/}"

# File yang diubah langsung di server akan membuat merge gagal di tengah jalan.
# Database SQLite dikecualikan karena ditangani khusus (lihat untrack_sqlite_database).
local_changes="$(git status --porcelain --untracked-files=no | grep -vE " (${sqlite_relative//./\\.}|${sqlite_relative//./\\.}-journal)\$" || true)"
if [[ -n "$local_changes" ]]; then
    printf '%s\n' "$local_changes" | sed 's/^/      /' >&2
    fail "Ada file aplikasi yang diubah langsung di server. Simpan dulu (git stash) atau kembalikan (git checkout -- <file>) sebelum deploy."
fi
ok "Tidak ada perubahan lokal yang bentrok"

# ---------------------------------------------------------------------------
# 2. Cek commit baru
# ---------------------------------------------------------------------------

info "Mengambil perubahan dari origin/$BRANCH"
git fetch --prune --quiet origin "$BRANCH"

PREVIOUS_COMMIT="$(git rev-parse HEAD)"
target_commit="$(git rev-parse "origin/$BRANCH")"

if [[ "$PREVIOUS_COMMIT" == "$target_commit" && "$FORCE" != "1" ]]; then
    ok "Sudah versi terbaru: $(git log -1 --format='%h %s')"
    ok "Tidak ada yang perlu di-deploy. Pakai FORCE=1 untuk tetap menjalankan semua langkah."
    exit 0
fi

git merge-base --is-ancestor HEAD "origin/$BRANCH" \
    || fail "Server punya commit yang tidak ada di origin/$BRANCH. Tangani manual (git log origin/$BRANCH..HEAD)."

printf '    Commit yang akan dipasang:\n'
git log --format='      %h %s (%an)' "HEAD..origin/$BRANCH" | head -n 30
if git diff --name-only HEAD "origin/$BRANCH" -- database/migrations | grep -q .; then
    warn "Ada migrasi database baru; akan dijalankan setelah backup."
fi

# ---------------------------------------------------------------------------
# 3. Backup database
# ---------------------------------------------------------------------------

info "Backup database"
backup_database

# ---------------------------------------------------------------------------
# 4. Pasang kode baru (situs dalam maintenance)
# ---------------------------------------------------------------------------

info "Mode maintenance"
run php artisan down --retry=60 --refresh=15
[[ "$DRY_RUN" == "1" ]] || IN_MAINTENANCE=1

info "Memasang commit $(git rev-parse --short "origin/$BRANCH")"
park_sqlite_database
run git merge --ff-only --quiet "origin/$BRANCH"
unpark_sqlite_database

# Cache lama bisa merujuk kelas yang sudah tidak ada; hapus filenya langsung
# (tanpa artisan) sebelum composer menjalankan package:discover.
run rm -f bootstrap/cache/config.php bootstrap/cache/events.php bootstrap/cache/routes-v7.php

info "Dependensi PHP"
run composer install --no-dev --optimize-autoloader --no-interaction --no-progress

info "Aset frontend"
if [[ "$SKIP_BUILD" == "1" ]]; then
    warn "SKIP_BUILD=1: build aset dilewati."
elif [[ "$DRY_RUN" != "1" ]] && ! frontend_changed; then
    ok "Tidak ada perubahan frontend dan public/build sudah ada; build dilewati."
else
    run npm ci --no-audit --no-fund
    run npm run build
fi

info "Migrasi database"
run php artisan migrate --force

info "Cache aplikasi"
[[ -e public/storage ]] || run php artisan storage:link
run php artisan permission:cache-reset
run php artisan config:cache
run php artisan route:cache
run php artisan view:cache
run php artisan event:cache

if [[ "$RELOAD_FPM" == "1" ]]; then
    info "Reload $FPM_SERVICE (mengosongkan OPcache)"
    run sudo systemctl reload "$FPM_SERVICE"
fi

info "Membuka situs"
run php artisan up
IN_MAINTENANCE=0

# ---------------------------------------------------------------------------
# 5. Pemeriksaan setelah deploy (hanya peringatan)
# ---------------------------------------------------------------------------

info "Pemeriksaan setelah deploy"

if [[ "$DRY_RUN" != "1" ]]; then
    app_url="$(env_get APP_URL)"
    if [[ -n "$app_url" ]] && command -v curl >/dev/null 2>&1; then
        if curl -fsS --max-time 15 -o /dev/null "$app_url/up"; then
            ok "Health check $app_url/up berhasil"
        else
            warn "Health check $app_url/up gagal. Buka situs di browser dan cek storage/logs/laravel.log."
        fi
    fi
fi

if ! command -v crontab >/dev/null 2>&1; then
    warn "Paket cron belum terpasang, jadi artikel terjadwal tidak akan tayang. Pasang: sudo apt install cron"
    warn "lalu 'crontab -e' dan tambahkan: * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
elif (crontab -l 2>/dev/null; cat /etc/crontab /etc/cron.d/* 2>/dev/null) | grep -q 'schedule:run'; then
    ok "Cron scheduler terpasang"
else
    warn "Cron scheduler tidak ditemukan. Tanpa ini artikel terjadwal tidak tayang. Pasang dengan 'crontab -e':"
    warn "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
fi

info "Selesai dalam $(( $(date +%s) - STARTED_AT )) detik"
ok "Sebelumnya: $(git log -1 --format='%h %s' "$PREVIOUS_COMMIT")"
ok "Sekarang:   $(git log -1 --format='%h %s' "$( [[ "$DRY_RUN" == "1" ]] && echo "origin/$BRANCH" || echo HEAD )")"
[[ -z "$DB_BACKUP_FILE" ]] || ok "Backup:     $DB_BACKUP_FILE"
