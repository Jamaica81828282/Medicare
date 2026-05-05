<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run: php artisan migrate
     * This adds the image_base64 column to the existing products table.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only add if it doesn't already exist
            if (!Schema::hasColumn('products', 'image_base64')) {
                $table->longText('image_base64')->nullable()->after('description')
                      ->comment('Base64 data URI of product image, e.g. data:image/png;base64,...');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_base64');
        });
    }
};