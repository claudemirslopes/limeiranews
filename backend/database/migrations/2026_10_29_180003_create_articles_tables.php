<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('cover_url', 1000)->nullable(); // external/demo cover image
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('primary_category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('status', 30)->default('draft'); // draft|in_review|revision_requested|approved|scheduled|published|archived
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_breaking')->default(false);
            $table->boolean('is_sponsored')->default(false);
            $table->string('source_name')->nullable();
            $table->string('image_credit')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url', 1000)->nullable();
            $table->string('og_image_path')->nullable();
            $table->boolean('allow_index')->default(true);
            $table->unsignedBigInteger('view_count')->default(0); // internal only
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->index(['status', 'published_at']);
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'category_id']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('role', 20)->default('gallery'); // gallery|inline
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('status', 30);
            $table->json('snapshot')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path', 1000);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();
        });

        Schema::create('breaking_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('headline');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('position', 30)->default('top');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaking_news');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_media');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('articles');
    }
};
