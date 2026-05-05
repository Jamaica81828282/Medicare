<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'expiring_30'    => DB::table('product_batches')
                                    ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                                    ->count(),

            'low_stock'      => DB::table('products')
                                    ->whereColumn('stock_quantity', '<=', 'reorder_level')
                                    ->count(),

            'total_products' => DB::table('products')
                                    ->where('is_active', 1)
                                    ->count(),

            'today_revenue'  => DB::table('invoices')
                                    ->whereDate('invoice_date', today())
                                    ->where('status', 'paid')
                                    ->sum('grand_total'),

            'total_invoices' => DB::table('invoices')
                                    ->where('status', 'paid')
                                    ->count(),

            'month_revenue'  => DB::table('invoices')
                                    ->whereMonth('invoice_date', now()->month)
                                    ->whereYear('invoice_date', now()->year)
                                    ->where('status', 'paid')
                                    ->sum('grand_total'),

            'total_suppliers' => DB::table('suppliers')
                                    ->where('is_active', 1)
                                    ->count(),

            'total_cashiers' => DB::table('users')
                                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                    ->where('roles.name', 'cashier')
                                    ->count(),
        ];

        $categories = DB::table('product_categories')->orderBy('category_name')->get();
        $suppliers  = DB::table('suppliers')->where('is_active', 1)->orderBy('supplier_name')->get();
        $tax_rates  = DB::table('tax_rates')->get();

        return view('admin.dashboard', compact('stats', 'categories', 'suppliers', 'tax_rates'));
    }
}