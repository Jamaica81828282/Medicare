<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('queue_number', 10);
            $table->unsignedInteger('invoice_id')->nullable();            $table->string('customer_name')->nullable();
            $table->enum('status', ['waiting', 'paid', 'serving', 'done', 'skipped'])->default('waiting');
            $table->date('queue_date');
            $table->unsignedSmallInteger('daily_sequence');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->unique(['queue_date', 'queue_number']);

            // Add foreign key only if invoices table uses standard bigIncrements
         });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};