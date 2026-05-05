<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CashierController extends Controller
{

    // ── DASHBOARD ─────────────────────────────────────────────────────
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'pending'       => DB::table('invoices')->where('status', 'draft')->count(),
            'today_sales'   => DB::table('invoices')
                                  ->where('status', 'paid')
                                  ->whereDate('invoice_date', $today)
                                  ->sum('grand_total'),
            'today_count'   => DB::table('invoices')
                                  ->where('status', 'paid')
                                  ->whereDate('invoice_date', $today)
                                  ->count(),
            'today_rx'      => DB::table('invoices')
                                  ->where('status', 'paid')
                                  ->whereDate('invoice_date', $today)
                                  ->whereNotNull('prescription_no')
                                  ->count(),
        ];

        $pendingOrders = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.status', 'draft')
            ->select(
                'invoices.*',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as customer_name"),
                'customers.phone',
                'customers.is_senior',
                'customers.is_pwd',
                'customers.id_number'
            )
            ->orderBy('invoices.created_at', 'asc')
            ->get()
            ->map(function ($inv) {
                $inv->items = DB::table('invoice_items')
                    ->where('invoice_id', $inv->id)
                    ->get();
                return $inv;
            });

        $recentPaid = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.status', 'paid')
            ->whereDate('invoices.invoice_date', $today)
            ->select(
                'invoices.*',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as customer_name")
            )
            ->orderBy('invoices.updated_at', 'desc')
            ->limit(10)
            ->get();

        $paymentMethods = DB::table('payment_methods')->where('is_active', 1)->get();
        $discountTypes  = DB::table('discount_types')->where('is_active', 1)->get();

        return view('cashier.dashboard', compact(
            'stats', 'pendingOrders', 'recentPaid', 'paymentMethods', 'discountTypes'
        ));
    }

    // ── GET INVOICE DETAIL (AJAX) ──────────────────────────────────────
    public function getInvoice($id)
    {
        $invoice = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
            ->where('invoices.id', $id)
            ->select(
                'invoices.*',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as customer_name"),
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

    // ── PROCESS PAYMENT ────────────────────────────────────────────────
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
            $subtotal      = (float) $invoice->subtotal;
            $discountAmt   = 0;
            $discountType  = null;

            // Apply discount if selected
            if ($request->discount_type_id) {
                $discountType = DB::table('discount_types')->find($request->discount_type_id);
                if ($discountType) {
                    if ($discountType->discount_method === 'percentage') {
                        $discountAmt = $subtotal * ($discountType->discount_value / 100);
                    } else {
                        $discountAmt = (float) $discountType->discount_value;
                    }
                    // For Senior/PWD — VAT-exempt on discounted amount (PH law)
                    // Recalculate: remove VAT from subtotal first, then apply discount
                    if (in_array($discountType->discount_code, ['SENIOR20', 'PWD20'])) {
                        $vatExemptBase = $subtotal / 1.12;
                        $discountAmt   = $vatExemptBase * ($discountType->discount_value / 100);
                        $newSubtotal   = $vatExemptBase - $discountAmt;
                        $newTax        = 0; // VAT-exempt
                        $grandTotal    = $newSubtotal;
                    } else {
                         $grandTotal = $subtotal - $discountAmt;
                         $newTax     = $grandTotal * 0.12 / 1.12;
                    }
                }
            } else {
                $grandTotal = (float) $invoice->grand_total;
                $newTax     = (float) $invoice->total_tax;
            }

            $changeAmount = (float) $request->amount_tendered - $grandTotal;

            // Update invoice
            DB::table('invoices')->where('id', $invoice->id)->update([
                'status'            => 'paid',
                'payment_method_id' => $request->payment_method_id,
                'payment_ref'       => $request->payment_ref ?? null,
                'prescription_no'   => $request->prescription_no ?? null,
                'total_discount'    => $discountAmt,
                'total_tax'         => isset($newTax) ? $newTax : $invoice->total_tax,
                'grand_total'       => $grandTotal,
                'amount_tendered'   => $request->amount_tendered,
                'change_amount'     => max(0, $changeAmount),
                'cashier_id'        => Auth::id(),
                'updated_at'        => now(),
            ]);

            // Record discount
            if ($discountType && $discountAmt > 0) {
                // Remove old discounts first
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

            // Deduct stock
            $items = DB::table('invoice_items')->where('invoice_id', $invoice->id)->get();
            foreach ($items as $item) {
                DB::table('products')
                    ->where('id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }

          // Advance queue ticket to 'paid' so it appears in pickup queue
            

           // Advance queue ticket to 'paid' so it appears in pickup queue
            $ticket = \App\Models\QueueTicket::where('invoice_id', $invoice->id)->first();

            if (!$ticket) {
                $next = \App\Models\QueueTicket::nextForToday();

                $customerName = null;
                if ($invoice->customer_id) {
                    $customerName = DB::table('customers')
                        ->where('id', $invoice->customer_id)
                        ->selectRaw("TRIM(CONCAT(first_name, ' ', last_name)) as full_name")
                        ->value('full_name');
                }

                $ticket = \App\Models\QueueTicket::create([
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

    // ── VOID INVOICE ───────────────────────────────────────────────────
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

    // ── PRINT INVOICE ──────────────────────────────────────────────────
    public function printInvoice($id)
    {
        $invoice = DB::table('invoices')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('payment_methods', 'invoices.payment_method_id', '=', 'payment_methods.id')
            ->leftJoin('users', 'invoices.cashier_id', '=', 'users.id')
            ->where('invoices.id', $id)
            ->select(
                'invoices.*',
                DB::raw("CONCAT(customers.first_name, ' ', customers.last_name) as customer_name"),
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
}