<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Cashier Dashboard — MediCare</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/cashier.css') }}">
</head>
<body>
<div style="display:flex; height:100vh; overflow:hidden;">

  {{-- ════════════════════════════════ SIDEBAR ════════════════════════════════ --}}
  <aside class="sidebar">
    <div style="padding: 20px 20px 16px; border-bottom: 1px solid #eaecf4;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:36px;height:36px;background:#3d52d5;border-radius:10px;display:flex;align-items:center;justify-content:center;">
          <span class="material-symbols-rounded icon-fill" style="color:white;font-size:20px;">medication</span>
        </div>
        <div>
          <div style="font-size:15px;font-weight:700;color:#1a1d2e;line-height:1;">MediCare</div>
          <div style="font-size:10px;color:#8891b4;letter-spacing:.06em;">CASHIER STATION</div>
        </div>
      </div>
    </div>

    <nav style="flex:1; padding: 10px 0; overflow-y:auto;">

      <div class="nav-group-label">Queue</div>
 
      <button class="nav-item active" onclick="switchSection('pending', this)">
        <span class="material-symbols-rounded">inbox</span>
        Pending Orders
        @if($stats['pending'] > 0)
          <span class="nav-badge">{{ $stats['pending'] }}</span>
        @endif
      </button>
 
      {{-- NEW: Queue / Pickup section --}}
      <button class="nav-item" onclick="switchSection('queue', this)" id="navQueue">
        <span class="material-symbols-rounded">local_pharmacy</span>
        Ready for Pickup
        <span class="nav-badge" id="queueNavBadge" style="display:none;">0</span>
      </button>

      <button class="nav-item" onclick="switchSection('completed', this)">
        <span class="material-symbols-rounded">check_circle</span>
        Today's Sales
      </button>

      <button class="nav-item" onclick="switchSection('search', this)">
        <span class="material-symbols-rounded">search</span>
        Search Invoice
        <span class="nav-tag green">useful</span>
      </button>

      {{-- ── INVENTORY ── --}}
      <div class="nav-group-label">Inventory</div>

      {{-- NEW: Inventory search (read-only) --}}
      <button class="nav-item" onclick="switchSection('inv-search', this)">
        <span class="material-symbols-rounded">medication_liquid</span>
        Product Lookup
        <span class="nav-tag green">new</span>
      </button>

      <button class="nav-item" onclick="switchSection('lowstock', this)">
        <span class="material-symbols-rounded">inventory_2</span>
        Low Stock Alerts
        @if(isset($stats['low_stock']) && $stats['low_stock'] > 0)
          <span class="nav-badge">{{ $stats['low_stock'] }}</span>
        @endif
      </button>

      <button class="nav-item" onclick="switchSection('expiring', this)">
        <span class="material-symbols-rounded">schedule</span>
        Expiring Soon
        @if(isset($stats['expiring_soon']) && $stats['expiring_soon'] > 0)
          <span class="nav-badge amber">{{ $stats['expiring_soon'] }}</span>
        @endif
      </button>

      <div class="nav-group-label">Reports</div>

      <button class="nav-item" onclick="switchSection('sales-summary', this)">
        <span class="material-symbols-rounded">bar_chart</span>
        Sales Summary
      </button>

      <button class="nav-item" onclick="switchSection('shift', this)">
        <span class="material-symbols-rounded">summarize</span>
        Shift Report
        <span class="nav-tag amber">end of day</span>
      </button>

      <div class="nav-group-label">Account</div>

      <form method="POST" action="{{ route('logout') }}" style="margin:0;">
        @csrf
        <button type="submit" class="nav-item" style="color:#dc2626;">
          <span class="material-symbols-rounded">logout</span>
          Sign Out
        </button>
      </form>
    </nav>

    <div style="padding: 16px 20px; border-top: 1px solid #eaecf4;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;background:#eef1ff;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#3d52d5;">
          <span class="material-symbols-rounded icon-fill">account_circle</span>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#1a1d2e;">{{ Auth::user()->name }}</div>
          <div style="font-size:11px;color:#8891b4;">Cashier</div>
        </div>
      </div>
    </div>
  </aside>

  {{-- ════════════════════════════════ MAIN ════════════════════════════════ --}}
  <div class="main">

    <div class="topbar">
      <div>
        <div style="font-size:18px;font-weight:700;color:#1a1d2e;" id="topTitle">Pending Orders</div>
        <div style="font-size:12px;color:#8891b4;">{{ now()->format('l, F j, Y') }}</div>
      </div>
      <div style="display:flex;align-items:center;gap:16px;">
        <div style="display:flex;gap:20px;">
          <div style="text-align:right;">
            <div style="font-size:18px;font-weight:700;color:#1a1d2e;">₱{{ number_format($stats['today_sales'],2) }}</div>
            <div style="font-size:11px;color:#8891b4;">Today's Sales</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:18px;font-weight:700;color:#1a1d2e;">{{ $stats['today_count'] }}</div>
            <div style="font-size:11px;color:#8891b4;">Transactions</div>
          </div>
        </div>
        <div style="background:#f5f6fa;padding:8px 16px;border-radius:10px;font-family:'DM Mono',monospace;font-size:16px;font-weight:500;color:#1a1d2e;min-width:90px;text-align:center;" id="clock"></div>
      </div>
    </div>

    <div style="flex:1;overflow:hidden;display:flex;">

      <div class="content" style="flex:1;" id="mainContent">

        {{-- ══════════════════ SECTION: PENDING ══════════════════ --}}
        <div class="section active" id="section-pending">
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
            <div class="stat-card blue">
              <div class="stat-icon blue"><span class="material-symbols-rounded icon-fill">inbox</span></div>
              <div class="stat-val">{{ $stats['pending'] }}</div>
              <div class="stat-lbl">Pending Orders</div>
            </div>
            <div class="stat-card green">
              <div class="stat-icon green"><span class="material-symbols-rounded icon-fill">payments</span></div>
              <div class="stat-val">₱{{ number_format($stats['today_sales'],0) }}</div>
              <div class="stat-lbl">Today's Revenue</div>
            </div>
            <div class="stat-card amber">
              <div class="stat-icon amber"><span class="material-symbols-rounded icon-fill">receipt_long</span></div>
              <div class="stat-val">{{ $stats['today_count'] }}</div>
              <div class="stat-lbl">Paid Invoices</div>
            </div>
            <div class="stat-card rose">
              <div class="stat-icon rose"><span class="material-symbols-rounded icon-fill">prescriptions</span></div>
              <div class="stat-val">{{ $stats['today_rx'] }}</div>
              <div class="stat-lbl">Rx Dispensed</div>
            </div>
          </div>

          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="font-size:14px;font-weight:700;color:#1a1d2e;">Queue <span style="color:#8891b4;font-weight:500;font-size:13px;">({{ $pendingOrders->count() }} waiting)</span></div>
            <button onclick="refreshPage()" style="display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #eaecf4;border-radius:8px;background:white;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;color:#6b7494;cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='#a5b4fc';this.style.color='#3d52d5'" onmouseout="this.style.borderColor='#eaecf4';this.style.color='#6b7494'">
              <span class="material-symbols-rounded" style="font-size:16px;">refresh</span> Refresh
            </button>
          </div>

          @if($pendingOrders->isEmpty())
            <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">
              <span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">inbox</span>
              <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">No pending orders</div>
              <div style="font-size:13px;">Kiosk orders will appear here as customers place them</div>
            </div>
          @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;" id="ordersGrid">
              @foreach($pendingOrders as $order)
              @php $isHold = false; // All pending orders are ready to process @endphp
              <div class="order-card"
                   id="card-{{ $order->id }}"
                   onclick="selectOrder({{ $order->id }})">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                  <div class="oc-inv">{{ $order->invoice_number }}</div>
                  <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;">
                    @if($order->is_senior) <span class="badge-sc">Senior</span> @endif
                    @if($order->is_pwd)    <span class="badge-pwd">PWD</span>   @endif
                    @foreach($order->items as $item)
                      @if(isset($item->requires_rx) && $item->requires_rx)
                        <span class="badge-rx"><span class="rx-dot"></span>Rx</span>
                        @break
                      @endif
                    @endforeach
                  </div>
                </div>
                <div class="oc-name">{{ $order->customer_name ?: 'Walk-in Customer' }}</div>
                <div class="oc-meta">{{ $order->phone ?? '—' }} &middot; {{ $order->items->count() }} item(s)</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid #f5f6fa;">
                  <div>
                    <div class="oc-total">₱{{ number_format($order->grand_total,2) }}</div>
                    <div class="oc-items">incl. VAT</div>
                  </div>
                  <div style="font-size:11px;color:#8891b4;">{{ \Carbon\Carbon::parse($order->created_at)->format('h:i A') }}</div>
                </div>
              </div>
              @endforeach
            </div>
          @endif
        </div>

       {{-- ══════════════════ SECTION: TODAY'S SALES ══════════════════ --}}
<div class="section" id="section-completed">
<style>
.tx-card{background:white;border:1px solid #eaecf4;border-radius:14px;padding:16px 18px;cursor:pointer;transition:border-color .15s,background .15s;margin-bottom:10px;}
.tx-card:hover{border-color:#c7cde8;background:#fafbff;}
.tx-card:hover .inv-num{color:#3d52d5;}
.tx-avatar{width:40px;height:40px;border-radius:50%;background:#eef1ff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#3d52d5;flex-shrink:0;font-family:'DM Mono',monospace;}
.inv-num{font-family:'DM Mono',monospace;font-size:13px;font-weight:600;color:#3d52d5;letter-spacing:.02em;transition:color .15s;}
.badge-pay{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;}
.badge-cash{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.badge-gcash{background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;}
.badge-card{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}
.badge-paid-status{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;}
</style>

  {{-- Mini Stats --}}
  @php
    $txTotal   = $recentPaid->sum('grand_total');
    $txCount   = $recentPaid->count();
    $txAvg     = $txCount ? $txTotal / $txCount : 0;
  @endphp
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
    <div style="background:white;border:1px solid #eaecf4;border-radius:12px;padding:14px 18px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#b0b8d4;margin-bottom:6px;">Total Revenue</div>
      <div style="font-size:22px;font-weight:900;color:#0f1117;letter-spacing:-.03em;font-family:'DM Mono',monospace;">₱{{ number_format($txTotal,2) }}</div>
    </div>
    <div style="background:white;border:1px solid #eaecf4;border-radius:12px;padding:14px 18px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#b0b8d4;margin-bottom:6px;">Transactions</div>
      <div style="font-size:22px;font-weight:900;color:#0f1117;letter-spacing:-.03em;font-family:'DM Mono',monospace;">{{ $txCount }}</div>
    </div>
    <div style="background:white;border:1px solid #eaecf4;border-radius:12px;padding:14px 18px;">
      <div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#b0b8d4;margin-bottom:6px;">Avg. Order</div>
      <div style="font-size:22px;font-weight:900;color:#0f1117;letter-spacing:-.03em;font-family:'DM Mono',monospace;">₱{{ number_format($txAvg,2) }}</div>
    </div>
  </div>

  {{-- Cards --}}
  @forelse($recentPaid as $inv)
  @php
    $initials = collect(explode(' ', trim($inv->customer_name)))
                  ->filter()->map(fn($w) => strtoupper($w[0]))->take(2)->join('');
    $initials = $initials ?: 'WI';
    $payBadgeClass = match(strtolower($inv->payment_method_name ?? '')) {
      'gcash'       => 'badge-gcash',
      'credit card',
      'debit card'  => 'badge-card',
      default       => 'badge-cash',
    };
  @endphp
  <div class="tx-card" onclick="window.open('{{ route('cashier.invoice.print', $inv->id) }}','_blank')">
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="tx-avatar">{{ $initials }}</div>
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
          <span class="inv-num">{{ $inv->invoice_number }}</span>
          <span class="badge-paid-status">
            <span class="material-symbols-rounded" style="font-size:12px;">check_circle</span>Paid
          </span>
          <span class="badge-pay {{ $payBadgeClass }}">{{ $inv->payment_method_name ?: 'Cash' }}</span>
        </div>
        <div style="font-size:15px;font-weight:700;color:#0f1117;margin-top:4px;">
          {{ trim($inv->customer_name) ?: 'Walk-in' }}
        </div>
      
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div style="font-size:19px;font-weight:900;color:#0f1117;font-family:'DM Mono',monospace;letter-spacing:-.02em;">
          ₱{{ number_format($inv->grand_total,2) }}
        </div>
        <div style="font-size:11.5px;color:#9aa3c2;margin-top:2px;">
          {{ $inv->items_count }} {{ $inv->items_count == 1 ? 'item' : 'items' }}
          · {{ \Carbon\Carbon::parse($inv->updated_at)->format('h:i A') }}
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:1px solid #f0f2f8;">
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#9aa3c2;">
        <span class="material-symbols-rounded" style="font-size:14px;">person</span>
        {{ Auth::user()->name }}
        @if($inv->prescription_no)
          <span style="margin:0 4px;color:#e2e4ef;">|</span>
          <span class="material-symbols-rounded" style="font-size:14px;">receipt</span>
          Rx: {{ $inv->prescription_no }}
        @endif
      </div>
      <a href="{{ route('cashier.invoice.print', $inv->id) }}" target="_blank"
        onclick="event.stopPropagation()"
        style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#3d52d5;text-decoration:none;padding:5px 12px;border:1.5px solid #a5b4fc;border-radius:8px;transition:all .15s;"
        onmouseover="this.style.background='#eef1ff'" onmouseout="this.style.background='transparent'">
        <span class="material-symbols-rounded" style="font-size:14px;">print</span> Print
      </a>
    </div>
  </div>
  @empty
  <div style="background:white;border:1px solid #eaecf4;border-radius:14px;padding:56px 20px;text-align:center;">
    <span class="material-symbols-rounded" style="font-size:40px;color:#d4d8ea;">receipt_long</span>
    <div style="font-size:13px;font-weight:700;color:#6b7494;margin-top:10px;">No transactions today yet</div>
    <div style="font-size:11px;color:#9aa3c2;margin-top:3px;">Paid invoices will appear here</div>
  </div>
  @endforelse

</div>
        {{-- ══════════════════ SECTION: SEARCH INVOICE ══════════════════ --}}
        <div class="section" id="section-search">
          <div style="margin-bottom:20px;">
            <div style="font-size:14px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Search Invoice</div>
            <div style="font-size:13px;color:#8891b4;">Search by invoice number, customer name, or phone</div>
          </div>

          <div class="search-wrap">
            <span class="material-symbols-rounded">search</span>
            <input type="text" id="searchInput" placeholder="e.g. INV-2026-00001 or Juan dela Cruz…"
              oninput="debounceSearch(this.value)" autocomplete="off"/>
          </div>

          <div id="searchResults">
            <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;" id="searchEmpty">
              <span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">manage_search</span>
              <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">Start typing to search</div>
              <div style="font-size:13px;">Results will appear here</div>
            </div>
            <div id="searchLoading" style="display:none;padding:40px;text-align:center;color:#8891b4;">
              <span class="material-symbols-rounded" style="font-size:32px;animation:spin 1s linear infinite;display:block;margin-bottom:8px;">sync</span>
              Searching…
            </div>
            <div id="searchTable" style="display:none;">
              <div style="background:white;border-radius:16px;border:1px solid #eaecf4;overflow:hidden;">
                <table class="rtable">
                  <thead>
                    <tr>
                      <th>Invoice #</th>
                      <th>Customer</th>
                      <th>Date</th>
                      <th>Total</th>
                      <th>Payment</th>
                      <th>Status</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody id="searchTbody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- ══════════════════ NEW SECTION: PRODUCT LOOKUP ══════════════════ --}}
        {{--
          Controller must pass: nothing extra — search is AJAX via route('cashier.product.lookup')
          Route: GET /cashier/products/lookup?q=... → returns JSON array from products table
          Example controller method:
            public function productLookup(Request $req) {
              $q = $req->query('q','');
              if (strlen($q) < 2) return response()->json([]);
              return Product::where('is_active',1)
                ->where(function($query) use ($q) {
                  $query->where('product_name','like',"%$q%")
                        ->orWhere('generic_name','like',"%$q%")
                        ->orWhere('brand','like',"%$q%")
                        ->orWhere('sku','like',"%$q%");
                })
                ->with('category')
                ->select('id','sku','product_name','generic_name','brand','selling_price','stock_quantity','reorder_level','requires_rx','category_id')
                ->limit(20)
                ->get()
                ->map(function($p) {
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
            }
        --}}
        <div class="section" id="section-inv-search">
          <div style="margin-bottom:20px;">
            <div style="font-size:14px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Product Lookup</div>
            <div style="font-size:13px;color:#8891b4;">Check if a product is available and its current stock — read-only</div>
          </div>

          {{-- Read-only notice --}}
          <div style="display:flex;align-items:center;gap:10px;background:#eef1ff;border:1px solid #c7d2fe;border-radius:12px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#3d52d5;font-weight:500;">
            <span class="material-symbols-rounded" style="font-size:18px;">info</span>
            This is a read-only view. Use it to answer customer questions about product availability.
          </div>

          <div class="search-wrap">
            <span class="material-symbols-rounded">search</span>
            <input type="text" id="invSearchInput"
              placeholder="Search by product name, generic name, brand, or SKU…"
              oninput="debounceInvSearch(this.value)" autocomplete="off"/>
          </div>

          {{-- Empty / loading / results --}}
          <div id="invSearchEmpty" style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">
            <span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">medication_liquid</span>
            <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">Search for a product</div>
            <div style="font-size:13px;">Results will appear here instantly</div>
          </div>
          <div id="invSearchLoading" style="display:none;padding:40px;text-align:center;color:#8891b4;">
            <span class="material-symbols-rounded" style="font-size:32px;animation:spin 1s linear infinite;display:block;margin-bottom:8px;">sync</span>
            Searching…
          </div>
          <div id="invSearchResults" style="display:none;">
            <div style="background:white;border-radius:16px;border:1px solid #eaecf4;overflow:hidden;">
              <div style="padding:12px 16px;border-bottom:1px solid #eaecf4;display:flex;align-items:center;justify-content:space-between;">
                <div style="font-size:13px;font-weight:700;color:#1a1d2e;">Results</div>
                <div style="font-size:11px;color:#8891b4;" id="invResultCount"></div>
              </div>
              <div id="invResultsList"></div>
            </div>
          </div>
        </div>

        {{-- ══════════════════ SECTION: LOW STOCK ALERTS ══════════════════ --}}
        <div class="section" id="section-lowstock">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
              <div style="font-size:14px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Low Stock Alerts</div>
              <div style="font-size:13px;color:#8891b4;">Products at or below their reorder level</div>
            </div>
            @if(isset($lowStockProducts) && $lowStockProducts->count() > 0)
            <div style="background:#fef2f2;color:#dc2626;font-size:13px;font-weight:700;padding:6px 14px;border-radius:8px;">
              {{ $lowStockProducts->count() }} item(s) need restocking
            </div>
            @endif
          </div>

          @if(!isset($lowStockProducts) || $lowStockProducts->isEmpty())
            <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">
              <span class="material-symbols-rounded icon-fill" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;color:#16a34a;">check_circle</span>
              <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">All stocks are healthy!</div>
              <div style="font-size:13px;">No products are below reorder level</div>
            </div>
          @else
            <div style="background:white;border-radius:16px;border:1px solid #eaecf4;overflow:hidden;">
              <table class="rtable">
                <thead>
                  <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>In Stock</th>
                    <th>Reorder At</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($lowStockProducts as $prod)
                  @php
                    $pct = $prod->reorder_level > 0 ? min(100, round(($prod->stock_quantity / $prod->reorder_level) * 100)) : 0;
                    $barColor = $prod->stock_quantity == 0 ? '#dc2626' : ($pct <= 50 ? '#f59e0b' : '#16a34a');
                  @endphp
                  <tr>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:#6b7494;">{{ $prod->sku }}</td>
                    <td>
                      <div style="font-weight:600;color:#1a1d2e;">{{ $prod->product_name }}</div>
                      @if($prod->generic_name)
                        <div style="font-size:11px;color:#8891b4;">{{ $prod->generic_name }}</div>
                      @endif
                    </td>
                    <td style="color:#6b7494;">{{ $prod->category->category_name ?? '—' }}</td>
                    <td style="font-weight:700;color:{{ $prod->stock_quantity == 0 ? '#dc2626' : '#1a1d2e' }};">
                      {{ $prod->stock_quantity }}
                    </td>
                    <td style="color:#6b7494;">{{ $prod->reorder_level }}</td>
                    <td>
                      <div class="stock-bar-wrap">
                        <div class="stock-bar-fill" style="width:{{ $pct }}%;background:{{ $barColor }};"></div>
                      </div>
                      <span style="font-size:11px;color:#8891b4;margin-left:6px;">{{ $pct }}%</span>
                    </td>
                    <td>
                      @if($prod->stock_quantity == 0)
                        <span class="stock-critical">Out of Stock</span>
                      @else
                        <span class="stock-low">Low Stock</span>
                      @endif
                    </td>
                    <td>
                      <button type="button" class="alert-row-btn" onclick='sendLowStockAlert(this, {{ $prod->id }}, @json($prod->product_name), @json($prod->sku))' style="border:none;background:#f97316;color:white;padding:8px 12px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;transition:transform .12s ease,box-shadow .12s ease,background .12s ease;">
                        Alert Admin
                      </button>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

        {{-- ══════════════════ SECTION: EXPIRING SOON ══════════════════ --}}
        <div class="section" id="section-expiring">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
              <div style="font-size:14px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Expiring Soon</div>
              <div style="font-size:13px;color:#8891b4;">Batches expiring within the next 30 days with remaining stock</div>
            </div>
            @if(isset($expiringProducts) && $expiringProducts->count() > 0)
            <div style="background:#fffbeb;color:#92400e;font-size:13px;font-weight:700;padding:6px 14px;border-radius:8px;border:1px solid #fde68a;">
              ⚠ {{ $expiringProducts->count() }} batch(es) expiring soon
            </div>
            @endif
          </div>

          @if(!isset($expiringProducts) || $expiringProducts->isEmpty())
            <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">
              <span class="material-symbols-rounded icon-fill" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;color:#16a34a;">verified</span>
              <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">No batches expiring soon</div>
              <div style="font-size:13px;">All batches are good for more than 30 days</div>
            </div>
          @else
            <div style="background:white;border-radius:16px;border:1px solid #eaecf4;overflow:hidden;">
              <table class="rtable">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Batch No.</th>
                    <th>Lot No.</th>
                    <th>Supplier</th>
                    <th>Batch Qty</th>
                    <th>Received</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($expiringProducts as $batch)
                  @php
                    $daysLeft  = (int) now()->diffInDays(\Carbon\Carbon::parse($batch->expiry_date), false);
                    $isExpired = $daysLeft < 0;
                    $textColor = $isExpired ? '#dc2626' : ($daysLeft <= 7 ? '#dc2626' : ($daysLeft <= 14 ? '#d97706' : '#92400e'));
                  @endphp
                  <tr>
                    <td>
                      <div style="font-weight:600;color:#1a1d2e;">{{ $batch->product_name }}</div>
                      <div style="font-size:11px;color:#8891b4;">
                        {{ $batch->generic_name ?? '' }}
                        @if($batch->category_name) · {{ $batch->category_name }} @endif
                      </div>
                      <div style="font-family:'DM Mono',monospace;font-size:10px;color:#c4c9dd;">{{ $batch->sku }}</div>
                    </td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:#1a1d2e;">{{ $batch->batch_number }}</td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:#6b7494;">{{ $batch->lot_number ?? '—' }}</td>
                    <td style="font-size:12px;color:#6b7494;">{{ $batch->supplier_name ?? '—' }}</td>
                    <td style="font-weight:700;color:{{ $batch->batch_qty <= 10 ? '#dc2626' : '#1a1d2e' }};">
                      {{ number_format($batch->batch_qty) }}
                      @if($batch->batch_qty <= 10)<div style="font-size:10px;color:#dc2626;font-weight:600;">low qty</div>@endif
                    </td>
                    <td style="font-size:12px;color:#8891b4;">{{ $batch->received_date ? \Carbon\Carbon::parse($batch->received_date)->format('M d, Y') : '—' }}</td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600;">{{ \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') }}</td>
                    <td style="font-weight:800;color:{{ $textColor }};">{{ $isExpired ? 'EXPIRED' : $daysLeft.'d' }}</td>
                    <td>
                      @if($isExpired)
                        <span style="background:#fef2f2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Expired</span>
                      @elseif($daysLeft <= 7)
                        <span style="background:#fef2f2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Critical</span>
                      @elseif($daysLeft <= 14)
                        <span style="background:#fffbeb;color:#d97706;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Urgent</span>
                      @else
                        <span style="background:#fff7ed;color:#c2410c;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">Soon</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

 {{-- ══════════════════ SECTION: SALES SUMMARY ══════════════════ --}}
<div class="section" id="section-sales-summary">

  {{-- Header --}}
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <div>
      <div style="font-size:14px;font-weight:700;color:#1a1d2e;">Sales Summary</div>
      <div style="font-size:13px;color:#8891b4;">Revenue overview · {{ now()->format('F j, Y') }}</div>
    </div>
  </div>

  {{-- KPI Row --}}
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;">
    <div style="background:white;border-radius:12px;padding:14px 16px;border:1px solid #eaecf4;border-top:3px solid #3d52d5;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#3d52d5;margin-bottom:6px;">Today</div>
      <div style="font-size:22px;font-weight:800;color:#1a1d2e;line-height:1;">₱{{ number_format($salesSummary['today_total'] ?? 0, 2) }}</div>
      <div style="font-size:11px;color:#8891b4;margin-top:4px;">{{ $salesSummary['today_count'] ?? 0 }} transaction(s)</div>
      <div style="font-size:11px;color:#16a34a;margin-top:3px;font-weight:600;">+₱{{ number_format($salesSummary['today_tax'] ?? 0, 2) }} VAT</div>
    </div>
    <div style="background:white;border-radius:12px;padding:14px 16px;border:1px solid #eaecf4;border-top:3px solid #16a34a;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#16a34a;margin-bottom:6px;">This Week</div>
      <div style="font-size:22px;font-weight:800;color:#1a1d2e;line-height:1;">₱{{ number_format($salesSummary['week_total'] ?? 0, 2) }}</div>
      <div style="font-size:11px;color:#8891b4;margin-top:4px;">{{ $salesSummary['week_count'] ?? 0 }} transaction(s)</div>
    </div>
    <div style="background:white;border-radius:12px;padding:14px 16px;border:1px solid #eaecf4;border-top:3px solid #d97706;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#d97706;margin-bottom:6px;">This Month</div>
      <div style="font-size:22px;font-weight:800;color:#1a1d2e;line-height:1;">₱{{ number_format($salesSummary['month_total'] ?? 0, 2) }}</div>
      <div style="font-size:11px;color:#8891b4;margin-top:4px;">{{ $salesSummary['month_count'] ?? 0 }} transaction(s)</div>
    </div>
  </div>

  {{-- Charts Row 1: Bar + Line --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">

    <div style="background:white;border-radius:12px;border:1px solid #eaecf4;padding:16px;">
      <div style="font-size:12px;font-weight:700;color:#1a1d2e;margin-bottom:2px;">7-Day Revenue</div>
      <div style="font-size:11px;color:#8891b4;margin-bottom:12px;">Daily gross sales (last 7 days)</div>
      <div style="position:relative;height:150px;">
        <canvas id="barChart"></canvas>
      </div>
    </div>

    <div style="background:white;border-radius:12px;border:1px solid #eaecf4;padding:16px;">
      <div style="font-size:12px;font-weight:700;color:#1a1d2e;margin-bottom:2px;">Transaction Trend</div>
      <div style="font-size:11px;color:#8891b4;margin-bottom:12px;">Number of transactions per day</div>
      <div style="position:relative;height:150px;">
        <canvas id="lineChart"></canvas>
      </div>
    </div>

  </div>

  {{-- Charts Row 2: Donut + Top Products --}}
  <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:12px;margin-bottom:12px;">

    <div style="background:white;border-radius:12px;border:1px solid #eaecf4;padding:16px;">
      <div style="font-size:12px;font-weight:700;color:#1a1d2e;margin-bottom:2px;">Payment Methods</div>
      <div style="font-size:11px;color:#8891b4;margin-bottom:12px;">Today's revenue split</div>
      @if(!empty($salesSummary['by_payment']) && $salesSummary['by_payment']->count() > 0)
        <div style="display:flex;align-items:center;gap:16px;">
          <div style="position:relative;height:130px;width:130px;flex-shrink:0;">
            <canvas id="donutChart"></canvas>
          </div>
          <div id="donutLegend" style="display:flex;flex-direction:column;gap:8px;flex:1;min-width:0;"></div>
        </div>
      @else
        <div style="text-align:center;padding:24px 0;color:#8891b4;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;opacity:.3;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <div style="font-size:12px;">No payment data today</div>
        </div>
      @endif
    </div>

    <div style="background:white;border-radius:12px;border:1px solid #eaecf4;padding:16px;">
      <div style="font-size:12px;font-weight:700;color:#1a1d2e;margin-bottom:2px;">Top Products</div>
      <div style="font-size:11px;color:#8891b4;margin-bottom:12px;">Units sold today</div>
      @if(!empty($shiftReport['top_products']) && $shiftReport['top_products']->count() > 0)
        <div style="position:relative;height:150px;">
          <canvas id="topProdChart"></canvas>
        </div>
      @else
        <div style="text-align:center;padding:24px 0;color:#8891b4;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;opacity:.3;"><rect x="2" y="3" width="6" height="18"/><rect x="9" y="8" width="6" height="13"/><rect x="16" y="13" width="6" height="8"/></svg>
          <div style="font-size:12px;">No product sales yet today</div>
        </div>
      @endif
    </div>

  </div>

  {{-- VAT + Discount Row --}}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
    <div style="background:white;border-radius:12px;padding:14px 16px;border:1px solid #eaecf4;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8891b4;margin-bottom:8px;">Today's Discounts Given</div>
      <div style="font-size:22px;font-weight:700;color:#dc2626;">— ₱{{ number_format($salesSummary['today_discount'] ?? 0,2) }}</div>
    </div>
    <div style="background:white;border-radius:12px;padding:14px 16px;border:1px solid #eaecf4;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8891b4;margin-bottom:8px;">Today's VAT Collected</div>
      <div style="font-size:22px;font-weight:700;color:#1a1d2e;">₱{{ number_format($salesSummary['today_tax'] ?? 0,2) }}</div>
    </div>
  </div>

  {{-- Payment Method Table --}}
  @if(!empty($salesSummary['by_payment']) && $salesSummary['by_payment']->count() > 0)
  <div style="background:white;border-radius:12px;border:1px solid #eaecf4;overflow:hidden;">
    <div style="padding:14px 18px;border-bottom:1px solid #eaecf4;font-size:13px;font-weight:700;color:#1a1d2e;">Today's Revenue by Payment Method</div>
    <table class="rtable">
      <thead><tr><th>Method</th><th>Transactions</th><th>Total</th><th>Share</th></tr></thead>
      <tbody>
        @php $todayTotal = $salesSummary['today_total'] ?? 0; @endphp
        @foreach($salesSummary['by_payment'] as $pm)
        <tr>
          <td style="font-weight:600;">{{ $pm->method_name }}</td>
          <td style="color:#6b7494;">{{ $pm->count }}</td>
          <td style="font-weight:700;">₱{{ number_format($pm->total,2) }}</td>
          <td>
            @php $share = $todayTotal > 0 ? round(($pm->total / $todayTotal) * 100,1) : 0; @endphp
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:80px;height:6px;background:#eaecf4;border-radius:4px;overflow:hidden;">
                <div style="width:{{ $share }}%;height:100%;background:#3d52d5;border-radius:4px;"></div>
              </div>
              <span style="font-size:12px;color:#6b7494;">{{ $share }}%</span>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

</div>

      {{-- ══════════════════ SECTION: SHIFT REPORT ══════════════════ --}}
<div class="section" id="section-shift">
<style>
.sr-stat{background:#f8f9fc;border-radius:10px;padding:16px 18px;text-align:center;}
.sr-stat-label{font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#9aa3c2;margin-bottom:6px;}
.sr-stat-val{font-size:24px;font-weight:800;color:#0f1117;letter-spacing:-.03em;font-family:'DM Mono',monospace;}
.prod-bar-track{flex:1;height:4px;background:#f0f2f8;border-radius:4px;overflow:hidden;}
.prod-bar-fill{height:4px;border-radius:4px;transition:width .6s ease;}

@media print {
  /* Hide everything except the shift report */
  body * { visibility: hidden; }
  #section-shift, #section-shift * { visibility: visible; }
  #section-shift { position: absolute; top: 0; left: 0; width: 100%; padding: 32px 40px; }

  /* Hide the print button itself */
  .sr-no-print { display: none !important; }

  /* Reset card styles to flat for print */
  .sr-print-header { background: none !important; border: none !important; border-bottom: 2px solid #0f1117!important; border-radius: 0 !important; padding: 0 0 16px !important; margin-bottom: 20px !important; }
  .sr-stat { background: none !important; border: 1px solid #dde1ec !important; }

  /* Print header branding */
  .sr-print-brand { display: block !important; }

  /* Force colors for print */
  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

  {{-- Print-only brand header --}}
  <div class="sr-print-brand" style="display:none;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #eaecf4;">
    <div style="font-size:18px;font-weight:800;color:#0f1117;">MediCare</div>
    <div style="font-size:11px;color:#9aa3c2;margin-top:2px;">Cashier Shift Report · {{ now()->format('F j, Y') }}</div>
  </div>

  {{-- Section header --}}
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;" class="sr-no-print-header">
    <div>
      <div style="font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#3d52d5;margin-bottom:3px;">Reports</div>
      <div style="font-size:22px;font-weight:800;color:#0f1117;letter-spacing:-.03em;">Shift Report</div>
      <div style="font-size:12px;color:#9aa3c2;margin-top:4px;">Your performance for today · {{ now()->format('F j, Y') }}</div>
    </div>
    <button onclick="window.print()" class="sr-no-print"
      style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border:1.5px solid #eaecf4;border-radius:10px;background:white;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;color:#6b7494;cursor:pointer;transition:all .15s;"
      onmouseover="this.style.borderColor='#c7cde8';this.style.color='#3d52d5'" onmouseout="this.style.borderColor='#eaecf4';this.style.color='#6b7494'">
      <span class="material-symbols-rounded" style="font-size:17px;">print</span> Print Report
    </button>
  </div>

 {{-- Cashier hero card --}}
<div class="sr-print-header" style="background:white;border:1px solid #eaecf4;border-radius:14px;padding:20px 24px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
  <div style="display:flex;align-items:center;gap:14px;">
    <div style="width:48px;height:48px;border-radius:50%;background:#eef1ff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#3d52d5;flex-shrink:0;">
      {{ $initials }}
    </div>
    <div>
      <div style="font-size:17px;font-weight:700;color:#0f1117;">{{ $shiftReport['cashier_name'] ?? Auth::user()->name }}</div>
      <div style="font-size:12px;color:#9aa3c2;margin-top:3px;display:flex;align-items:center;gap:5px;">
        <span class="material-symbols-rounded" style="font-size:14px;">schedule</span>
        @if($shiftReport['shift_start'])
          Shift started {{ $shiftReport['shift_start'] }}
        @else
          Logged in today
        @endif
      </div>
    </div>
  </div>
  <div style="text-align:right;">
    <div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#9aa3c2;margin-bottom:5px;">Total Collected</div>
    <div style="font-size:30px;font-weight:900;color:#0f1117;letter-spacing:-.04em;font-family:'DM Mono',monospace;">
      ₱{{ number_format($shiftReport['total_sales'] ?? 0, 2) }}
    </div>
  </div>
</div>

  {{-- Stat cards --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">
    <div class="sr-stat">
      <div class="sr-stat-label">Transactions</div>
      <div class="sr-stat-val">{{ $shiftReport['total_transactions'] ?? 0 }}</div>
    </div>
    <div class="sr-stat">
      <div class="sr-stat-label">Voided</div>
      <div class="sr-stat-val" style="{{ ($shiftReport['voided_count'] ?? 0) > 0 ? 'color:#dc2626;' : '' }}">
        {{ $shiftReport['voided_count'] ?? 0 }}
      </div>
    </div>
    <div class="sr-stat">
      <div class="sr-stat-label">Discounts</div>
      <div class="sr-stat-val" style="color:#d97706;">
        {{ ($shiftReport['total_discount'] ?? 0) > 0 ? '— ' : '' }}₱{{ number_format($shiftReport['total_discount'] ?? 0, 0) }}
      </div>
    </div>
    <div class="sr-stat">
      <div class="sr-stat-label">VAT Collected</div>
      <div class="sr-stat-val" style="color:#3d52d5;">₱{{ number_format($shiftReport['total_tax'] ?? 0, 0) }}</div>
    </div>
  </div>

  {{-- Top Products --}}
  @if(!empty($shiftReport['top_products']) && $shiftReport['top_products']->count() > 0)
  @php
    $maxQty = $shiftReport['top_products']->max('qty_sold');
    $rankColors = [
      ['bg'=>'#eef1ff','text'=>'#3d52d5','bar'=>'#3d52d5'],
      ['bg'=>'#f0fdf4','text'=>'#15803d','bar'=>'#16a34a'],
      ['bg'=>'#fefce8','text'=>'#854d0e','bar'=>'#d97706'],
      ['bg'=>'#fef2f2','text'=>'#991b1b','bar'=>'#ef4444'],
      ['bg'=>'#f5f3ff','text'=>'#6d28d9','bar'=>'#7c3aed'],
    ];
  @endphp
  <div style="background:white;border:1px solid #eaecf4;border-radius:14px;padding:18px 20px;">
    <div style="font-size:12px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#9aa3c2;margin-bottom:14px;">
      Top products sold this shift
    </div>
    @foreach($shiftReport['top_products'] as $i => $prod)
    @php $rc = $rankColors[$i] ?? $rankColors[4]; $pct = $maxQty > 0 ? round(($prod->qty_sold / $maxQty) * 100) : 0; @endphp
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #f5f6fa;' : '' }}">
      <div style="width:24px;height:24px;border-radius:6px;background:{{ $rc['bg'] }};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:{{ $rc['text'] }};flex-shrink:0;">
        {{ $i + 1 }}
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:600;color:#0f1117;margin-bottom:6px;">{{ $prod->product_name }}</div>
        <div class="prod-bar-track">
          <div class="prod-bar-fill" style="width:{{ $pct }}%;background:{{ $rc['bar'] }};"></div>
        </div>
      </div>
      <div style="font-size:12px;font-weight:700;color:#6b7494;white-space:nowrap;font-family:'DM Mono',monospace;min-width:54px;text-align:right;">
        {{ number_format($prod->qty_sold) }} {{ $prod->qty_sold == 1 ? 'unit' : 'units' }}
      </div>
    </div>
    @endforeach
  </div>

  @else
  <div style="background:white;border:1.5px dashed #dde1ec;border-radius:14px;padding:48px;text-align:center;">
    <span class="material-symbols-rounded" style="font-size:40px;color:#d4d8ea;display:block;margin-bottom:10px;">inventory_2</span>
    <div style="font-size:13px;font-weight:700;color:#6b7494;">No sales yet this shift</div>
    <div style="font-size:12px;color:#9aa3c2;margin-top:3px;">Top products will appear after completing transactions</div>
  </div>
  @endif

</div>
         {{-- ══════════════════ SECTION: QUEUE / PICKUP ══════════════════ --}}
        <div class="section" id="section-queue">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
              <div style="font-size:14px;font-weight:700;color:#1a1d2e;">Ready for Pickup Queue</div>
              <div style="font-size:13px;color:#8891b4;">Call customers after their medicine is packed</div>
            </div>
            <div style="display:flex;gap:8px;">
              <button onclick="loadQueue()" style="display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #eaecf4;border-radius:8px;background:white;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;color:#6b7494;cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='#a5b4fc';this.style.color='#3d52d5'" onmouseout="this.style.borderColor='#eaecf4';this.style.color='#6b7494'">
                <span class="material-symbols-rounded" style="font-size:16px;">refresh</span> Refresh
              </button>
              <button onclick="resetQueue()" style="display:flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #fca5a5;border-radius:8px;background:white;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:600;color:#dc2626;cursor:pointer;transition:all .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='white'">
                <span class="material-symbols-rounded" style="font-size:16px;">restart_alt</span> Reset Queue
              </button>
            </div>
          </div>
 
          {{-- Now serving banner --}}
          <div id="nowServingBanner" style="display:none;background:linear-gradient(135deg,#002045,#1a3a6b);border-radius:14px;padding:18px 24px;margin-bottom:20px;display:none;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:4px;">Now Showing on Display</div>
              <div style="font-size:36px;font-weight:800;color:white;line-height:1;" id="nowServingNum">—</div>
              <div style="font-size:13px;color:rgba(255,255,255,.55);margin-top:4px;" id="nowServingName"></div>
            </div>
            <button onclick="markDone(currentServingId)" style="padding:10px 20px;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.2);border-radius:10px;color:white;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;" onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
              <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px;">check_circle</span> Mark as Done
            </button>
          </div>
 
          {{-- Queue list --}}
          <div id="queueList">
            <div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">
              <span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">local_pharmacy</span>
              <div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">Queue is empty</div>
              <div style="font-size:13px;">Paid orders will appear here once payment is processed</div>
            </div>
          </div>
        </div>
 
        {{-- ══════════════ END QUEUE SECTION ══════════════ --}}
 

      </div>{{-- /content --}}

      {{-- ════════════════════════════════ RIGHT PANEL ════════════════════════════════ --}}
      <div class="panel" id="rightPanel">
        <div class="panel-empty" id="panelEmpty">
          <span class="material-symbols-rounded icon-fill">point_of_sale</span>
          <div style="font-size:15px;font-weight:600;color:#1a1d2e;">Select an order</div>
          <div style="font-size:13px;max-width:200px;">Click any pending order on the left to process it</div>
        </div>

        <div id="panelContent" style="display:none;flex-direction:column;gap:20px;">

          <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
              <div style="font-size:12px;font-family:'DM Mono',monospace;color:#8891b4;" id="panelInvNo"></div>
              <button onclick="clearPanel()" style="width:28px;height:28px;border-radius:7px;background:#f5f6fa;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#8891b4;">
                <span class="material-symbols-rounded" style="font-size:16px;">close</span>
              </button>
            </div>
            <div style="font-size:18px;font-weight:700;color:#1a1d2e;" id="panelCustName"></div>
            <div style="font-size:13px;color:#8891b4;margin-top:2px;" id="panelCustMeta"></div>
          </div>

          <div>
            <div class="section-label">Order Items</div>
            <div id="panelItems" style="display:flex;flex-direction:column;gap:6px;"></div>
          </div>

          <div id="rxSection" style="display:none;">
            <div class="section-label">Prescription</div>
            <input type="text" id="rxInput" class="input-field" placeholder="Prescription No. (required for Rx items)"/>
          </div>

          <div>
            <div class="section-label">Discount</div>
            <select id="discountSelect" class="disc-select" onchange="recalc()">
              <option value="">— No discount —</option>
              @foreach($discountTypes as $d)
              <option value="{{ $d->id }}" data-method="{{ $d->discount_method }}" data-value="{{ $d->discount_value }}" data-code="{{ $d->discount_code }}">
                {{ $d->discount_name }} ({{ $d->discount_method === 'percentage' ? $d->discount_value.'%' : '₱'.number_format($d->discount_value,2) }})
              </option>
              @endforeach
            </select>
            <div id="idNumberWrap" style="display:none;margin-top:8px;">
              <input type="text" id="idNumberInput" class="input-field" placeholder="Senior/PWD ID Number"/>
            </div>
          </div>

          <div>
            <div class="section-label">Payment Method</div>
            <div class="pay-method" id="payMethodBtns">
              @foreach($paymentMethods as $pm)
              <button class="pay-btn" data-id="{{ $pm->id }}" onclick="selectPayMethod(this)">{{ $pm->method_name }}</button>
              @endforeach
            </div>
          </div>

          <div id="cashTenderedWrap">
            <div class="section-label">Amount Tendered</div>
            <input type="number" id="tenderedInput" class="input-field" placeholder="0.00" step="0.01" oninput="recalc()"/>
          </div>

          <div class="totals-box">
            <div class="total-row"><span>Subtotal</span><span id="calcSub">₱0.00</span></div>
            <div class="total-row"><span>Discount</span><span id="calcDisc" style="color:#dc2626;">— ₱0.00</span></div>
            <div class="total-row"><span>VAT (12%)</span><span id="calcVat">₱0.00</span></div>
            <div class="total-row grand"><span>Total</span><span id="calcTotal">₱0.00</span></div>
            <div class="total-row change" id="changeRow" style="display:none;">
              <span>Change</span><span id="calcChange">₱0.00</span>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:8px;">
            <button class="btn-pay" id="btnPay" onclick="processPayment()" disabled>
              <span class="material-symbols-rounded icon-fill">payments</span>
              Confirm Payment
            </button>
            <button class="btn-void" onclick="showVoidModal()">
              <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px;">cancel</span>
              Void Order
            </button>
          </div>

        </div>{{-- /panelContent --}}
      </div>{{-- /panel --}}

    </div>
  </div>
</div>

{{-- VOID MODAL --}}
<div class="modal-ov" id="voidModal" onclick="closeVoidModal(event)">
  <div class="modal-box">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
      <div style="width:44px;height:44px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;">
        <span class="material-symbols-rounded icon-fill" style="font-size:24px;">cancel</span>
      </div>
      <div>
        <div style="font-size:17px;font-weight:700;color:#1a1d2e;">Void Order</div>
        <div style="font-size:13px;color:#8891b4;">This action cannot be undone</div>
      </div>
    </div>
    <div style="margin-bottom:16px;">
      <div class="section-label">Reason for voiding</div>
      <textarea id="voidReason" rows="3" class="input-field" style="resize:none;" placeholder="e.g. Customer cancelled, duplicate order..."></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button onclick="closeVoidModal()" style="flex:1;height:44px;border-radius:10px;border:1.5px solid #eaecf4;background:white;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
      <button onclick="submitVoid()" style="flex:1;height:44px;border-radius:10px;background:#dc2626;border:none;color:white;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;">Void Order</button>
    </div>
  </div>
</div>

{{-- SUCCESS MODAL --}}
<div class="modal-ov" id="successModal">
  <div class="modal-box" style="text-align:center;">
    <div style="width:64px;height:64px;border-radius:20px;background:#f0fdf4;border:2px solid #bbf7d0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <span class="material-symbols-rounded icon-fill" style="color:#16a34a;font-size:32px;">check_circle</span>
    </div>
    <div style="font-size:22px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Payment Received!</div>
    <div style="font-size:13px;color:#8891b4;margin-bottom:6px;" id="successInvNo"></div>
    <div style="background:#f5f6fa;border-radius:12px;padding:16px;margin:16px 0;text-align:left;">
      <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:600;color:#1a1d2e;margin-bottom:6px;">
        <span>Amount Paid</span><span id="successTotal"></span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:20px;font-weight:800;color:#16a34a;">
        <span>Change</span><span id="successChange"></span>
      </div>
    </div>
      {{-- NEW: show queue number on the payment success screen --}}
    <div id="successQueueBox" style="display:none;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:14px;margin:12px 0;text-align:center;">
      <div style="font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#16a34a;margin-bottom:6px;">Queue Number</div>
      <div style="font-size:48px;font-weight:900;color:#002045;line-height:1;" id="successQueueNum">—</div>
      <div style="font-size:12px;color:#64748b;margin-top:6px;">Medicine is now being packed</div>
    </div>
 
    <div style="display:flex;gap:10px;flex-direction:column;">
      <div style="display:flex;gap:10px;">
        <button onclick="closeSuccess()" style="flex:1;height:44px;border-radius:10px;border:1.5px solid #eaecf4;background:white;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Done</button>
        <a id="successPrintBtn" href="#" target="_blank" style="flex:1;height:44px;border-radius:10px;background:#3d52d5;border:none;color:white;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;">
          <span class="material-symbols-rounded" style="font-size:18px;">print</span> Print Receipt
        </a>
      </div>
      {{-- NEW: Call for pickup button (only if there's a queue ticket) --}}
      <button id="callPickupBtn" onclick="callFromSuccess()" style="display:none;width:100%;height:44px;border-radius:10px;background:#16a34a;border:none;color:white;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:none;align-items:center;justify-content:center;gap:8px;">
        <span class="material-symbols-rounded" style="font-size:18px;">campaign</span>
        Call for Pickup — <span id="callPickupNumLabel"></span>
      </button>
    </div>
  </div>
</div>

<div class="modal-ov" id="alertModal" style="display:none;background:rgba(15,23,42,.5);position:fixed;inset:0;align-items:center;justify-content:center;z-index:95;">
  <div class="modal-box" style="max-width:500px;width:100%;background:white;border-radius:20px;padding:24px;position:relative;">
    <button type="button" onclick="closeAlertModal()" style="position:absolute;top:18px;right:18px;width:34px;height:34px;border:none;border-radius:12px;background:#f3f4f6;color:#334155;cursor:pointer;font-size:18px;">×</button>
    <div style="font-size:18px;font-weight:700;color:#0f172a;margin-bottom:10px;" id="alertModalTitle">Alert Admin</div>
    <div style="font-size:13px;color:#64748b;margin-bottom:16px;" id="alertModalSku">SKU: —</div>
    <textarea id="alertMessage" placeholder="Add an optional note for admin..." style="width:100%;min-height:120px;border:1px solid #e2e8f0;border-radius:14px;padding:14px;font-size:13px;color:#0f172a;resize:vertical;"></textarea>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
      <button type="button" onclick="closeAlertModal()" style="border:1px solid #cbd5e1;background:white;color:#334155;border-radius:10px;padding:11px 18px;font-weight:700;cursor:pointer;">Cancel</button>
      <button type="button" id="alertSubmitBtn" onclick="submitAlert()" style="border:none;background:#16a34a;color:white;border-radius:10px;padding:11px 18px;font-weight:700;cursor:pointer;">Send Alert</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"><span id="toastMsg"></span></div>

<style>
  .alert-row-btn {
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease, opacity .12s ease;
  }
  .alert-row-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 14px rgba(249,115,22,.18);
    background: #fb923c;
  }
  .alert-row-btn:active,
  .alert-row-btn.btn-clicked,
  .alert-row-btn.sending {
    transform: scale(0.96);
    box-shadow: inset 0 2px 10px rgba(0,0,0,.18);
  }
  .alert-row-btn.alert-sent {
    background: #10b981 !important;
    cursor: default !important;
    box-shadow: none !important;
  }
  .alert-row-btn.alert-sent:hover {
    background: #0f766e !important;
  }
  .alert-row-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
</style>

{{-- ══ CHART.JS — place just before closing </body> tag ══ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
 <script>
    {{-- Chart data --}}
    var weekly = @json($weeklySales ?? []);
    var byPay  = @json($salesSummary['by_payment'] ?? []);
    var topP   = @json($shiftReport['top_products'] ?? []);

    {{-- Routes --}}
    var CSRF              = document.querySelector('meta[name=csrf-token]').content;
    var ROUTE_GET         = "{{ route('cashier.invoice.get',  ['id' => '__ID__']) }}";
    var ROUTE_PAY         = "{{ route('cashier.payment.process') }}";
    var ROUTE_VOID        = "{{ route('cashier.invoice.void') }}";
    var ROUTE_PRINT       = "{{ route('cashier.invoice.print', ['id' => '__ID__']) }}";
    var ROUTE_SEARCH      = "{{ route('cashier.invoice.search') }}";
    var ROUTE_PROD_LOOKUP = "{{ route('cashier.product.lookup') }}";
    var ROUTE_QUEUE_LIST  = "{{ route('cashier.queue.list') }}";
    var ROUTE_QUEUE_CALL  = "{{ route('cashier.queue.call') }}";
    var ROUTE_QUEUE_DONE  = "{{ route('cashier.queue.done') }}";
    var ROUTE_QUEUE_SKIP  = "{{ route('cashier.queue.skip') }}";
    var ROUTE_QUEUE_RESET = "{{ route('cashier.queue.reset') }}";
    var ROUTE_ALERT_CREATE = "{{ route('cashier.alert.create') }}";
</script>
<script src="{{ asset('js/cashier.js') }}?v={{ filemtime(public_path('js/cashier.js')) }}"></script>
</body>
</html>