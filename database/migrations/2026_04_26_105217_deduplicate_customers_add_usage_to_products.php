<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add usage_recommendation to products ──────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->text('usage_recommendation')->nullable()->after('description');
        });

        // ── 2. Deduplicate customers — keep the oldest record per phone ──
        // For each duplicated phone, keep the lowest id (oldest), 
        // reassign all invoices/orders to it, then delete the duplicates.

        $dupes = DB::table('customers')
            ->select('phone', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('phone')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($dupes as $dupe) {
            // Get all IDs for this phone except the one we're keeping
            $removeIds = DB::table('customers')
                ->where('phone', $dupe->phone)
                ->where('id', '!=', $dupe->keep_id)
                ->pluck('id');

            // Reassign any invoices/orders linked to duplicate IDs → keep_id
            // Adjust table/column names to match your schema
            DB::table('invoices')
                ->whereIn('customer_id', $removeIds)
                ->update(['customer_id' => $dupe->keep_id]);

            // Delete the duplicates
            DB::table('customers')
                ->whereIn('id', $removeIds)
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('usage_recommendation');
        });
        // Note: deduplication cannot be automatically reversed
    }
};