<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Invoice {{ $invoice->invoice_number }} — MediCare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --ink:       #1a1a1a;
    --ink-mid:   #444444;
    --ink-light: #888888;
    --ink-faint: #cccccc;
    --accent:    #1d6b4f;
    --accent-bg: #edf6f1;
    --warn:      #b84c3a;
    --paper:     #ffffff;
    --receipt-w: 420px;
  }

  body {
    background: #f0ede8;
    font-family: 'DM Sans', sans-serif;
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 16px 80px;
  }

  /* ── Receipt Shell ── */
  .receipt {
    width: var(--receipt-w);
    max-width: 100%;
    background: var(--paper);
    position: relative;
    filter: drop-shadow(0 8px 32px rgba(0,0,0,0.13));
  }

  /* Zigzag top edge */
  .receipt::before {
    content: '';
    display: block;
    height: 16px;
    background:
      linear-gradient(135deg, #f0ede8 33.33%, transparent 33.33%) 0 0,
      linear-gradient(225deg, #f0ede8 33.33%, transparent 33.33%) 0 0;
    background-size: 16px 16px;
    background-color: var(--paper);
    background-repeat: repeat-x;
  }

  /* Zigzag bottom edge */
  .receipt::after {
    content: '';
    display: block;
    height: 16px;
    background:
      linear-gradient(315deg, #f0ede8 33.33%, transparent 33.33%) 0 0,
      linear-gradient(45deg,  #f0ede8 33.33%, transparent 33.33%) 0 0;
    background-size: 16px 16px;
    background-color: var(--paper);
    background-repeat: repeat-x;
  }

  .receipt-inner {
    padding: 4px 32px 28px;
  }

  /* ── Header ── */
  .r-header {
    text-align: center;
    padding: 24px 0 20px;
    border-bottom: 1px dashed var(--ink-faint);
  }

  .r-cross {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    background: var(--accent);
    border-radius: 50%;
    margin-bottom: 10px;
  }
  .r-cross svg { display: block; }

  .r-pharmacy {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -0.3px;
    line-height: 1.2;
  }

  .r-tagline {
    font-size: 10px;
    font-weight: 500;
    color: var(--accent);
    letter-spacing: 2.5px;
    text-transform: uppercase;
    margin: 4px 0 8px;
  }

  .r-sub {
    font-size: 11.5px;
    color: var(--ink-light);
    line-height: 1.6;
    font-weight: 400;
  }

  /* ── Badge bar ── */
  .r-badge-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--accent-bg);
    border-left: 3px solid var(--accent);
    border-radius: 0 6px 6px 0;
    padding: 10px 14px;
    margin: 18px 0 16px;
  }

  .badge-lbl {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 2px;
  }

  .badge-inv {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    font-weight: 500;
    color: var(--ink);
  }

  .badge-date {
    font-size: 11.5px;
    color: var(--ink-mid);
    text-align: right;
  }

  /* ── Section label ── */
  .sec-label {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-light);
    margin-bottom: 6px;
  }

  /* ── Meta row ── */
  .meta-grid {
    display: flex;
    gap: 24px;
    margin-bottom: 16px;
  }
  .meta-val {
    font-size: 12.5px;
    font-weight: 500;
    color: var(--ink);
  }

  /* ── Divider ── */
  .r-divider {
    border: none;
    border-top: 1px dashed var(--ink-faint);
    margin: 14px 0;
  }

  /* ── Customer ── */
  .cust-name {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 2px;
  }
  .cust-sub {
    font-size: 11.5px;
    color: var(--ink-light);
    line-height: 1.6;
  }

  .pill {
    display: inline-block;
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    border-radius: 20px;
    padding: 3px 10px;
    margin-top: 6px;
    margin-right: 4px;
  }
  .pill-senior { background: #fff3e0; color: #b36200; }
  .pill-pwd    { background: #e8f0fe; color: #1a56c4; }

  /* ── Items table ── */
  .items-wrap { margin: 14px 0; }

  .inv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }

  .inv-table thead tr {
    border-bottom: 1.5px solid var(--ink);
  }

  .inv-table th {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--ink-mid);
    padding: 0 0 6px;
    text-align: left;
  }
  .inv-table th:not(:first-child) { text-align: right; }

  .inv-table tbody tr {
    border-bottom: 1px dashed var(--ink-faint);
  }
  .inv-table tbody tr:last-child { border-bottom: none; }

  .inv-table td {
    padding: 8px 0;
    vertical-align: top;
    color: var(--ink);
  }
  .inv-table td:not(:first-child) {
    text-align: right;
    white-space: nowrap;
  }

  .i-main { font-weight: 500; font-size: 12.5px; }
  .i-sub  { font-size: 10.5px; color: var(--ink-light); margin-top: 1px; font-style: italic; }

  /* ── Totals ── */
  .totals-box {
    background: #fafafa;
    border-radius: 6px;
    border: 1px solid var(--ink-faint);
    padding: 12px 14px;
    margin-top: 14px;
  }

  .t-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--ink-mid);
    padding: 3px 0;
  }
  .t-row.discount { color: var(--warn); }
  .t-row.vat-note { font-size: 10px; color: var(--ink-light); font-style: italic; }

  /* ── Grand total ── */
  .grand-box {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    background: var(--accent);
    border-radius: 6px;
    padding: 12px 14px;
    margin-top: 8px;
  }
  .grand-lbl {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.75);
  }
  .grand-amt {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: #ffffff;
  }

  /* ── Cash section ── */
  .cash-box {
    margin-top: 10px;
    padding: 10px 14px;
    border-radius: 6px;
    border: 1px dashed var(--ink-faint);
  }
  .cash-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--ink-mid);
    padding: 3px 0;
  }
  .change-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    border-top: 1px solid var(--ink-faint);
    margin-top: 6px;
    padding-top: 6px;
  }

  /* ── Footer ── */
  .r-footer {
    text-align: center;
    padding: 20px 0 8px;
    border-top: 1px dashed var(--ink-faint);
    margin-top: 20px;
  }
  .footer-main {
    font-family: 'Playfair Display', serif;
    font-size: 13px;
    font-style: italic;
    color: var(--ink-mid);
    margin-bottom: 6px;
  }
  .footer-sub {
    font-size: 10.5px;
    color: var(--ink-light);
    line-height: 1.7;
  }
  .footer-end {
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--ink-faint);
    margin-top: 12px;
  }

  /* ── Barcode-style decoration ── */
  .barcode {
    display: flex;
    justify-content: center;
    gap: 2px;
    margin: 16px 0 4px;
  }
  .barcode span {
    display: inline-block;
    height: 28px;
    background: var(--ink);
    border-radius: 1px;
  }

  /* ── Print actions ── */
  .print-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    justify-content: center;
  }

  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    background: #fff;
    color: var(--ink);
    border: 1.5px solid var(--ink-faint);
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-back:hover { border-color: var(--ink-mid); }

  .btn-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 22px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    background: var(--accent);
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-print:hover { background: #175a40; }

  @media print {
    body { background: white; padding: 0; }
    .receipt { filter: none; width: 100%; max-width: 320px; }
    .print-actions { display: none; }
  }
</style>
</head>
<body>

<div class="receipt">
  <div class="receipt-inner">

    {{-- ── Header ── --}}
    <div class="r-header">
      <div class="r-cross">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <rect x="7" y="2" width="4" height="14" rx="1.5" fill="white"/>
          <rect x="2" y="7" width="14" height="4" rx="1.5" fill="white"/>
        </svg>
      </div>
      <div class="r-pharmacy">{{ $settings['pharmacy_name'] ?? 'MediCare Pharmacy' }}</div>
      <div class="r-tagline">{{ $settings['pharmacy_tagline'] ?? 'Your Health, Our Priority' }}</div>
      <div class="r-sub">
        {{ $settings['address_line1'] ?? '' }}<br>
        {{ $settings['address_line2'] ?? '' }}<br>
        Tel: {{ $settings['phone'] ?? '' }}
        @if(($settings['vat_registered'] ?? '') === 'true')
          &nbsp;·&nbsp; VAT Reg: {{ $settings['vat_number'] ?? '' }}
        @endif
      </div>
    </div>

    {{-- ── Badge bar ── --}}
    <div class="r-badge-bar">
      <div>
        <div class="badge-lbl">Invoice No.</div>
        <div class="badge-inv">{{ $invoice->invoice_number }}</div>
      </div>
      <div>
        <div class="badge-lbl" style="text-align:right;">Date</div>
        <div class="badge-date">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M j, Y h:i A') }}</div>
      </div>
    </div>

    {{-- ── Meta ── --}}
    <div class="meta-grid">
      <div>
        <div class="sec-label">Cashier</div>
        <div class="meta-val">{{ $invoice->cashier_name ?? '—' }}</div>
      </div>
      <div>
        <div class="sec-label">Payment</div>
        <div class="meta-val">{{ $invoice->payment_method_name ?? '—' }}</div>
      </div>
      @if($invoice->prescription_no)
      <div>
        <div class="sec-label">Rx No.</div>
        <div class="meta-val">{{ $invoice->prescription_no }}</div>
      </div>
      @endif
    </div>

    <hr class="r-divider">

    {{-- ── Customer ── --}}
    <div class="sec-label">Customer</div>
    <div class="cust-name">{{ $invoice->customer_name ?? 'Walk-in Customer' }}</div>
    @if($invoice->customer_phone)
      <div class="cust-sub">{{ $invoice->customer_phone }}</div>
    @endif
    @if($invoice->customer_address)
      <div class="cust-sub">{{ $invoice->customer_address }}</div>
    @endif
    @if($invoice->is_senior)
      <span class="pill pill-senior">Senior Citizen</span>
    @endif
    @if($invoice->is_pwd)
      <span class="pill pill-pwd">PWD</span>
    @endif

    <hr class="r-divider">

    {{-- ── Items ── --}}
    <div class="sec-label">Items Purchased</div>
    <div class="items-wrap">
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
              <div class="i-main">{{ $item->product_name }}</div>
              @if($item->generic_name)
                <div class="i-sub">{{ $item->generic_name }}</div>
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

    {{-- ── Totals ── --}}
    <div class="totals-box">
      <div class="t-row"><span>Subtotal</span><span>₱{{ number_format($invoice->subtotal, 2) }}</span></div>
      @foreach($invoice->discounts as $disc)
        <div class="t-row discount">
          <span>{{ $disc->discount_name }} ({{ $disc->discount_method === 'percentage' ? $disc->discount_value.'%' : '₱'.number_format($disc->discount_value,2) }})</span>
          <span>— ₱{{ number_format($disc->discount_amount, 2) }}</span>
        </div>
        @if($invoice->is_senior || $invoice->is_pwd)
          <div class="t-row vat-note"><span>VAT-exempt applied</span><span></span></div>
        @endif
      @endforeach
      <div class="t-row"><span>VAT (12%)</span><span>₱{{ number_format($invoice->total_tax, 2) }}</span></div>
    </div>

    <div class="grand-box">
      <div class="grand-lbl">Total Due</div>
      <div class="grand-amt">₱{{ number_format($invoice->grand_total, 2) }}</div>
    </div>

    @if($invoice->amount_tendered)
    <div class="cash-box">
      <div class="cash-row"><span>Cash Tendered</span><span>₱{{ number_format($invoice->amount_tendered, 2) }}</span></div>
      <div class="change-row"><span>Change</span><span>₱{{ number_format(max(0, $invoice->change_amount), 2) }}</span></div>
    </div>
    @endif

    {{-- ── Barcode decoration ── --}}
    <div class="barcode">
      @php
        $bars = [2,1,3,1,2,4,1,2,1,3,2,1,4,1,2,3,1,2,1,3,2,1,2,4,1,3,1,2,1,3,2,4,1,2,1];
        foreach($bars as $w) echo "<span style='width:{$w}px'></span>";
      @endphp
    </div>
    <div style="text-align:center; font-family:'DM Mono',monospace; font-size:9px; color:var(--ink-faint); letter-spacing:2px;">
      {{ $invoice->invoice_number }}
    </div>

    {{-- ── Footer ── --}}
    <div class="r-footer">
      <div class="footer-main">"{{ $settings['invoice_footer_note'] ?? 'Thank you for choosing us!' }}"</div>
      <div class="footer-sub">
        This serves as your official receipt.<br>
        {{ $settings['email'] ?? '' }}
      </div>
      <div class="footer-end">— End of Receipt —</div>
    </div>

  </div>
</div>

{{-- ── Action buttons ── --}}
<div class="print-actions">
  <a href="{{ route('cashier.dashboard') }}" class="btn-back">← Back</a>
  <button class="btn-print" onclick="window.print()">🖨 Print Receipt</button>
</div>

</body>
</html>