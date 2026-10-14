<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_topics', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->string('group_code', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_menu_visible')->default(true);
            $table->timestamps();
        });
        Schema::create('category_topic_links', function (Blueprint $table) {
            $table->foreignId('topic_id')->constrained('category_topics')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->primary(['topic_id', 'category_id']);
            $table->index(['category_id', 'topic_id']);
        });
        Schema::create('category_topic_contents', function (Blueprint $table) {
            $table->foreignId('topic_id')->constrained('category_topics')->restrictOnDelete();
            $table->string('content_type', 191);
            $table->unsignedBigInteger('content_id');
            $table->primary(['topic_id', 'content_type', 'content_id'], 'category_topic_contents_pk');
            $table->index(['content_type', 'content_id'], 'category_topic_contents_content_idx');
        });
        Schema::create('category_domain_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id');
            $table->string('domain', 40);
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->primary(['legacy_id', 'domain']);
            $table->unique('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_domain_mappings');
        Schema::dropIfExists('category_topic_contents');
        Schema::dropIfExists('category_topic_links');
        Schema::dropIfExists('category_topics');
    }
};
