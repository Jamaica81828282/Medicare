<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
  public function index()
{
    $today = Carbon::today();

    // Last 7 days daily sales for chart
$weeklySales = collect(range(6, 0))->map(function ($daysAgo) {
    $date = Carbon::today()->subDays($daysAgo);
    return [
        'date'  => $date->format('D'),
        'total' => (float) DB::table('invoices')
            ->where('status', 'paid')
            ->whereDate('invoice_date', $date)
            ->sum('grand_total'),
        'count' => (int) DB::table('invoices')
            ->where('status', 'paid')
            ->whereDate('invoice_date', $date)
            ->count(),
    ];
});

    // ── LOW STOCK ──────────────────────────────────────────────────
    $lowStockProducts = DB::table('products')
        ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
        ->where('products.is_active', 1)
        ->whereRaw('products.stock_quantity <= products.reorder_level')
        ->select(
            'products.*',
            'product_categories.category_name'
        )
        ->orderByRaw('products.stock_quantity / NULLIF(products.reorder_level, 0) ASC')
        ->get()
        ->map(function ($p) {
            // wrap category into an object the blade expects
            $p->category = (object)['category_name' => $p->category_name ?? '—'];
            return $p;
        });

    // ── SALES SUMMARY ──────────────────────────────────────────────
    $salesSummary = [
        'today_total'    => DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$today)->sum('grand_total'),
        'today_count'    => DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$today)->count(),
        'today_discount' => DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$today)->sum('total_discount'),
        'today_tax'      => DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$today)->sum('total_tax'),
        'week_total'     => DB::table('invoices')->where('status','paid')->whereBetween('invoice_date',[Carbon::now()->startOfWeek(), Carbon::now()])->sum('grand_total'),
        'week_count'     => DB::table('invoices')->where('status','paid')->whereBetween('invoice_date',[Carbon::now()->startOfWeek(), Carbon::now()])->count(),
        'month_total'    => DB::table('invoices')->where('status','paid')->whereMonth('invoice_date',Carbon::now()->month)->whereYear('invoice_date',Carbon::now()->year)->sum('grand_total'),
        'month_count'    => DB::table('invoices')->where('status','paid')->whereMonth('invoice_date',Carbon::now()->month)->whereYear('invoice_date',Carbon::now()->year)->count(),
        'by_payment'     => DB::table('invoices')
                                ->join('payment_methods','payment_methods.id','=','invoices.payment_method_id')
                                ->where('invoices.status','paid')
                                ->whereDate('invoices.invoice_date',$today)
                                ->select(DB::raw('payment_methods.method_name, SUM(invoices.grand_total) as total, COUNT(*) as count'))
                                ->groupBy('payment_methods.method_name')
                                ->get(),
    ];

    // ── SHIFT REPORT ───────────────────────────────────────────────
    $shiftReport = [
        'cashier_name'       => Auth::user()->name,
        'shift_start' => session('shift_started_at'),
        'total_sales'        => DB::table('invoices')->where('status','paid')->where('cashier_id',Auth::id())->whereDate('invoice_date',$today)->sum('grand_total'),
        'total_transactions' => DB::table('invoices')->where('status','paid')->where('cashier_id',Auth::id())->whereDate('invoice_date',$today)->count(),
        'total_discount'     => DB::table('invoices')->where('status','paid')->where('cashier_id',Auth::id())->whereDate('invoice_date',$today)->sum('total_discount'),
        'total_tax'          => DB::table('invoices')->where('status','paid')->where('cashier_id',Auth::id())->whereDate('invoice_date',$today)->sum('total_tax'),
        'voided_count'       => DB::table('invoices')->where('status','voided')->where('cashier_id',Auth::id())->whereDate('invoice_date',$today)->count(),
        'top_products'       => DB::table('invoice_items')
                                    ->join('invoices','invoices.id','=','invoice_items.invoice_id')
                                    ->where('invoices.status','paid')
                                    ->where('invoices.cashier_id',Auth::id())
                                    ->whereDate('invoices.invoice_date',$today)
                                    ->select(DB::raw('invoice_items.product_name, SUM(invoice_items.quantity) as qty_sold'))
                                    ->groupBy('invoice_items.product_name')
                                    ->orderByDesc('qty_sold')
                                    ->limit(5)
                                    ->get(),
    ];




// ── EXPIRING SOON ──────────────────────────────────────────────
$expiringProducts = DB::table('product_batches')
    ->join('products', 'products.id', '=', 'product_batches.product_id')
    ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
    ->leftJoin('suppliers', 'suppliers.id', '=', 'product_batches.supplier_id')
    ->where('product_batches.is_active', 1)
    ->where('products.is_active', 1)
    ->where('product_batches.quantity', '>', 0)
    ->where('product_batches.expiry_date', '<=', Carbon::now()->addDays(30))
    ->select(
        'product_batches.id as batch_id',
        'product_batches.batch_number',
        'product_batches.lot_number',
        'product_batches.expiry_date',
        'product_batches.quantity as batch_qty',
        'product_batches.received_date',
        'products.id as product_id',
        'products.sku',
        'products.product_name',
        'products.generic_name',
        'product_categories.category_name',
        'suppliers.supplier_name'
    )
    ->orderBy('product_batches.expiry_date', 'asc')
    ->get();

// ── STATS (built AFTER the queries above) ─────────────────────
$stats = [
    'pending'       => DB::table('invoices')->where('status', 'draft')->count(),
    'today_sales'   => $salesSummary['today_total'],
    'today_count'   => $salesSummary['today_count'],
    'today_rx'      => DB::table('invoices')->where('status', 'paid')->whereDate('invoice_date', $today)->whereNotNull('prescription_no')->count(),
    'low_stock'     => $lowStockProducts->count(),
    'expiring_soon' => $expiringProducts->count(),   // ← now works because $expiringProducts exists
];

    $pendingOrders = DB::table('invoices')
        ->leftJoin('customers','invoices.customer_id','=','customers.id')
        ->where('invoices.status','draft')
        ->select('invoices.*', DB::raw("CONCAT(customers.first_name,' ',customers.last_name) as customer_name"), 'customers.phone','customers.is_senior','customers.is_pwd','customers.id_number')
        ->orderBy('invoices.created_at','asc')
        ->get()
        ->map(function ($inv) {
            $inv->items = DB::table('invoice_items')->where('invoice_id',$inv->id)->get();
            return $inv;
        });

   $recentPaid = DB::table('invoices')
    ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
    ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
    ->where('invoices.status', 'paid')
    ->whereDate('invoices.invoice_date', $today)
    ->select(
        'invoices.*',
        DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as customer_name"),
        'payment_methods.method_name as payment_method_name',
        DB::raw("(SELECT COUNT(*) FROM invoice_items WHERE invoice_items.invoice_id = invoices.id) as items_count")
    )
    ->orderBy('invoices.updated_at', 'desc')
    ->limit(10)
    ->get();

    $paymentMethods = DB::table('payment_methods')->where('is_active',1)->get();
    $discountTypes  = DB::table('discount_types')->where('is_active',1)->get();
    $weeklySales = collect(range(6, 0))->map(function ($daysAgo) {
    $date = Carbon::today()->subDays($daysAgo);
    return [
        'date'  => $date->format('D'),
        'total' => (float) DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$date)->sum('grand_total'),
        'count' => (int)   DB::table('invoices')->where('status','paid')->whereDate('invoice_date',$date)->count(),
    ];
});

   return view('cashier.dashboard', compact(
    'stats','pendingOrders','recentPaid','paymentMethods','discountTypes',
    'lowStockProducts','salesSummary','shiftReport','weeklySales','expiringProducts'
));
}

// ── SEARCH INVOICE (AJAX) ─────────────────────────────────────────────
public function searchInvoices(Request $request)
{
    $q = trim($request->get('q',''));
    if (strlen($q) < 2) return response()->json([]);

    $results = DB::table('v_invoice_summary')
        ->where(function($query) use ($q) {
            $query->where('invoice_number','LIKE',"%{$q}%")
                  ->orWhere('customer_name','LIKE',"%{$q}%");
        })
        ->orderBy('invoice_date','desc')
        ->limit(20)
        ->get();

    return response()->json($results);
}

    public function getInvoice($id)
    {
        $invoice = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
            ->where('invoices.id', $id)
            ->select(
                'invoices.*',
                DB::raw("CONCAT(COALESCE(customers.first_name,''), ' ', COALESCE(customers.last_name,'')) as customer_name"),
                'customers.phone',
                'customers.address',
                'customers.is_senior',
                'customers.is_pwd',
                'customers.id_number',
                'payment_methods.method_name as payment_method_name'
            )
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice->items     = DB::table('invoice_items')->where('invoice_id', $id)->get();
        $invoice->discounts = DB::table('invoice_discounts')->where('invoice_id', $id)->get();

        return response()->json($invoice);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'invoice_id'        => 'required|integer|exists:invoices,id',
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'amount_tendered'   => 'required|numeric|min:0',
            'prescription_no'   => 'nullable|string|max:100',
            'discount_type_id'  => 'nullable|integer|exists:discount_types,id',
        ]);

        $invoice = DB::table('invoices')->where('id', $request->invoice_id)->first();

        if (!$invoice || $invoice->status !== 'draft') {
            return response()->json(['error' => 'Invoice is not in a payable state.'], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal     = (float) $invoice->subtotal;
            $discountAmt  = 0;
            $discountType = null;
            $newTax       = (float) $invoice->total_tax;
            $grandTotal   = (float) $invoice->grand_total;

            if ($request->discount_type_id) {
                $discountType = DB::table('discount_types')->find($request->discount_type_id);
                if ($discountType) {
                    if (in_array($discountType->discount_code, ['SENIOR20', 'PWD20'])) {
                        $vatExemptBase = $subtotal / 1.12;
                        $discountAmt   = $vatExemptBase * ($discountType->discount_value / 100);
                        $grandTotal    = $vatExemptBase - $discountAmt;
                        $newTax        = 0;
                    } elseif ($discountType->discount_method === 'percentage') {
                        $discountAmt = $grandTotal * ($discountType->discount_value / 100);
                        $grandTotal  = $grandTotal - $discountAmt;
                        $newTax      = $grandTotal * 0.12 / 1.12;
                    } else {
                        $discountAmt = (float) $discountType->discount_value;
                        $grandTotal  = $grandTotal - $discountAmt;
                        $newTax      = $grandTotal * 0.12 / 1.12;
                    }
                }
            }

            $changeAmount = (float) $request->amount_tendered - $grandTotal;

            DB::table('invoices')->where('id', $invoice->id)->update([
                'status'            => 'paid',
                'payment_method_id' => $request->payment_method_id,
                'payment_ref'       => $request->payment_ref ?? null,
                'prescription_no'   => $request->prescription_no ?? null,
                'total_discount'    => $discountAmt,
                'total_tax'         => $newTax,
                'grand_total'       => $grandTotal,
                'amount_tendered'   => $request->amount_tendered,
                'change_amount'     => max(0, $changeAmount),
                'cashier_id'        => Auth::id(),
                'updated_at'        => now(),
            ]);

            if ($discountType && $discountAmt > 0) {
                DB::table('invoice_discounts')->where('invoice_id', $invoice->id)->delete();
                DB::table('invoice_discounts')->insert([
                    'invoice_id'       => $invoice->id,
                    'discount_type_id' => $discountType->id,
                    'discount_code'    => $discountType->discount_code,
                    'discount_name'    => $discountType->discount_name,
                    'discount_method'  => $discountType->discount_method,
                    'discount_value'   => $discountType->discount_value,
                    'discount_amount'  => $discountAmt,
                    'id_number_used'   => $request->id_number_used ?? null,
                    'applied_by'       => Auth::id(),
                ]);
            }

            foreach (DB::table('invoice_items')->where('invoice_id', $invoice->id)->get() as $item) {
                DB::table('products')->where('id', $item->product_id)->decrement('stock_quantity', $item->quantity);
            }

            // Advance queue ticket to 'paid' so it appears in pickup queue
            $ticket = QueueTicket::where('invoice_id', $invoice->id)->first();

            if (!$ticket) {
                $next = QueueTicket::nextForToday();

                $customerName = null;
                if ($invoice->customer_id) {
                    $customerName = DB::table('customers')
                        ->where('id', $invoice->customer_id)
                        ->selectRaw("TRIM(CONCAT(first_name, ' ', last_name)) as full_name")
                        ->value('full_name');
                }

                $ticket = QueueTicket::create([
                    'invoice_id'     => $invoice->id,
                    'queue_number'   => $next['queue_number'],
                    'queue_date'     => $next['queue_date'],
                    'daily_sequence' => $next['sequence'],
                    'customer_name'  => $customerName ?: 'Walk-in Customer',
                    'status'         => 'paid',
                ]);
            } elseif (in_array($ticket->status, ['waiting', 'skipped'])) {
                $ticket->update(['status' => 'paid']);
            }

            DB::commit();

            return response()->json([
                'success'          => true,
                'invoice_number'   => $invoice->invoice_number,
                'grand_total'      => $grandTotal,
                'change_amount'    => max(0, $changeAmount),
                'invoice_id'       => $invoice->id,
                'queue_number'     => $ticket?->queue_number,
                'queue_ticket_id'  => $ticket?->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }

    public function voidInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'reason'     => 'required|string|max:255',
        ]);

        $invoice = DB::table('invoices')->where('id', $request->invoice_id)->first();

        if (!$invoice || !in_array($invoice->status, ['draft', 'issued'])) {
            return response()->json(['error' => 'Only pending orders can be voided.'], 422);
        }

        DB::table('invoices')->where('id', $request->invoice_id)->update([
            'status'        => 'voided',
            'voided_reason' => $request->reason,
            'voided_at'     => now(),
            'updated_at'    => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function printInvoice($id)
    {
        $invoice = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
            ->leftJoin('users', 'invoices.cashier_id', '=', 'users.id')
            ->where('invoices.id', $id)
            ->select(
                'invoices.*',
                DB::raw("CONCAT(COALESCE(customers.first_name,''), ' ', COALESCE(customers.last_name,'')) as customer_name"),
                'customers.phone as customer_phone',
                'customers.address as customer_address',
                'customers.is_senior',
                'customers.is_pwd',
                'customers.id_number',
                'payment_methods.method_name as payment_method_name',
                'users.name as cashier_name'
            )
            ->first();

        abort_if(!$invoice, 404);

        $invoice->items     = DB::table('invoice_items')->where('invoice_id', $id)->get();
        $invoice->discounts = DB::table('invoice_discounts')->where('invoice_id', $id)->get();
        $settings           = DB::table('company_settings')->pluck('setting_value', 'setting_key');

        return view('cashier.invoice-print', compact('invoice', 'settings'));
    }

    public function productLookup(Request $request)
    {
        $q = $request->query('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = \App\Models\Product::where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('product_name', 'like', "%{$q}%")
                      ->orWhere('generic_name', 'like', "%{$q}%")
                      ->orWhere('brand', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->with('category')
            ->select('id', 'sku', 'product_name', 'generic_name', 'brand', 'selling_price', 'stock_quantity', 'reorder_level', 'requires_rx', 'category_id')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id'             => $p->id,
                    'sku'            => $p->sku,
                    'product_name'   => $p->product_name,
                    'generic_name'   => $p->generic_name,
                    'brand'          => $p->brand,
                    'selling_price'  => $p->selling_price,
                    'stock_quantity' => $p->stock_quantity,
                    'reorder_level'  => $p->reorder_level,
                    'requires_rx'    => $p->requires_rx,
                    'category'       => $p->category->category_name ?? '—',
                ];
            });

        return response()->json($products);
    }

    // ── CREATE STOCK ALERT ─────────────────────────────────────────────
    public function createAlert(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'message'    => 'required|string|max:500',
        ]);

        $product = DB::table('products')->where('id', $request->product_id)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Check if there's already an active alert for this product
        $existing = DB::table('alerts')
            ->where('product_id', $request->product_id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return response()->json(['error' => 'An active alert already exists for this product'], 422);
        }

        DB::table('alerts')->insert([
            'type'       => 'low_stock',
            'product_id' => $request->product_id,
            'created_by' => Auth::id(),
            'message'    => $request->message,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Alert sent to admin']);
    }
}