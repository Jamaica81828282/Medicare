<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ── DASHBOARD ──────────────────────────────────────────────────────
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'total_products'  => DB::table('products')->where('is_active', 1)->count(),
            'total_suppliers' => DB::table('suppliers')->where('is_active', 1)->count(),
            'total_invoices'  => DB::table('invoices')->where('status', 'paid')->count(),
            'today_revenue'   => DB::table('invoices')->where('status', 'paid')->whereDate('invoice_date', $today)->sum('grand_total'),
            'low_stock'       => DB::table('products')->where('is_active', 1)->whereRaw('stock_quantity <= reorder_level')->count(),
            'expiring_30'     => DB::table('product_batches')->where('is_active', 1)->where('quantity', '>', 0)->where('expiry_date', '<=', Carbon::now()->addDays(30))->count(),
            'total_cashiers'  => DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')->where('roles.name', 'cashier')->count(),
            'month_revenue'   => DB::table('invoices')->where('status', 'paid')->whereMonth('invoice_date', $today->month)->whereYear('invoice_date', $today->year)->sum('grand_total'),
        ];

        $categories = DB::table('product_categories')->where('is_active', 1)->get();
        $suppliers  = DB::table('suppliers')->where('is_active', 1)->get();
        $tax_rates  = DB::table('tax_rates')->where('is_active', 1)->get();
        $uoms       = DB::table('units_of_measure')->get();

        return view('admin.dashboard', compact('stats', 'categories', 'suppliers', 'tax_rates', 'uoms'));
    }

    /**
     * Allow admin to view a specific cashier's dashboard
     */
    public function viewCashierDashboard($cashierId)
    {
        // Verify the cashier exists and is a cashier
        $cashier = DB::table('users as u')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('u.id', $cashierId)
            ->where('r.name', 'cashier')
            ->select('u.*', 'r.name as role_name')
            ->first();

        if (!$cashier) {
            return redirect()->route('admin.dashboard')->with('error', 'Cashier not found');
        }

        // Temporarily set the cashier as the authenticated user for the view
        // We'll do this by passing the cashier data to the cashier dashboard view
        $today = Carbon::today();

        // Get cashier-specific stats
        $stats = [
            'today_invoices'  => DB::table('invoices')->where('cashier_id', $cashierId)->whereDate('invoice_date', $today)->count(),
            'today_revenue'   => DB::table('invoices')->where('cashier_id', $cashierId)->where('status', 'paid')->whereDate('invoice_date', $today)->sum('grand_total'),
            'today_void'      => DB::table('invoices')->where('cashier_id', $cashierId)->where('status', 'voided')->whereDate('invoice_date', $today)->count(),
            'month_invoices'  => DB::table('invoices')->where('cashier_id', $cashierId)->whereMonth('invoice_date', $today->month)->whereYear('invoice_date', $today->year)->count(),
            'month_revenue'   => DB::table('invoices')->where('cashier_id', $cashierId)->where('status', 'paid')->whereMonth('invoice_date', $today->month)->whereYear('invoice_date', $today->year)->sum('grand_total'),
            'total_paid_invoices' => DB::table('invoices')->where('cashier_id', $cashierId)->where('status', 'paid')->count(),
        ];

        // ── SHIFT REPORT for the specific cashier ─────────────────────────
        $shiftReport = [
            'cashier_name'       => $cashier->name,
            'shift_start'        => Carbon::today()->format('h:i A'),
            'total_sales'        => DB::table('invoices')->where('status','paid')->where('cashier_id',$cashierId)->whereDate('invoice_date',$today)->sum('grand_total'),
            'total_transactions' => DB::table('invoices')->where('status','paid')->where('cashier_id',$cashierId)->whereDate('invoice_date',$today)->count(),
            'total_discount'     => DB::table('invoices')->where('status','paid')->where('cashier_id',$cashierId)->whereDate('invoice_date',$today)->sum('total_discount'),
            'total_tax'          => DB::table('invoices')->where('status','paid')->where('cashier_id',$cashierId)->whereDate('invoice_date',$today)->sum('total_tax'),
            'voided_count'       => DB::table('invoices')->where('status','voided')->where('cashier_id',$cashierId)->whereDate('invoice_date',$today)->count(),
            'top_products'       => DB::table('invoice_items')
                                        ->join('invoices','invoices.id','=','invoice_items.invoice_id')
                                        ->where('invoices.status','paid')
                                        ->where('invoices.cashier_id',$cashierId)
                                        ->whereDate('invoices.invoice_date',$today)
                                        ->select(DB::raw('invoice_items.product_name, SUM(invoice_items.quantity) as qty_sold'))
                                        ->groupBy('invoice_items.product_name')
                                        ->orderByDesc('qty_sold')
                                        ->limit(5)
                                        ->get(),
        ];

        return view('admin.cashier-view', [
            'cashier' => $cashier,
            'stats' => $stats,
            'shiftReport' => $shiftReport,
            'viewingAsAdmin' => true,
        ]);
    }

    // ═══════════════════════════════════════════════════
    //  PRODUCTS
    // ═══════════════════════════════════════════════════

   public function getProducts(Request $request)
{
    $q      = trim($request->get('q', '') ?? '');
    $cat    = trim($request->get('category', '') ?? '');
    $status = trim($request->get('status', '') ?? '');

    $query = DB::table('products as p')
        ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
        ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
       ->select(
    'p.id', 'p.sku', 'p.barcode', 'p.product_name', 'p.generic_name',
    'p.brand', 'p.dosage', 'p.category_id', 'p.supplier_id', 'p.uom_id',
    'p.tax_rate_id', 'p.cost_price', 'p.selling_price', 'p.requires_rx',
    'p.is_active', 'p.stock_quantity', 'p.reorder_level', 'p.description',
    'p.usage_recommendation', 'p.created_at',
    DB::raw('CASE WHEN p.image_base64 IS NOT NULL AND p.image_base64 != "" THEN 1 ELSE 0 END as has_image'),
    'pc.category_name', 's.supplier_name'
);

    if (!empty($q)) {
        $query->where(function ($qb) use ($q) {
            $qb->where('p.product_name', 'like', "%{$q}%")
               ->orWhere('p.generic_name', 'like', "%{$q}%")
               ->orWhere('p.sku',          'like', "%{$q}%")
               ->orWhere('p.brand',        'like', "%{$q}%");
        });
    }

    if (!empty($cat))    $query->where('p.category_id', $cat);
    if (!empty($status)) $query->where('p.is_active', (int) $status);

   $products = $query->orderBy('p.product_name')->get();
return response()->json($products);
}

 public function getProduct($id)
{
    $p = DB::table('products as p')
        ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
        ->leftJoin('suppliers as s', 'p.supplier_id', '=', 's.id')
        ->select('p.*', 'pc.category_name', 's.supplier_name')
        ->where('p.id', $id)
        ->first();

    if (!$p) return response()->json(['error' => 'Not found'], 404);

    $p->has_image = !empty($p->image_base64);

    // Don't send the raw path or huge base64 — let the image route serve it
    // Just tell the frontend whether an image exists
    $p->image_base64 = $p->has_image
        ? route('admin.products.image', $p->id)  // send the URL instead
        : null;

    return response()->json($p);
}

    public function storeProduct(Request $request)
    {
        $request->validate([
            'sku'           => 'required|string|max:50|unique:products,sku',
            'product_name'  => 'required|string|max:200',
            'selling_price' => 'required|numeric|min:0',
            'cost_price'    => 'required|numeric|min:0',
            'category_id'   => 'required|integer',
            'stock_quantity'=> 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        $imageBase64 = null;
        if ($request->hasFile('image')) {
            $file        = $request->file('image');
            $mime        = $file->getMimeType();
            $data        = base64_encode(file_get_contents($file->getRealPath()));
            $imageBase64 = "data:{$mime};base64,{$data}";
        }

        $id = DB::table('products')->insertGetId([
            'sku'                  => strtoupper(trim($request->sku)),
            'barcode'              => $request->barcode ?? null,
            'product_name'         => $request->product_name,
            'generic_name'         => $request->generic_name ?? null,
            'brand'                => $request->brand ?? null,
            'dosage'               => $request->dosage ?? null,
            'category_id'          => $request->category_id,
            'supplier_id'          => $request->supplier_id ?? null,
            'uom_id'               => $request->uom_id ?? null,
            'tax_rate_id'          => $request->tax_rate_id ?? null,
            'cost_price'           => $request->cost_price,
            'selling_price'        => $request->selling_price,
            'requires_rx'          => $request->boolean('requires_rx') ? 1 : 0,
            'is_active'            => 1,
            'stock_quantity'       => $request->stock_quantity,
            'reorder_level'        => $request->reorder_level,
            'description'          => $request->description ?? null,
            'usage_recommendation' => $request->usage_recommendation ?? null,
            'image_base64'         => $imageBase64,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }
public function serveProductImage($id)
{
    $image = DB::table('products')->where('id', $id)->value('image_base64');

    if (!$image) abort(404);

    // Handle actual base64 data URI (e.g. Flanax)
    if (preg_match('/^data:(image\/\w+);base64,(.+)$/', $image, $matches)) {
        return response(base64_decode($matches[2]), 200)
            ->header('Content-Type', $matches[1])
            ->header('Cache-Control', 'public, max-age=86400');
    }

    // Handle file paths like /images/products/biogesic.jpg
    if (str_starts_with($image, '/')) {
        $path = public_path($image);
        if (file_exists($path)) {
            return response()->file($path, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
}
    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'sku'           => "required|string|max:50|unique:products,sku,{$id}",
            'product_name'  => 'required|string|max:200',
            'selling_price' => 'required|numeric|min:0',
            'cost_price'    => 'required|numeric|min:0',
        ]);

        $data = [
            'sku'                  => strtoupper(trim($request->sku)),
            'barcode'              => $request->barcode ?? null,
            'product_name'         => $request->product_name,
            'generic_name'         => $request->generic_name ?? null,
            'brand'                => $request->brand ?? null,
            'dosage'               => $request->dosage ?? null,
            'category_id'          => $request->category_id ?? null,
            'supplier_id'          => $request->supplier_id ?? null,
            'uom_id'               => $request->uom_id ?? null,
            'tax_rate_id'          => $request->tax_rate_id ?? null,
            'cost_price'           => $request->cost_price,
            'selling_price'        => $request->selling_price,
            'requires_rx'          => $request->boolean('requires_rx') ? 1 : 0,
            'reorder_level'        => $request->reorder_level ?? 10,
            'description'          => $request->description ?? null,
            'usage_recommendation' => $request->usage_recommendation ?? null,
            'updated_at'           => now(),
        ];

        if ($request->hasFile('image')) {
            $file             = $request->file('image');
            $mime             = $file->getMimeType();
            $b64              = base64_encode(file_get_contents($file->getRealPath()));
            $data['image_base64'] = "data:{$mime};base64,{$b64}";
        }

        DB::table('products')->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function toggleProduct($id)
    {
        $p = DB::table('products')->where('id', $id)->first();
        if (!$p) return response()->json(['error' => 'Not found'], 404);

        DB::table('products')->where('id', $id)->update([
            'is_active'  => $p->is_active ? 0 : 1,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'is_active' => !$p->is_active]);
    }

    // ═══════════════════════════════════════════════════
    //  BATCHES
    // ═══════════════════════════════════════════════════

    public function getBatches(Request $request)
    {
        $q         = $request->get('q', '');
        $productId = $request->get('product_id', '');

        $query = DB::table('product_batches as pb')
            ->join('products as p', 'p.id', '=', 'pb.product_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'pb.supplier_id')
            ->select(
                'pb.*',
                'p.product_name', 'p.sku', 'p.stock_quantity as product_stock',
                's.supplier_name'
            )
            ->where('pb.is_active', 1);

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('pb.batch_number', 'like', "%{$q}%")
                   ->orWhere('pb.lot_number',  'like', "%{$q}%")
                   ->orWhere('p.product_name', 'like', "%{$q}%");
            });
        }

        if ($productId) $query->where('pb.product_id', $productId);

        return response()->json($query->orderBy('pb.expiry_date', 'asc')->get());
    }

    public function storeBatch(Request $request)
    {
        $request->validate([
            'product_id'   => 'required|integer|exists:products,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date'  => 'required|date|after:today',
            'quantity'     => 'required|numeric|min:1',
            'received_date'=> 'nullable|date',
            'supplier_id'  => 'nullable|integer|exists:suppliers,id',
            'cost_price'   => 'nullable|numeric|min:0',
        ]);

        $exists = DB::table('product_batches')
            ->where('product_id',   $request->product_id)
            ->where('batch_number', $request->batch_number)
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Batch number already exists for this product.'], 422);
        }

        DB::beginTransaction();
        try {
            $batchId = DB::table('product_batches')->insertGetId([
                'product_id'   => $request->product_id,
                'batch_number' => $request->batch_number,
                'lot_number'   => $request->lot_number ?? null,
                'expiry_date'  => $request->expiry_date,
                'quantity'     => $request->quantity,
                'cost_price'   => $request->cost_price ?? null,
                'received_date'=> $request->received_date ?? today()->toDateString(),
                'supplier_id'  => $request->supplier_id ?? null,
                'is_active'    => 1,
                'notes'        => $request->notes ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            if ($request->boolean('update_stock')) {
                DB::table('products')
                    ->where('id', $request->product_id)
                    ->increment('stock_quantity', (int) $request->quantity);
            }

            DB::commit();
            return response()->json(['success' => true, 'id' => $batchId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function confirmBatchStock(Request $request, $id)
    {
        $batch = DB::table('product_batches')->where('id', $id)->first();
        if (!$batch) return response()->json(['error' => 'Batch not found'], 404);

        DB::table('products')
            ->where('id', $batch->product_id)
            ->increment('stock_quantity', (int) $batch->quantity);

        return response()->json(['success' => true]);
    }

    public function deleteBatch($id)
    {
        DB::table('product_batches')->where('id', $id)->update([
            'is_active'  => 0,
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════
    //  SUPPLIERS
    // ═══════════════════════════════════════════════════

    public function getSuppliers(Request $request)
    {
        $q     = $request->get('q', '');
        $query = DB::table('suppliers');

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('supplier_name',   'like', "%{$q}%")
                   ->orWhere('supplier_code',  'like', "%{$q}%")
                   ->orWhere('contact_person', 'like', "%{$q}%");
            });
        }

        return response()->json($query->orderBy('supplier_name')->get());
    }

    public function storeSupplier(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:150',
            'supplier_code' => 'required|string|max:30|unique:suppliers,supplier_code',
        ]);

        $id = DB::table('suppliers')->insertGetId([
            'supplier_code'  => strtoupper(trim($request->supplier_code)),
            'supplier_name'  => $request->supplier_name,
            'contact_person' => $request->contact_person ?? null,
            'phone'          => $request->phone ?? null,
            'email'          => $request->email ?? null,
            'address'        => $request->address ?? null,
            'is_active'      => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateSupplier(Request $request, $id)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:150',
            'supplier_code' => "required|string|max:30|unique:suppliers,supplier_code,{$id}",
        ]);

        DB::table('suppliers')->where('id', $id)->update([
            'supplier_code'  => strtoupper(trim($request->supplier_code)),
            'supplier_name'  => $request->supplier_name,
            'contact_person' => $request->contact_person ?? null,
            'phone'          => $request->phone ?? null,
            'email'          => $request->email ?? null,
            'address'        => $request->address ?? null,
            'is_active'      => $request->boolean('is_active') ? 1 : 0,
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ═══════════════════════════════════════════════════
    //  INVOICE HISTORY
    // ═══════════════════════════════════════════════════

    public function getInvoices(Request $request)
    {
        $q        = $request->get('q', '');
        $status   = $request->get('status', '');
        $cashier  = $request->get('cashier_id', '');
        $method   = $request->get('payment_method', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');
        $page     = max(1, (int) $request->get('page', 1));
        $perPage  = 25;

        $query = DB::table('invoices as i')
            ->leftJoin('customers as c',     'c.id',  '=', 'i.customer_id')
            ->leftJoin('users as u',         'u.id',  '=', 'i.cashier_id')
            ->leftJoin('payment_methods as pm','pm.id','=', 'i.payment_method_id')
            ->select(
                'i.*',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) as customer_name"),
                'u.name as cashier_name',
                'pm.method_name as payment_method'
            );

        if ($q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('i.invoice_number', 'like', "%{$q}%")
                   ->orWhereRaw("CONCAT(c.first_name,' ',c.last_name) LIKE ?", ["%{$q}%"]);
            });
        }

        if ($status)   $query->where('i.status', $status);
        if ($cashier)  $query->where('i.cashier_id', $cashier);
        if ($method)   $query->where('i.payment_method_id', $method);
        if ($dateFrom) $query->whereDate('i.invoice_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('i.invoice_date', '<=', $dateTo);

        $total   = $query->count();
        $results = (clone $query)
            ->orderBy('i.invoice_date', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $topProducts = [];
        if ($dateFrom || $dateTo) {
            $topProducts = DB::table('invoice_items as ii')
                ->join('invoices as i', 'i.id', '=', 'ii.invoice_id')
                ->leftJoin('tax_rates as t', 't.id', '=', 'ii.tax_rate_id')
                ->where('i.status', 'paid');

            if ($dateFrom) {
                $topProducts->whereDate('i.invoice_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $topProducts->whereDate('i.invoice_date', '<=', $dateTo);
            }

            $topProducts = $topProducts
                ->select(
                    'ii.product_name',
                    DB::raw('SUM(ii.quantity) as qty_sold'),
                    DB::raw('SUM(ii.quantity * ii.unit_price * (1 + COALESCE(t.rate_percentage, 0) / 100)) as revenue')
                )
                ->groupBy('ii.product_name')
                ->orderByDesc('qty_sold')
                ->limit(8)
                ->get();
        }

        return response()->json([
            'data'         => $results,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'top_products' => $topProducts,
        ]);
    }

    public function getInvoiceItems($id)
    {
        $items = DB::table('invoice_items as ii')
            ->join('products as p',             'p.id',  '=', 'ii.product_id')
            ->leftJoin('units_of_measure as u',  'u.id',  '=', 'ii.uom_id')
            ->leftJoin('tax_rates as t',          't.id',  '=', 'ii.tax_rate_id')
            ->select(
                'ii.id', 'ii.invoice_id', 'ii.product_id', 'ii.quantity', 'ii.unit_price', 'ii.uom_id', 'ii.tax_rate_id',
                'p.product_name',
                'p.generic_name',
                'u.uom_code',
                DB::raw('COALESCE(t.rate_percentage, 0) as tax_rate_pct'),
                DB::raw('ii.quantity * ii.unit_price as line_subtotal'),
                DB::raw('ii.quantity * ii.unit_price * COALESCE(t.rate_percentage, 0) / 100 as line_tax'),
                DB::raw('ii.quantity * ii.unit_price * (1 + COALESCE(t.rate_percentage, 0) / 100) as line_total')
            )
            ->where('ii.invoice_id', $id)
            ->orderBy('ii.id', 'asc')
            ->get();

        return response()->json($items);
    }

    public function adminVoidInvoice(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) return response()->json(['error' => 'Invoice not found'], 404);
        if ($invoice->status === 'voided') return response()->json(['error' => 'Already voided'], 422);

        DB::beginTransaction();
        try {
            if ($invoice->status === 'paid') {
                $items = DB::table('invoice_items')->where('invoice_id', $id)->get();
                foreach ($items as $item) {
                    DB::table('products')
                        ->where('id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity);
                }
            }

            DB::table('invoices')->where('id', $id)->update([
                'status'        => 'voided',
                'voided_reason' => $request->reason,
                'voided_at'     => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportInvoices(Request $request)
    {
        $status   = $request->get('status', '');
        $cashier  = $request->get('cashier_id', '');
        $method   = $request->get('payment_method', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');

        $query = DB::table('invoices as i')
            ->leftJoin('customers as c',      'c.id',   '=', 'i.customer_id')
            ->leftJoin('users as u',          'u.id',   '=', 'i.cashier_id')
            ->leftJoin('payment_methods as pm','pm.id', '=', 'i.payment_method_id')
            ->select(
                'i.invoice_number', 'i.invoice_date', 'i.status',
                DB::raw("TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) as customer_name"),
                'c.phone as customer_phone',
                'i.subtotal', 'i.total_discount', 'i.total_tax', 'i.grand_total',
                'i.amount_tendered', 'i.change_amount',
                'pm.method_name as payment_method',
                'u.name as cashier_name',
                'i.prescription_no', 'i.voided_reason'
            );

        if ($status)   $query->where('i.status', $status);
        if ($cashier)  $query->where('i.cashier_id', $cashier);
        if ($method)   $query->where('i.payment_method_id', $method);
        if ($dateFrom) $query->whereDate('i.invoice_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('i.invoice_date', '<=', $dateTo);

        $rows = $query->orderBy('i.invoice_date', 'desc')->get();

        $csv = "Invoice #,Date,Status,Customer,Phone,Subtotal,Discount,VAT,Grand Total,Tendered,Change,Payment Method,Cashier,Prescription No,Void Reason\n";

        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
                [
                    $row->invoice_number, $row->invoice_date,   $row->status,
                    $row->customer_name,  $row->customer_phone,
                    $row->subtotal,       $row->total_discount,  $row->total_tax,
                    $row->grand_total,    $row->amount_tendered, $row->change_amount,
                    $row->payment_method, $row->cashier_name,
                    $row->prescription_no, $row->voided_reason,
                ]
            )) . "\n";
        }

        $filename = 'invoices_export_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ═══════════════════════════════════════════════════
    //  CASHIER ACCOUNT MANAGEMENT
    // ═══════════════════════════════════════════════════

    public function getUsers()
    {
        $users = DB::table('users as u')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
            ->join('roles as r',             'r.id',         '=', 'mhr.role_id')
            ->select('u.*', 'r.name as role_name')
            ->where('mhr.model_type', 'App\\Models\\User')
            ->whereIn('r.name', ['admin', 'cashier'])
            ->orderBy('u.name')
            ->get();

        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,cashier',
        ]);

        DB::beginTransaction();
        try {
            $userId = DB::table('users')->insertGetId([
                'name'       => $request->name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleId = DB::table('roles')->where('name', $request->role)->value('id');

            DB::table('model_has_roles')->insert([
                'role_id'    => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $userId,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'id' => $userId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'role'  => 'required|in:admin,cashier',
        ]);

        DB::beginTransaction();
        try {
            $data = [
                'name'       => $request->name,
                'email'      => $request->email,
                'updated_at' => now(),
            ];

            if ($request->filled('password')) {
                $request->validate(['password' => 'min:8|confirmed']);
                $data['password'] = Hash::make($request->password);
            }

            DB::table('users')->where('id', $id)->update($data);

            $roleId = DB::table('roles')->where('name', $request->role)->value('id');

            DB::table('model_has_roles')
                ->where('model_id',   $id)
                ->where('model_type', 'App\\Models\\User')
                ->delete();

            DB::table('model_has_roles')->insert([
                'role_id'    => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $id,
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}