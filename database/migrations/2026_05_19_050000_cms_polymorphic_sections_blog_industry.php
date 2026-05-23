<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CMS expansion: polymorphic sections + Blog + Industry domain models.
 *
 * Drives the new 12-component page-builder system. After this migration
 * runs, sections can attach to any model (Page, Solution, Blog, Industry,
 * DeviceType) via morphMany. The legacy `page_id` columns on
 * page_sections / solutions / device_types are removed.
 *
 * HARD CUT: legacy page_sections rows are truncated. The CMS is re-seeded
 * from scratch by HomePageSeeder + PublicPageSeeder + BlogSeeder +
 * IndustrySeeder, which use the new schema. Any manual /admin edits to
 * the existing seeded pages will be lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Truncate legacy page_sections rows. The structure changes
        //    below and seeders re-create everything with the new schema.
        DB::table('page_sections')->delete();

        // 2. Drop legacy page_id FKs that wrapped owning models in a Page row.
        Schema::table('solutions', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropColumn('page_id');
        });

        Schema::table('device_types', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropColumn('page_id');
        });

        // 3. Switch page_sections to polymorphic ownership.
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropIndex(['page_id', 'is_active', 'sort_order']);
            $table->dropColumn('page_id');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->morphs('sectionable');
            $table->index(['sectionable_type', 'sectionable_id', 'is_active', 'sort_order'], 'page_sections_morph_active_order_idx');
        });

        // 4. Blogs.
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
            $table->index(['is_featured', 'sort_order']);
        });

        // 5. Blog tags + pivot.
        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_blog_tag', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_id', 'blog_tag_id']);
        });

        // 6. Industries.
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('icon_name')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_featured', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industries');
        Schema::dropIfExists('blog_blog_tag');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blogs');

        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropIndex('page_sections_morph_active_order_idx');
            $table->dropMorphs('sectionable');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->index(['page_id', 'is_active', 'sort_order']);
        });

        Schema::table('device_types', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
        });

        Schema::table('solutions', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
        });
    }
};
