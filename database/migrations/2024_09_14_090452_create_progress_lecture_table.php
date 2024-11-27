<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('progress_lecture', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lecture_id');
            $table->boolean('done')->default(false);
            $table->softDeletes();
            $table->boolean('is_deleted')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('inactive');

            $table->timestamps();

            // Primary key
            $table->primary(['user_id', 'course_id', 'lecture_id']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('lecture_id')->references('id')->on('lectures')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_lecture');
    }
};
