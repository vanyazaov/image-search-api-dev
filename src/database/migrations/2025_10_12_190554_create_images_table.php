<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_images_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'brand']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
};
