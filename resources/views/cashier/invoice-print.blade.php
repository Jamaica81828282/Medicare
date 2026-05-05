<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Invoice {{ $invoice->invoice_number }} — MediCare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/invoice.css') }}">
</head>
<body>

<div class="screen-wrap">

  <div class="page">

    {{-- ── Header ── --}}
    <div class="inv-header">
      <div class="pharmacy-name">{{ $settings['pharmacy_name'] ?? 'MediCare Pharmacy' }}</div>
      <div class="pharmacy-tag">{{ $settings['pharmacy_tagline'] ?? 'Your Health, Our Priority' }}</div>
      <div class="pharmacy-sub">{{ $settings['address_line1'] ?? '' }}</div>
      <div class="pharmacy-sub">{{ $settings['address_line2'] ?? '' }}</div>
      <div class="pharmacy-sub">Tel: {{ $settings['phone'] ?? '' }}</div>
      @if(($settings['vat_registered'] ?? '') === 'true')
        <div class="pharmacy-sub">VAT Reg: {{ $settings['vat_number'] ?? '' }}</div>
      @endif
    </div>

    {{-- ── Badge Bar ── --}}
    <div class="inv-badge-bar">
      <div>
        <div class="badge-label">Invoice</div>
        <div class="badge-value">{{ $invoice->invoice_number }}</div>
      </div>
      <div style="text-align:right;">
        <div class="badge-label">Date</div>
        <div class="badge-date">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M j, Y h:i A') }}</div>
      </div>
    </div>

    {{-- ── Body ── --}}
    <div class="inv-body">

      {{-- Meta grid --}}
      <div class="meta-grid">
        <div>
          <div class="meta-label">Cashier</div>
          <div class="meta-value">{{ $invoice->cashier_name ?? '—' }}</div>
        </div>
        <div>
          <div class="meta-label">Payment</div>
          <div class="meta-value">{{ $invoice->payment_method_name ?? '—' }}</div>
        </div>
        @if($invoice->prescription_no)
        <div style="margin-top:4px;">
          <div class="meta-label">Rx No.</div>
          <div class="meta-value">{{ $invoice->prescription_no }}</div>
        </div>
        @endif
      </div>

      <hr class="inv-divider">

      {{-- Customer --}}
      <div class="section-label">Customer</div>
      <div class="customer-name">{{ $invoice->customer_name ?? 'Walk-in Customer' }}</div>
      @if($invoice->customer_phone)
        <div class="customer-sub">{{ $invoice->customer_phone }}</div>
      @endif
      @if($invoice->customer_address)
        <div class="customer-sub">{{ $invoice->customer_address }}</div>
      @endif
      @if($invoice->is_senior)
        <span class="badge-pill badge-senior">Senior Citizen</span>
      @endif
      @if($invoice->is_pwd)
        <span class="badge-pill badge-pwd">Person with Disability</span>
      @endif

      {{-- Items --}}
      <div class="items-section">
        <div class="section-label" style="margin-top:14px;">Items</div>
        <table class="inv-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->items as $item)
            <tr>
              <td>
                <div class="i-name-main">{{ $item->product_name }}</div>
                @if($item->generic_name)
                  <div class="i-name-sub">{{ $item->generic_name }}</div>
                @endif
              </td>
              <td>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
              <td>₱{{ number_format($item->unit_price, 2) }}</td>
              <td>₱{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Totals --}}
      <div class="totals-box">
        <div class="total-row"><span>Subtotal</span><span>₱{{ number_format($invoice->subtotal, 2) }}</span></div>
        @foreach($invoice->discounts as $disc)
          <div class="total-row discount">
            <span>{{ $disc->discount_name }} ({{ $disc->discount_method === 'percentage' ? $disc->discount_value.'%' : '₱'.number_format($disc->discount_value,2) }})</span>
            <span>— ₱{{ number_format($disc->discount_amount, 2) }}</span>
          </div>
          @if($invoice->is_senior || $invoice->is_pwd)
            <div class="total-row vat-note"><span>(VAT-exempt)</span><span></span></div>
          @endif
        @endforeach
        <div class="total-row"><span>VAT (12%)</span><span>₱{{ number_format($invoice->total_tax, 2) }}</span></div>
      </div>

      <div class="grand-row">
        <div class="grand-label">TOTAL</div>
        <div class="grand-amt">₱{{ number_format($invoice->grand_total, 2) }}</div>
      </div>

      @if($invoice->amount_tendered)
        <div class="cash-section">
          <div class="cash-row"><span>Cash Tendered</span><span>₱{{ number_format($invoice->amount_tendered, 2) }}</span></div>
        </div>
        <div style="padding: 0 14px;">
          <div class="change-row">
            <span>Change</span>
            <span>₱{{ number_format(max(0, $invoice->change_amount), 2) }}</span>
          </div>
        </div>
      @endif

    </div>{{-- /inv-body --}}

    {{-- Footer --}}
    <div class="inv-footer">
      <div class="footer-main">{{ $settings['invoice_footer_note'] ?? 'Thank you for choosing us!' }}</div>
      <div class="footer-sub">
        This serves as your official receipt.<br>
        {{ $settings['email'] ?? '' }}
      </div>
      <div class="footer-end">— END OF RECEIPT —</div>
    </div>

  </div>{{-- /page --}}

  {{-- Action buttons (hidden on print) --}}
  <div class="print-actions">
    <a href="{{ route('cashier.dashboard') }}" class="btn-back">← Back</a>
    <button class="btn-print" onclick="window.print()">🖨 Print Receipt</button>
  </div>

</div>{{-- /screen-wrap --}}

</body>
</html>