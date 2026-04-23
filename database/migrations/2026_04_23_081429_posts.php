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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->string('type');
            $table->string('status');
            $table->string('acl_resource')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'type', 'slug']);
            $table->index(['org_id', 'type', 'status', 'published_at']);
            $table->index('acl_resource');
            $table->index('parent_id');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type');
            $table->string('acl_resource')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->timestamps();

            $table->unique(['org_id', 'type', 'slug']);
            $table->index('acl_resource');
            $table->index('parent_id');
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->integer('position')->nullable();

            $table->primary(['post_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('posts');
    }
};
