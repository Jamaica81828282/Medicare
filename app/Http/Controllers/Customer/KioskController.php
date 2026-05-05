<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    // ── SHOW KIOSK PAGE ──────────────────────────────────────────────
    public function index()
    {
        // Load all active products with their category code
        $products = DB::table('products as p')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->leftJoin('tax_rates as tr', 'p.tax_rate_id', '=', 'tr.id')
            ->where('p.is_active', 1)
            ->select([
                'p.id',
                'p.sku',
                'p.product_name',
                'p.generic_name',
                'p.brand',
                'p.dosage',
                'p.selling_price',
                'p.stock_quantity',
                'p.requires_rx',
                'p.description',
                'p.usage_recommendation',  // ← CHANGE 1: added for "How to Take" section
                'p.image_base64',          // nullable — added by migration below
                'pc.category_code as category',
                'tr.rate_percentage as tax_rate',
            ])
            ->orderBy('p.product_name')
            ->get()
            ->map(function ($p) {
                // Cast types so JS receives proper booleans / numbers
                $p->requires_rx    = (bool) $p->requires_rx;
                $p->selling_price  = (float) $p->selling_price;
                $p->stock_quantity = (int)   $p->stock_quantity;
                $p->tax_rate       = (float) ($p->tax_rate ?? 12);
                // Blade will embed image_base64 directly — null is fine
                return $p;
            });

        return view('customer.kiosk', compact('products'));
    }

    // ── SEARCH RETURNING CUSTOMER ─────────────────────────────────────
    public function searchCustomer(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        try {
            // Detect which columns actually exist so we never crash on a missing column
            $existingColumns = DB::getSchemaBuilder()->getColumnListing('customers');

            // Build select list from only columns confirmed to exist
            $wantedColumns = ['id', 'first_name', 'last_name', 'phone', 'address',
                              'is_senior', 'is_pwd', 'id_number',
                              'age', 'customer_code', 'email', 'loyalty_points'];
            $selectColumns = array_values(array_intersect($wantedColumns, $existingColumns));

            // Step 1: one canonical ID per phone (oldest = MIN id) — removes duplicates
            $canonicalIds = DB::table('customers')
                ->selectRaw('MIN(id) as id')
                ->groupBy('phone')
                ->pluck('id');

            // Step 2: search within canonical records only
            $customers = DB::table('customers')
                ->whereIn('id', $canonicalIds)
                ->where(function ($query) use ($q, $existingColumns) {
                    $query->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ["%{$q}%"])
                          ->orWhere('phone', 'LIKE', "%{$q}%");
                    if (in_array('customer_code', $existingColumns)) {
                        $query->orWhere('customer_code', 'LIKE', "%{$q}%");
                    }
                })
                ->select($selectColumns)
                ->orderByRaw("CONCAT(first_name,' ',last_name)")
                ->limit(8)
                ->get()
                ->map(function ($c) {
                    // Count past orders — try all common status values gracefully
                    try {
                        $c->order_count = DB::table('invoices')
                            ->where('customer_id', $c->id)
                            ->whereIn('status', ['paid', 'issued', 'completed'])
                            ->count();
                    } catch (\Exception $e) {
                        $c->order_count = 0;
                    }

                    $c->is_senior      = (bool) ($c->is_senior      ?? false);
                    $c->is_pwd         = (bool) ($c->is_pwd         ?? false);
                    $c->age            = $c->age            ?? null;
                    $c->customer_code  = $c->customer_code  ?? null;
                    $c->id_number      = $c->id_number      ?? null;
                    $c->loyalty_points = $c->loyalty_points ?? 0;
                    return $c;
                });

            return response()->json($customers);

        } catch (\Exception $e) {
            // Log the real error so you can see it in storage/logs/laravel.log
            Log::error('KioskController@searchCustomer failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── SUBMIT ORDER (create invoice) ─────────────────────────────────
    public function submitOrder(Request $request)
    {
        $request->validate([
            'customer.first_name' => 'required|string|max:100',
            'customer.last_name'  => 'required|string|max:100',
            'customer.phone'      => 'required|string|max:30',
            'items'               => 'required|array|min:1',
            'items.*.id'          => 'required|integer|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:1',
        ]);

$customerData = $request->input('customer');
        $items = $request->input('items');

        try {
            return DB::transaction(function () use ($customerData, $items) {

            // 1. Upsert customer ─────────────────────────────────────
            // CHANGE 3a: replaced the old "else { insertGetId }" block with
            // a phone-number lookup first, so a new customer typing their
            // own number never creates a second row in the customers table.
            $customerId = null;

            if (!empty($customerData['id'])) {
                // Returning customer selected from search — update and reuse
                DB::table('customers')
                    ->where('id', $customerData['id'])
                    ->update([
                        'first_name' => $customerData['first_name'],
                        'last_name'  => $customerData['last_name'],
                        'phone'      => $customerData['phone'],
                        'address'    => $customerData['address']  ?? null,
                        'age'        => $customerData['age']       ?? null,
                        'is_senior'  => $customerData['is_senior'] ?? 0,
                        'is_pwd'     => $customerData['is_pwd']    ?? 0,
                        'id_number'  => $customerData['id_number'] ?? null,
                        'updated_at' => now(),
                    ]);
                $customerId = $customerData['id'];
            } else {
                // Check if this phone number already exists before inserting
                $existing = DB::table('customers')
                    ->where('phone', $customerData['phone'])
                    ->orderBy('id')
                    ->first();

                if ($existing) {
                    // Reuse the existing record — do NOT create a duplicate
                    DB::table('customers')
                        ->where('id', $existing->id)
                        ->update([
                            'first_name' => $customerData['first_name'],
                            'last_name'  => $customerData['last_name'],
                            'address'    => $customerData['address']  ?? $existing->address,
                            'age'        => $customerData['age']       ?? $existing->age,
                            'is_senior'  => $customerData['is_senior'] ?? $existing->is_senior,
                            'is_pwd'     => $customerData['is_pwd']    ?? $existing->is_pwd,
                            'id_number'  => $customerData['id_number'] ?? $existing->id_number,
                            'updated_at' => now(),
                        ]);
                    $customerId = $existing->id;
                } else {
                    // Genuinely new customer — insert
                    $customerId = DB::table('customers')->insertGetId([
                        'first_name' => $customerData['first_name'],
                        'last_name'  => $customerData['last_name'],
                        'phone'      => $customerData['phone'],
                        'address'    => $customerData['address']  ?? null,
                        'age'        => $customerData['age']       ?? null,
                        'is_senior'  => $customerData['is_senior'] ?? 0,
                        'is_pwd'     => $customerData['is_pwd']    ?? 0,
                        'id_number'  => $customerData['id_number'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

// 2. Generate invoice number ──────────────────────────────
            $invoiceNumber = null;
            DB::statement('CALL generate_invoice_number(@inv_no)');
            $row = DB::selectOne('SELECT @inv_no AS inv_no');
            $invoiceNumber = $row->inv_no;

            // 3. Calculate totals ─────────────────────────────────────
            $subtotal      = 0;
            $totalTax      = 0;
            $lineItems     = [];

            foreach ($items as $item) {
                $product = DB::table('products as p')
                    ->leftJoin('tax_rates as tr', 'p.tax_rate_id', '=', 'tr.id')
                    ->leftJoin('units_of_measure as u', 'p.uom_id', '=', 'u.id')
                    ->where('p.id', $item['id'])
                    ->select('p.*', 'tr.rate_percentage as tax_rate', 'u.uom_code')
                    ->first();

                if (!$product) continue;

                $qty          = (float) $item['qty'];
                $unitPrice    = (float) $product->selling_price;
                $taxRate      = (float) ($product->tax_rate ?? 0);
                $lineSubtotal = round($unitPrice * $qty, 2);
                $lineTax      = round($lineSubtotal * ($taxRate / 100), 2);
                $lineTotal    = $lineSubtotal + $lineTax;

                $subtotal  += $lineSubtotal;
                $totalTax  += $lineTax;

                $lineItems[] = [
                    'product'      => $product,
                    'qty'          => $qty,
                    'unitPrice'    => $unitPrice,
                    'taxRate'      => $taxRate,
                    'lineSubtotal' => $lineSubtotal,
                    'lineTax'      => $lineTax,
                    'lineTotal'    => $lineTotal,
                ];
            }

            $grandTotal = round($subtotal + $totalTax, 2);

            // 4. Insert invoice ───────────────────────────────────────
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => now(),
                'customer_id'    => $customerId,
                'status'         => 'draft',   // cashier will change to 'paid'
                'subtotal'       => $subtotal,
                'total_discount' => 0,
                'total_tax'      => $totalTax,
                'grand_total'    => $grandTotal,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 5. Insert invoice items ─────────────────────────────────
            foreach ($lineItems as $li) {
                $p = $li['product'];
                DB::table('invoice_items')->insert([
                    'invoice_id'   => $invoiceId,
                    'product_id'   => $p->id,
                    'product_name' => $p->product_name,
                    'generic_name' => $p->generic_name,
                    'uom_code'     => $p->uom_code ?? 'PC',
                    'quantity'     => $li['qty'],
                    'unit_price'   => $li['unitPrice'],
                    'tax_rate_id'  => $p->tax_rate_id,
                    'tax_rate_pct' => $li['taxRate'],
                    'line_subtotal'=> $li['lineSubtotal'],
                    'line_tax'     => $li['lineTax'],
                    'line_discount'=> 0,
                    'line_total'   => $li['lineTotal'],
                    'sort_order'   => 0,
                ]);

                // Deduct stock
                DB::table('products')
                    ->where('id', $p->id)
                    ->decrement('stock_quantity', $li['qty']);
            }

// Create queue ticket
$ticketData = \App\Models\QueueTicket::nextForToday();
$ticket = \App\Models\QueueTicket::create([
    'queue_number'   => $ticketData['queue_number'],
    'invoice_id'     => $invoiceId,
    'customer_name'  => trim($customerData['first_name'] . ' ' . $customerData['last_name']),
    'status'         => 'waiting',
    'queue_date'     => $ticketData['queue_date'],
    'daily_sequence' => $ticketData['sequence'],
]);

return response()->json([
    'success'        => true,
    'invoice_number' => $invoiceNumber,
    'invoice_id'     => $invoiceId,
    'grand_total'    => $grandTotal,
    'queue_number'   => $ticket->queue_number,
]);
   
        });
        } catch (\Exception $e) {
            Log::error('KioskController@submitOrder failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save order: ' . $e->getMessage()], 500);
        }
    }

    // ── SEED DEMO PRODUCTS INTO DB ───────────────────────────────────
    // Visit: GET /kiosk/seed-products  (remove this route in production!)
    public function seedProducts()
    {
        $demo = [
            ['sku'=>'BIOG-500','product_name'=>'Biogesic','generic_name'=>'Paracetamol 500mg','brand'=>'Unilab','dosage'=>'500mg','category_code'=>'OTC','requires_rx'=>0,'selling_price'=>8.50,'cost_price'=>4.00,'stock_quantity'=>150,'reorder_level'=>20,'description'=>'Fast-acting pain reliever and fever reducer suitable for adults and children.'],
            ['sku'=>'NEOZ-CAP','product_name'=>'Neozep Forte','generic_name'=>'Phenylephrine + Paracetamol','brand'=>'Unilab','dosage'=>'10mg/325mg','category_code'=>'OTC','requires_rx'=>0,'selling_price'=>11.00,'cost_price'=>5.50,'stock_quantity'=>80,'reorder_level'=>15,'description'=>'Provides relief from nasal congestion, runny nose, headache, and fever.'],
            ['sku'=>'ALAX-FC','product_name'=>'Alaxan FR','generic_name'=>'Ibuprofen + Paracetamol','brand'=>'Unilab','dosage'=>'200mg/325mg','category_code'=>'OTC','requires_rx'=>0,'selling_price'=>14.00,'cost_price'=>7.00,'stock_quantity'=>60,'reorder_level'=>10,'description'=>'Dual-action pain reliever combining ibuprofen and paracetamol.'],
            ['sku'=>'AMOX-500','product_name'=>'Amoxicillin','generic_name'=>'Amoxicillin Trihydrate 500mg','brand'=>'Generics','dosage'=>'500mg','category_code'=>'RX','requires_rx'=>1,'selling_price'=>18.00,'cost_price'=>9.00,'stock_quantity'=>200,'reorder_level'=>30,'description'=>'Penicillin-type antibiotic for bacterial infections.'],
            ['sku'=>'VITC-500','product_name'=>'Fern-C','generic_name'=>'Ascorbic Acid 500mg','brand'=>'Fern-C','dosage'=>'500mg','category_code'=>'VITAMINS','requires_rx'=>0,'selling_price'=>9.50,'cost_price'=>4.75,'stock_quantity'=>300,'reorder_level'=>50,'description'=>'Non-acidic Vitamin C using sodium ascorbate, gentle on the stomach.'],
            ['sku'=>'OMEP-20','product_name'=>'Omeprazole','generic_name'=>'Omeprazole 20mg','brand'=>'Generics','dosage'=>'20mg','category_code'=>'RX','requires_rx'=>1,'selling_price'=>16.00,'cost_price'=>8.00,'stock_quantity'=>0,'reorder_level'=>20,'description'=>'Proton pump inhibitor that reduces stomach acid production.'],
            ['sku'=>'CETI-10','product_name'=>'Cetirizine','generic_name'=>'Cetirizine HCl 10mg','brand'=>'Zyrtec','dosage'=>'10mg','category_code'=>'OTC','requires_rx'=>0,'selling_price'=>22.00,'cost_price'=>11.00,'stock_quantity'=>45,'reorder_level'=>10,'description'=>'Non-drowsy antihistamine for relief from allergy symptoms.'],
            ['sku'=>'MYRAE-400','product_name'=>'Myra-E 400','generic_name'=>'Vitamin E 400 IU','brand'=>'Myra','dosage'=>'400 IU','category_code'=>'VITAMINS','requires_rx'=>0,'selling_price'=>28.00,'cost_price'=>14.00,'stock_quantity'=>90,'reorder_level'=>15,'description'=>'Natural Vitamin E that nourishes skin from within.'],
            ['sku'=>'BP-MON','product_name'=>'BP Monitor','generic_name'=>'Automatic Blood Pressure Monitor','brand'=>'Omron','dosage'=>'N/A','category_code'=>'MEDICAL','requires_rx'=>0,'selling_price'=>1850.00,'cost_price'=>1200.00,'stock_quantity'=>12,'reorder_level'=>3,'description'=>'Clinically validated automatic blood pressure monitor for home use.'],
            ['sku'=>'NEUT-HB','product_name'=>'Neutrogena Hydro Boost','generic_name'=>'Water Gel Moisturizer','brand'=>'Neutrogena','dosage'=>'50ml','category_code'=>'BEAUTY','requires_rx'=>0,'selling_price'=>485.00,'cost_price'=>280.00,'stock_quantity'=>25,'reorder_level'=>5,'description'=>'Lightweight, oil-free moisturizer that quenches skin and keeps it hydrated.'],
            ['sku'=>'CETAL-SYR','product_name'=>'Cetalgin Syrup','generic_name'=>'Paracetamol 250mg/5mL','brand'=>'Pascual','dosage'=>'250mg/5mL','category_code'=>'OTC','requires_rx'=>0,'selling_price'=>65.00,'cost_price'=>32.00,'stock_quantity'=>40,'reorder_level'=>10,'description'=>'Pediatric syrup for gentle fever and pain relief in children.'],
            ['sku'=>'JJ-BLOT','product_name'=>"Johnson's Baby Lotion",'generic_name'=>'Baby Moisturizing Lotion','brand'=>"Johnson's",'dosage'=>'500ml','category_code'=>'BABY','requires_rx'=>0,'selling_price'=>185.00,'cost_price'=>100.00,'stock_quantity'=>0,'reorder_level'=>5,'description'=>"Clinically proven mild and gentle on baby's skin."],
        ];

        // Get default tax rate (VAT12) and UOM (PC)
        $vatId  = DB::table('tax_rates')->where('tax_code', 'VAT12')->value('id');
        $pcId   = DB::table('units_of_measure')->where('uom_code', 'PC')->value('id');

        $inserted = 0;
        $skipped  = 0;

        foreach ($demo as $d) {
            // Skip if SKU already exists
            if (DB::table('products')->where('sku', $d['sku'])->exists()) {
                $skipped++;
                continue;
            }

            $catId = DB::table('product_categories')
                ->where('category_code', $d['category_code'])
                ->value('id');

            DB::table('products')->insert([
                'sku'            => $d['sku'],
                'product_name'   => $d['product_name'],
                'generic_name'   => $d['generic_name'],
                'brand'          => $d['brand'],
                'dosage'         => $d['dosage'],
                'category_id'    => $catId,
                'uom_id'         => $pcId,
                'tax_rate_id'    => $vatId,
                'cost_price'     => $d['cost_price'],
                'selling_price'  => $d['selling_price'],
                'requires_rx'    => $d['requires_rx'],
                'is_active'      => 1,
                'stock_quantity' => $d['stock_quantity'],
                'reorder_level'  => $d['reorder_level'],
                'description'    => $d['description'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $inserted++;
        }

        return response()->json([
            'success'  => true,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'message'  => "Done! {$inserted} products inserted, {$skipped} already existed.",
        ]);
    }

    // ── UPLOAD PRODUCT IMAGE ─────────────────────────────────────────
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // max 2MB
        ]);

        $file     = $request->file('image');
        $mime     = $file->getMimeType();
        $base64   = base64_encode(file_get_contents($file->getRealPath()));
        $dataUri  = "data:{$mime};base64,{$base64}";

        DB::table('products')->where('id', $id)->update([
            'image_base64' => $dataUri,
            'updated_at'   => now(),
        ]);

        return response()->json(['success' => true, 'image' => $dataUri]);
    }

    // ── UPDATE EXISTING DRAFT INVOICE ────────────────────────────────
    public function update(Request $request)
    {
        $request->validate([
            'invoice_id'          => 'required|integer|exists:invoices,id',
            'customer.first_name' => 'required|string|max:100',
            'customer.last_name'  => 'required|string|max:100',
            'customer.phone'      => 'required|string|max:30',
            'items'               => 'required|array|min:1',
            'items.*.id'          => 'required|integer|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:1',
        ]);

        $invoiceId   = $request->input('invoice_id');
        $customerData = $request->input('customer');
        $items        = $request->input('items');

        return DB::transaction(function () use ($invoiceId, $customerData, $items) {

            // 1. Check invoice exists and is draft ─────────────────────
            $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
            if (!$invoice || $invoice->status !== 'draft') {
                return response()->json(['error' => 'Invoice not found or not in draft status.'], 422);
            }

            // 2. Update customer ───────────────────────────────────────
            // CHANGE 3b: same phone-number-lookup fix applied here too
            if (!empty($customerData['id'])) {
                // Returning customer selected from search — update and reuse
                DB::table('customers')
                    ->where('id', $customerData['id'])
                    ->update([
                        'first_name' => $customerData['first_name'],
                        'last_name'  => $customerData['last_name'],
                        'phone'      => $customerData['phone'],
                        'address'    => $customerData['address']  ?? null,
                        'age'        => $customerData['age']       ?? null,
                        'is_senior'  => $customerData['is_senior'] ?? 0,
                        'is_pwd'     => $customerData['is_pwd']    ?? 0,
                        'id_number'  => $customerData['id_number'] ?? null,
                        'updated_at' => now(),
                    ]);
                $customerId = $customerData['id'];
            } else {
                // Check if this phone number already exists before inserting
                $existing = DB::table('customers')
                    ->where('phone', $customerData['phone'])
                    ->orderBy('id')
                    ->first();

                if ($existing) {
                    // Reuse the existing record — do NOT create a duplicate
                    DB::table('customers')
                        ->where('id', $existing->id)
                        ->update([
                            'first_name' => $customerData['first_name'],
                            'last_name'  => $customerData['last_name'],
                            'address'    => $customerData['address']  ?? $existing->address,
                            'age'        => $customerData['age']       ?? $existing->age,
                            'is_senior'  => $customerData['is_senior'] ?? $existing->is_senior,
                            'is_pwd'     => $customerData['is_pwd']    ?? $existing->is_pwd,
                            'id_number'  => $customerData['id_number'] ?? $existing->id_number,
                            'updated_at' => now(),
                        ]);
                    $customerId = $existing->id;
                } else {
                    // Genuinely new customer — insert
                    $customerId = DB::table('customers')->insertGetId([
                        'first_name' => $customerData['first_name'],
                        'last_name'  => $customerData['last_name'],
                        'phone'      => $customerData['phone'],
                        'address'    => $customerData['address']  ?? null,
                        'age'        => $customerData['age']       ?? null,
                        'is_senior'  => $customerData['is_senior'] ?? 0,
                        'is_pwd'     => $customerData['is_pwd']    ?? 0,
                        'id_number'  => $customerData['id_number'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 3. Restore stock from old items ──────────────────────────
            $oldItems = DB::table('invoice_items')->where('invoice_id', $invoiceId)->get();
            foreach ($oldItems as $oldItem) {
                DB::table('products')
                    ->where('id', $oldItem->product_id)
                    ->increment('stock_quantity', $oldItem->quantity);
            }

            // 4. Delete old invoice items ──────────────────────────────
            DB::table('invoice_items')->where('invoice_id', $invoiceId)->delete();

            // 5. Calculate new totals ───────────────────────────────────
            $subtotal      = 0;
            $totalTax      = 0;
            $lineItems     = [];

            foreach ($items as $item) {
                $product = DB::table('products as p')
                    ->leftJoin('tax_rates as tr', 'p.tax_rate_id', '=', 'tr.id')
                    ->leftJoin('units_of_measure as u', 'p.uom_id', '=', 'u.id')
                    ->where('p.id', $item['id'])
                    ->select('p.*', 'tr.rate_percentage as tax_rate', 'u.uom_code')
                    ->first();

                if (!$product) continue;

                $qty          = (float) $item['qty'];
                $unitPrice    = (float) $product->selling_price;
                $taxRate      = (float) ($product->tax_rate ?? 0);
                $lineSubtotal = round($unitPrice * $qty, 2);
                $lineTax      = round($lineSubtotal * ($taxRate / 100), 2);
                $lineTotal    = $lineSubtotal + $lineTax;

                $subtotal  += $lineSubtotal;
                $totalTax  += $lineTax;

                $lineItems[] = [
                    'product'      => $product,
                    'qty'          => $qty,
                    'unitPrice'    => $unitPrice,
                    'taxRate'      => $taxRate,
                    'lineSubtotal' => $lineSubtotal,
                    'lineTax'      => $lineTax,
                    'lineTotal'    => $lineTotal,
                ];
            }

            $grandTotal = round($subtotal + $totalTax, 2);

            // 6. Update invoice ────────────────────────────────────────
            DB::table('invoices')->where('id', $invoiceId)->update([
                'customer_id'    => $customerId,
                'subtotal'       => $subtotal,
                'total_discount' => 0,
                'total_tax'      => $totalTax,
                'grand_total'    => $grandTotal,
                'updated_at'     => now(),
            ]);

            // 7. Insert new invoice items ──────────────────────────────
            foreach ($lineItems as $li) {
                $p = $li['product'];
                DB::table('invoice_items')->insert([
                    'invoice_id'   => $invoiceId,
                    'product_id'   => $p->id,
                    'product_name' => $p->product_name,
                    'generic_name' => $p->generic_name,
                    'uom_code'     => $p->uom_code ?? 'PC',
                    'quantity'     => $li['qty'],
                    'unit_price'   => $li['unitPrice'],
                    'tax_rate_id'  => $p->tax_rate_id,
                    'tax_rate_pct' => $li['taxRate'],
                    'line_subtotal'=> $li['lineSubtotal'],
                    'line_tax'     => $li['lineTax'],
                    'line_discount'=> 0,
                    'line_total'   => $li['lineTotal'],
                    'sort_order'   => 0,
                ]);

                // Deduct stock again
                DB::table('products')
                    ->where('id', $p->id)
                    ->decrement('stock_quantity', $li['qty']);
            }

           $existingTicket = \App\Models\QueueTicket::where('invoice_id', $invoiceId)->first();

return response()->json([
    'success'        => true,
    'invoice_number' => $invoice->invoice_number,
    'invoice_id'     => $invoiceId,
    'grand_total'    => $grandTotal,
    'queue_number'   => $existingTicket?->queue_number,
]);
        });
    }
}