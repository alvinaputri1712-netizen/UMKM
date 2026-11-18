<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->string('position');
                $table->string('education')->nullable();
                $table->string('category')->nullable();
                $table->text('summary')->nullable();
                $table->string('image_path')->nullable();
                $table->string('status', 20)->default('published');
                $table->integer('sort_order')->default(0);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique('slug');
                $table->index(['status', 'published_at', 'sort_order']);
                $table->index('name');
            });

            return;
        }

        Schema::table('teachers', function (Blueprint $table): void {
            if (! Schema::hasIndex('teachers', 'teachers_name_index')) {
                $table->index('name');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
