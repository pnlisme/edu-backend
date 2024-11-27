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
        Schema::create('answers_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('question_id');
            $table->text('content');
            $table->softDeletes();
            $table->boolean('is_deleted')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('inactive');

            $table->timestamps();

            // Primary key
            $table->primary(['user_id', 'question_id']);

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers_users');
    }
};
