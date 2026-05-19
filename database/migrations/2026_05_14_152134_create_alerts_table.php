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
        if (!Schema::hasTable('alerts')) {
            Schema::create('alerts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50); // e.g., 'low_stock', 'expiring_batch'
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedInteger('batch_id')->nullable();
                $table->unsignedInteger('created_by'); // cashier who created the alert
                $table->text('message');
                $table->enum('status', ['active', 'resolved', 'dismissed'])->default('active');
                $table->unsignedInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                // Foreign keys removed for now to avoid issues
                // $table->foreign('product_id')->references('id')->on('products');
                // $table->foreign('batch_id')->references('id')->on('product_batches');
                // $table->foreign('created_by')->references('id')->on('users');
                // $table->foreign('resolved_by')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
