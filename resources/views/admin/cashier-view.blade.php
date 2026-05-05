<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Viewing {{ $cashier->name }} — MediCare</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: #f5f6fa; color: #1a1d2e; overflow: hidden; height: 100vh; }
.material-symbols-rounded { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: -4px; }
.icon-fill { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #dde1ec; border-radius: 10px; }
.topbar { height: 64px; background: #ffffff; border-bottom: 1px solid #eaecf4; display: flex; align-items: center; justify-content: space-between; padding: 0 28px; flex-shrink: 0; }
.content { flex: 1; overflow-y: auto; padding: 28px; }
.stat-card { background: white; border-radius: 16px; padding: 22px 24px; border: 1px solid #eaecf4; position: relative; overflow: hidden; }
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 16px 16px 0 0; }
.stat-card.blue::before  { background: #3d52d5; }
.stat-card.green::before { background: #16a34a; }
.stat-card.amber::before { background: #d97706; }
.stat-card.rose::before  { background: #e11d48; }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
.stat-icon.blue  { background: #eef1ff; color: #3d52d5; }
.stat-icon.green { background: #f0fdf4; color: #16a34a; }
.stat-icon.amber { background: #fffbeb; color: #d97706; }
.stat-icon.rose  { background: #fff1f2; color: #e11d48; }
.stat-val { font-size: 28px; font-weight: 700; color: #1a1d2e; line-height: 1; }
.stat-lbl { font-size: 12px; color: #8891b4; margin-top: 4px; font-weight: 500; }
.btn-back { height: 44px; padding: 0 20px; border-radius: 10px; background: white; border: 1.5px solid #eaecf4; color: #1a1d2e; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
.btn-back:hover { border-color: #a5b4fc; color: #3d52d5; }
</style>
</head>
<body>
<div style="display:flex; height:100vh; overflow:hidden; flex-direction:column;">

  <div class="topbar">
    <div>
      <div style="font-size:18px;font-weight:700;color:#1a1d2e;">Viewing: {{ $cashier->name }}</div>
      <div style="font-size:12px;color:#8891b4;">Cashier Dashboard — {{ now()->format('l, F j, Y') }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="background:#eef1ff;border:1px solid #c7d2fe;border-radius:10px;padding:8px 16px;font-size:13px;color:#3d52d5;font-weight:600;">
        Admin Viewing Mode
      </div>
      <a href="{{ route('admin.dashboard') }}" class="btn-back">
        <span class="material-symbols-rounded" style="font-size:18px;">arrow_back</span>Back to Admin
      </a>
    </div>
  </div>

  <div class="content">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div class="stat-card blue">
        <div class="stat-icon blue"><span class="material-symbols-rounded icon-fill">receipt_long</span></div>
        <div class="stat-val">{{ $stats['today_invoices'] }}</div>
        <div class="stat-lbl">Today's Invoices</div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon green"><span class="material-symbols-rounded icon-fill">payments</span></div>
        <div class="stat-val">₱{{ number_format($stats['today_revenue'], 0) }}</div>
        <div class="stat-lbl">Today's Revenue (Paid)</div>
      </div>
      <div class="stat-card amber">
        <div class="stat-icon amber"><span class="material-symbols-rounded icon-fill">cancel</span></div>
        <div class="stat-val">{{ $stats['today_void'] }}</div>
        <div class="stat-lbl">Today's Voided</div>
      </div>
      <div class="stat-card rose">
        <div class="stat-icon rose"><span class="material-symbols-rounded icon-fill">trending_up</span></div>
        <div class="stat-val">{{ $stats['month_invoices'] }}</div>
        <div class="stat-lbl">This Month's Invoices</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:24px;">
      <div style="background:white;border-radius:16px;border:1px solid #eaecf4;padding:24px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:20px;color:#1a1d2e;">
          <span class="material-symbols-rounded" style="font-size:20px;vertical-align:-4px;margin-right:8px;color:#3d52d5;">info</span>
          About This View
        </div>
        <div style="font-size:13px;color:#6b7494;line-height:1.6;display:flex;flex-direction:column;gap:12px;">
          <div>
            <strong style="color:#1a1d2e;">Cashier Name:</strong><br>{{ $cashier->name }}
          </div>
          <div>
            <strong style="color:#1a1d2e;">Email:</strong><br>{{ $cashier->email }}
          </div>
          <div>
            <strong style="color:#1a1d2e;">Total Paid Invoices:</strong><br>{{ $stats['total_paid_invoices'] }}
          </div>
          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px;font-size:12px;color:#166534;margin-top:8px;">
            <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-4px;">info</span>
            This is a read-only view of this cashier's performance and activity. You cannot edit or process transactions from here.
          </div>
        </div>
      </div>

      <div style="background:white;border-radius:16px;border:1px solid #eaecf4;padding:24px;">
        <div style="font-size:15px;font-weight:700;margin-bottom:20px;color:#1a1d2e;">
          <span class="material-symbols-rounded" style="font-size:20px;vertical-align:-4px;margin-right:8px;color:#16a34a;">bar_chart</span>
          Monthly Overview
        </div>
        <div style="font-size:13px;color:#6b7494;line-height:1.8;display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;justify-content:space-between;">
            <span>Total Invoices:</span>
            <strong style="color:#1a1d2e;">{{ $stats['month_invoices'] }}</strong>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span>Total Revenue:</span>
            <strong style="color:#16a34a;">₱{{ number_format($stats['month_revenue'], 2) }}</strong>
          </div>
          <div style="padding-top:12px;border-top:1px solid #eaecf4;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#8891b4;">
              <span>Month:</span>
              <span>{{ now()->format('F Y') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(!empty($shiftReport['top_products']) && $shiftReport['top_products']->count() > 0)
    <div style="background:white;border-radius:16px;border:1px solid #eaecf4;overflow:hidden;">
      <div style="padding:16px 20px;border-bottom:1px solid #eaecf4;font-size:13px;font-weight:700;color:#1a1d2e;">Top 5 Products Sold Today</div>
      <table class="rtable" style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid #eaecf4;">
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:700;color:#8891b4;background:#f9fafb;">#</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:700;color:#8891b4;background:#f9fafb;">Product</th>
            <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:700;color:#8891b4;background:#f9fafb;">Units Sold</th>
          </tr>
        </thead>
        <tbody>
          @foreach($shiftReport['top_products'] as $i => $prod)
          <tr style="border-bottom:1px solid #eaecf4;">
            <td style="padding:12px 16px;color:#8891b4;font-weight:700;">{{ $i+1 }}</td>
            <td style="padding:12px 16px;font-weight:600;">{{ $prod->product_name }}</td>
            <td style="padding:12px 16px;"><span style="background:#eef1ff;color:#3d52d5;font-weight:700;padding:4px 12px;border-radius:6px;font-size:12px;">{{ number_format($prod->qty_sold) }} units</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:40px;text-align:center;color:#8891b4;">
      <div style="font-size:14px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">No sales yet today</div>
      <div style="font-size:13px;">Top products will appear after transactions are completed</div>
    </div>
    @endif
  </div>

</div>
</body>
</html>
