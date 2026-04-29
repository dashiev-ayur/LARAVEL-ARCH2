<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('title');
            $table->index(['org_id', 'type', 'parent_id', 'sort_order', 'id']);
        });

        $positionsByGroup = [];

        DB::table('categories')
            ->select(['id', 'org_id', 'type', 'parent_id'])
            ->orderBy('org_id')
            ->orderBy('type')
            ->orderBy('parent_id')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->each(function (object $category) use (&$positionsByGroup): void {
                $groupKey = implode(':', [
                    $category->org_id,
                    $category->type,
                    $category->parent_id ?? 'root',
                ]);

                $sortOrder = $positionsByGroup[$groupKey] ?? 0;
                $positionsByGroup[$groupKey] = $sortOrder + 1;

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['sort_order' => $sortOrder]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['org_id', 'type', 'parent_id', 'sort_order', 'id']);
            $table->dropColumn('sort_order');
        });
    }
};
