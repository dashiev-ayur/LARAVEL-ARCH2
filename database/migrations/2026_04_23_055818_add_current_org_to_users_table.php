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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'current_org_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_org_id')->nullable()->constrained('orgs')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'current_org_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['current_org_id']);
                $table->dropColumn('current_org_id');
            });
        }
    }
};
