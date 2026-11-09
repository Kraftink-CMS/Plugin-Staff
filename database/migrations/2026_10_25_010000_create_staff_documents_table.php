<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createDocuments();
        $this->createDirectory();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff__member_roles');
        Schema::dropIfExists('staff__roles');
        Schema::dropIfExists('staff__members');
        Schema::dropIfExists('staff__document_revisions');
        Schema::dropIfExists('staff__documents');
    }

    private function createDocuments(): void
    {
        Schema::create('staff__documents', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 12);
            $table->string('slug', 180);
            $table->string('title', 180);
            $table->longText('body');
            $table->string('category', 120)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('permission', 100)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'published', 'position']);
            $table->index(['deleted_at', 'id'], 'staff_documents_trash_index');
            $table->index(['published', 'deleted_at', 'updated_at'], 'staff_documents_retention_index');
        });
        Schema::create('staff__document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('staff__documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('snapshot');
            $table->timestamp('created_at');
            $table->index(['document_id', 'created_at', 'id'], 'staff_revisions_retention_index');
        });
    }

    private function createDirectory(): void
    {
        Schema::create('staff__members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 12);
            $table->string('name', 120);
            $table->string('title', 120);
            $table->text('biography')->nullable();
            $table->string('avatar', 2000)->nullable();
            $table->string('website', 2000)->nullable();
            $table->json('social_links')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->index(['locale', 'published', 'position']);
        });
        Schema::create('staff__roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('locale', 12);
            $table->string('title', 120);
            $table->string('color', 7)->default('#6c757d');
            $table->unsignedInteger('position')->default(0);
            $table->unique(['role_id', 'locale']);
            $table->index(['locale', 'position']);
        });
        Schema::create('staff__member_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('staff__members')->cascadeOnDelete();
            $table->foreignId('role_listing_id')->constrained('staff__roles')->cascadeOnDelete();
            $table->unique(['member_id', 'role_listing_id']);
            $table->index('role_listing_id');
        });
    }
};
