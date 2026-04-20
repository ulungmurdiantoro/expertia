<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('profile_slug')->nullable()->unique()->after('username');
            $table->string('avatar')->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('website')->nullable()->after('bio');
            $table->string('institution')->nullable()->after('website');
            $table->json('social_links')->nullable()->after('institution');
            $table->string('status')->default('active')->after('social_links');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'public_id',
                'username',
                'profile_slug',
                'avatar',
                'bio',
                'website',
                'institution',
                'social_links',
                'status',
            ]);
        });
    }
};
