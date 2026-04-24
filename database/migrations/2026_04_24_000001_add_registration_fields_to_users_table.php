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
            $table->string('position')->nullable()->after('institution');
            $table->string('gender', 16)->nullable()->after('position');
            $table->string('whatsapp_number')->nullable()->after('gender');
            $table->string('institution_unit')->nullable()->after('whatsapp_number');
            $table->string('linkedin_url')->nullable()->after('institution_unit');
            $table->json('expertise_areas')->nullable()->after('linkedin_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'position',
                'gender',
                'whatsapp_number',
                'institution_unit',
                'linkedin_url',
                'expertise_areas',
            ]);
        });
    }
};
