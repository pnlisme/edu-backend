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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            // old
            // $table->string('code')->unique();
            // $table->string('description')->nullable();
            // $table->tinyInteger('discount_type');
            // $table->double('discount_value');
            // $table->date('start_date')->nullable();
            // $table->date('end_date')->nullable();
            // $table->integer('remain_quantity')->nullable();

            // new
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->enum('discount_type', ['fixed', 'percent']); // loại giảm giá: cố định hoặc theo %
            $table->decimal('discount_value', 8, 2); // giá trị giảm giá
            $table->integer('usage_limit')->nullable(); // số lần có thể sử dụng
            $table->integer('usage_count')->default(0); // số lần đã sử dụng
            $table->date('expires_at')->nullable(); // ngày hết hạn
            $table->double('min_order_value')->nullable();
            $table->double('max_discount_value')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('deleted_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
