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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('level_id');
            $table->unsignedBigInteger('language_id');
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('thumbnail');
            $table->double('price')->nullable();
            $table->enum('type_sale', ['percent', 'price'])->default('price')->nullable();
            $table->double('sale_value')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->bigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by');
            $table->bigInteger('updated_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('course_levels')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
