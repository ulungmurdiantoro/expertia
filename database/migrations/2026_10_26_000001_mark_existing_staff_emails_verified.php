<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email kini wajib diverifikasi (User implements MustVerifyEmail), dan semua dashboard
 * memakai middleware 'verified'. Akun redaksi dan kontributor yang sudah ada ditandai
 * terverifikasi agar tidak terkunci saat deploy; akun pembaca lama diminta memverifikasi
 * emailnya pada interaksi berikutnya.
 */
return new class extends Migration
{
    private const READER_ROLES = ['user', 'subscriber', 'partner-manager'];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable('users') || ! Schema::hasTable($tables['model_has_roles'])) {
            return;
        }

        $roleKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $modelKey = config('permission.column_names.model_morph_key') ?? 'model_id';

        $staffIds = DB::table($tables['model_has_roles'])
            ->join($tables['roles'], $tables['roles'].'.id', '=', $tables['model_has_roles'].'.'.$roleKey)
            ->where($tables['model_has_roles'].'.model_type', 'App\\Models\\User')
            ->whereNotIn($tables['roles'].'.name', self::READER_ROLES)
            ->pluck($tables['model_has_roles'].'.'.$modelKey);

        $authorIds = Schema::hasTable('articles') ? DB::table('articles')->distinct()->pluck('user_id') : collect();

        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereIn('id', $staffIds->merge($authorIds)->unique()->values())
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Tidak dibatalkan: tanda terverifikasi tidak bisa dibedakan dari verifikasi asli.
    }
};
