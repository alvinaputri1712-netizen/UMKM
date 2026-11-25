<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers') || Schema::hasColumn('teachers', 'employment_status')) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('employment_status', 20)->default('active')->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teachers') || ! Schema::hasColumn('teachers', 'employment_status')) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn('employment_status');
        });
    }
};
