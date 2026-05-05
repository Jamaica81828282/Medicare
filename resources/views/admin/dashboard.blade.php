<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Admin Panel — MediCare</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div style="display:flex;height:100vh;overflow:hidden;">

{{-- ════════ SIDEBAR ════════ --}}
<aside class="sidebar">
  <div style="padding:18px 18px 14px;border-bottom:1px solid #eaecf4;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:36px;height:36px;background:linear-gradient(135deg,#3d52d5,#6979f8);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(61,82,213,.3);">
        <span class="material-symbols-rounded icon-fill" style="color:white;font-size:20px;">medication</span>
      </div>
      <div>
        <div style="font-size:15px;font-weight:700;color:#1a1d2e;line-height:1;">MediCare</div>
        <div style="font-size:9.5px;color:#9aa3c2;letter-spacing:.08em;margin-top:1px;">ADMIN PANEL</div>
      </div>
    </div>
  </div>

  <nav style="flex:1;padding:8px 0;overflow-y:auto;">
    <div class="nav-group-label">Overview</div>
    <button class="nav-item active" onclick="switchSection('dashboard',this)">
      <span class="material-symbols-rounded">dashboard</span>Dashboard
    </button>

    <div class="nav-group-label">Inventory</div>
    <button class="nav-item" onclick="switchSection('products',this)">
      <span class="material-symbols-rounded">medication_liquid</span>Products
    </button>
    <button class="nav-item" onclick="switchSection('batches',this)">
      <span class="material-symbols-rounded">inventory_2</span>Batch Entry
      @if($stats['expiring_30'] > 0)
        <span style="margin-left:auto;background:#f59e0b;color:white;font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:20px;">{{ $stats['expiring_30'] }}</span>
      @endif
    </button>
    <button class="nav-item" onclick="switchSection('suppliers',this)">
      <span class="material-symbols-rounded">local_shipping</span>Suppliers
    </button>

    <div class="nav-group-label">Sales</div>
    <button class="nav-item" onclick="switchSection('invoices',this)">
      <span class="material-symbols-rounded">receipt_long</span>Invoice History
    </button>

    <div class="nav-group-label">Reports</div>
    <button class="nav-item" onclick="switchSection('report-sales',this)">
      <span class="material-symbols-rounded">trending_up</span>Sales Report
    </button>
    <button class="nav-item" onclick="switchSection('report-inventory',this)">
      <span class="material-symbols-rounded">inventory_2</span>Inventory Report
    </button>
    <button class="nav-item" onclick="switchSection('report-cashier',this)">
      <span class="material-symbols-rounded">people</span>Cashier Performance
    </button>

    <div class="nav-group-label">System</div>
    <button class="nav-item" onclick="switchSection('users',this)">
      <span class="material-symbols-rounded">manage_accounts</span>Cashier Accounts
    </button>

    <div class="divider"></div>
    <div class="nav-group-label">View Cashier Dashboard</div>
    <select id="cashierSelector" class="select-field" style="margin:6px 8px;width:calc(100% - 16px);font-size:12.5px;" onchange="viewCashierDashboard()">
      <option value="">Select Cashier...</option>
    </select>

    <a href="{{ route('cashier.dashboard') ?? '/cashier' }}"
       style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-radius:10px;font-size:13.5px;font-weight:500;color:#6b7494;text-decoration:none;transition:all .15s;margin:1px 8px;"
       onmouseover="this.style.background='#f5f6fa';this.style.color='#1a1d2e'" onmouseout="this.style.background='';this.style.color='#6b7494'">
      <span class="material-symbols-rounded" style="font-size:19px;">point_of_sale</span>Current User View
    </a>

    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
      @csrf
      <button type="submit" class="nav-item" style="color:#dc2626;">
        <span class="material-symbols-rounded">logout</span>Sign Out
      </button>
    </form>
  </nav>

  <div style="padding:14px 16px;border-top:1px solid #eaecf4;">
    <div style="display:flex;align-items:center;gap:10px;">
      <div style="width:34px;height:34px;background:#eef1ff;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#3d52d5;flex-shrink:0;">
        <span class="material-symbols-rounded icon-fill" style="font-size:18px;">admin_panel_settings</span>
      </div>
      <div style="min-width:0;">
        <div style="font-size:13px;font-weight:600;color:#1a1d2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name }}</div>
        <div style="font-size:11px;color:#9aa3c2;">Administrator</div>
      </div>
    </div>
  </div>
</aside>

{{-- ════════ MAIN ════════ --}}
<div class="main">
  <div class="topbar">
    <div>
      <div style="font-size:17px;font-weight:700;color:#1a1d2e;" id="topTitle">Dashboard</div>
      <div style="font-size:11.5px;color:#9aa3c2;margin-top:1px;">{{ now()->format('l, F j, Y') }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      @if($stats['low_stock'] > 0)
      <div style="display:flex;align-items:center;gap:5px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:5px 11px;font-size:12px;font-weight:600;color:#dc2626;">
        <span class="material-symbols-rounded" style="font-size:15px;">warning</span>
        {{ $stats['low_stock'] }} low stock
      </div>
      @endif
      @if($stats['expiring_30'] > 0)
      <div style="display:flex;align-items:center;gap:5px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:5px 11px;font-size:12px;font-weight:600;color:#92400e;">
        <span class="material-symbols-rounded" style="font-size:15px;">schedule</span>
        {{ $stats['expiring_30'] }} expiring
      </div>
      @endif
      <div style="background:#f0f2f8;padding:7px 14px;border-radius:9px;font-family:'DM Mono',monospace;font-size:14px;font-weight:500;color:#1a1d2e;min-width:88px;text-align:center;border:1px solid #e8eaf4;" id="clock"></div>
    </div>
  </div>

  <div class="content" id="mainContent">

    {{-- ══════════════ DASHBOARD ══════════════ --}}
    <div class="section active" id="section-dashboard">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div class="stat-card blue">
          <div class="stat-icon blue"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">medication_liquid</span></div>
          <div class="stat-val">{{ $stats['total_products'] }}</div>
          <div class="stat-lbl">Active Products</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon green"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">payments</span></div>
          <div class="stat-val">₱{{ number_format($stats['today_revenue'],0) }}</div>
          <div class="stat-lbl">Today's Revenue</div>
        </div>
        <div class="stat-card teal">
          <div class="stat-icon teal"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">receipt_long</span></div>
          <div class="stat-val">{{ $stats['total_invoices'] }}</div>
          <div class="stat-lbl">Total Paid Invoices</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-icon purple"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">bar_chart</span></div>
          <div class="stat-val">₱{{ number_format($stats['month_revenue'],0) }}</div>
          <div class="stat-lbl">This Month's Revenue</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-icon amber"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">inventory_2</span></div>
          <div class="stat-val">{{ $stats['low_stock'] }}</div>
          <div class="stat-lbl">Low Stock Products</div>
        </div>
        <div class="stat-card rose">
          <div class="stat-icon rose"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">schedule</span></div>
          <div class="stat-val">{{ $stats['expiring_30'] }}</div>
          <div class="stat-lbl">Batches Expiring (30d)</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon green"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">local_shipping</span></div>
          <div class="stat-val">{{ $stats['total_suppliers'] }}</div>
          <div class="stat-lbl">Active Suppliers</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon blue"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">manage_accounts</span></div>
          <div class="stat-val">{{ $stats['total_cashiers'] }}</div>
          <div class="stat-lbl">Cashier Accounts</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;box-shadow:0 2px 12px rgba(61,82,213,.04);">
          <div class="section-title">Quick Actions</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <button onclick="switchSection('products',document.querySelector('[onclick*=\'products\']'));setTimeout(function(){document.getElementById('btnAddProduct').click();},100)" class="btn-secondary" style="justify-content:flex-start;width:100%;height:40px;">
              <span class="material-symbols-rounded" style="font-size:17px;color:#3d52d5;">add_circle</span>Add New Product
            </button>
            <button onclick="switchSection('batches',document.querySelectorAll('.nav-item')[3]);setTimeout(function(){document.getElementById('btnAddBatch').click();},100)" class="btn-secondary" style="justify-content:flex-start;width:100%;height:40px;">
              <span class="material-symbols-rounded" style="font-size:17px;color:#16a34a;">inventory_2</span>Record Stock Delivery
            </button>
            <button onclick="switchSection('suppliers',document.querySelectorAll('.nav-item')[4]);setTimeout(function(){document.getElementById('btnAddSupplier').click();},100)" class="btn-secondary" style="justify-content:flex-start;width:100%;height:40px;">
              <span class="material-symbols-rounded" style="font-size:17px;color:#0d9488;">local_shipping</span>Add Supplier
            </button>
            <button onclick="switchSection('users',document.querySelectorAll('.nav-item')[6]);setTimeout(function(){document.getElementById('btnAddUser').click();},100)" class="btn-secondary" style="justify-content:flex-start;width:100%;height:40px;">
              <span class="material-symbols-rounded" style="font-size:17px;color:#7c3aed;">person_add</span>Create Cashier Account
            </button>
          </div>
        </div>
        <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;box-shadow:0 2px 12px rgba(61,82,213,.04);">
          <div class="section-title">System Info</div>
          <div style="display:flex;flex-direction:column;gap:11px;font-size:13px;color:#6b7494;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span>Database</span><span style="font-weight:600;color:#1a1d2e;font-family:'DM Mono',monospace;font-size:12px;">jumuad_invoices</span>
            </div>
            <div style="height:1px;background:#f0f2f8;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span>VAT Rate</span><span style="font-weight:600;color:#1a1d2e;">12% (PH Standard)</span>
            </div>
            <div style="height:1px;background:#f0f2f8;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span>Logged In As</span><span style="font-weight:600;color:#3d52d5;font-size:12px;">{{ Auth::user()->email }}</span>
            </div>
            <div style="height:1px;background:#f0f2f8;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span>Server Time</span><span style="font-weight:600;color:#1a1d2e;font-size:12px;">{{ now()->format('M j, Y h:i A') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════ PRODUCTS ══════════════ --}}
    <div class="section" id="section-products">
      <div class="search-bar">
        <div class="search-wrap">
          <span class="material-symbols-rounded">search</span>
          <input type="text" id="prodSearch" placeholder="Search by name, SKU, brand, generic name…" oninput="debounce(loadProducts,350)()"/>
        </div>
        <select id="prodCatFilter" class="select-field" style="width:175px;" onchange="loadProducts()">
          <option value="">All Categories</option>
          @foreach($categories as $cat)
          <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
          @endforeach
        </select>
        <select id="prodStatusFilter" class="select-field" style="width:135px;" onchange="loadProducts()">
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
        <button class="btn-primary" id="btnAddProduct" onclick="openProductModal()">
          <span class="material-symbols-rounded" style="font-size:17px;">add</span>Add Product
        </button>
      </div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th style="width:56px;">Image</th>
                <th>SKU</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Selling Price</th>
                <th>Cost</th>
                <th>Stock</th>
                <th>Reorder</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="prodTableBody">
              <tr><td colspan="10" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ══════════════ BATCHES ══════════════ --}}
    <div class="section" id="section-batches">
      <div style="background:#eef1ff;border:1px solid #c7d2fe;border-radius:12px;padding:13px 16px;margin-bottom:18px;font-size:13px;color:#3d52d5;font-weight:500;display:flex;align-items:flex-start;gap:10px;">
        <span class="material-symbols-rounded" style="font-size:17px;flex-shrink:0;margin-top:1px;">info</span>
        <div><strong>Batch Entry — Stock Delivery Recording</strong><br>
        Record each delivery here. After saving, confirm to add its quantity to the product's stock. The cashier's "Expiring Soon" section auto-populates from these entries.</div>
      </div>
      <div class="search-bar">
        <div class="search-wrap">
          <span class="material-symbols-rounded">search</span>
          <input type="text" id="batchSearch" placeholder="Search by batch #, lot #, or product name…" oninput="debounce(loadBatches,350)()"/>
        </div>
        <button class="btn-primary" id="btnAddBatch" onclick="openBatchModal()">
          <span class="material-symbols-rounded" style="font-size:17px;">add</span>Record Delivery
        </button>
      </div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th>Product</th><th>Batch #</th><th>Lot #</th><th>Supplier</th>
                <th>Qty</th><th>Cost/Unit</th><th>Received</th><th>Expiry</th><th>Days Left</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="batchTableBody">
              <tr><td colspan="10" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ══════════════ SUPPLIERS ══════════════ --}}
    <div class="section" id="section-suppliers">
      <div class="search-bar">
        <div class="search-wrap">
          <span class="material-symbols-rounded">search</span>
          <input type="text" id="supplierSearch" placeholder="Search suppliers…" oninput="debounce(loadSuppliers,350)()"/>
        </div>
        <button class="btn-primary" id="btnAddSupplier" onclick="openSupplierModal()">
          <span class="material-symbols-rounded" style="font-size:17px;">add</span>Add Supplier
        </button>
      </div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th>Code</th><th>Supplier Name</th><th>Contact Person</th>
                <th>Phone</th><th>Email</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="supplierTableBody">
              <tr><td colspan="7" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ══════════════ INVOICES ══════════════ --}}
    <div class="section" id="section-invoices">
      <div class="search-bar">
        <div class="search-wrap">
          <span class="material-symbols-rounded">search</span>
          <input type="text" id="invSearch" placeholder="Search by invoice # or customer…" oninput="debounce(loadInvoices,400)()"/>
        </div>
        <select id="invStatusFilter" class="select-field" style="width:135px;" onchange="loadInvoices()">
          <option value="">All Status</option>
          <option value="paid">Paid</option>
          <option value="draft">Draft</option>
          <option value="voided">Voided</option>
        </select>
        <input type="date" id="invDateFrom" class="input-field" style="width:155px;" onchange="loadInvoices()"/>
        <input type="date" id="invDateTo"   class="input-field" style="width:155px;" onchange="loadInvoices()"/>
        <button class="btn-secondary" onclick="exportInvoicesCSV()">
          <span class="material-symbols-rounded" style="font-size:16px;">download</span>Export CSV
        </button>
      </div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th>Invoice #</th><th>Date</th><th>Customer</th><th>Cashier</th>
                <th>Payment</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Status</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="invTableBody">
              <tr><td colspan="10" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
        <div class="pagination" id="invPagination" style="display:none;"></div>
      </div>
    </div>

    {{-- ══════════════ USERS ══════════════ --}}
    <div class="section" id="section-users">
      <div class="search-bar">
        <div style="font-size:13px;color:#9aa3c2;flex:1;">Manage cashier and admin accounts. Customers use their own login via the kiosk.</div>
        <button class="btn-primary" id="btnAddUser" onclick="openUserModal()">
          <span class="material-symbols-rounded" style="font-size:17px;">person_add</span>New Account
        </button>
      </div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <tr><td colspan="5" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ══════════════ SALES REPORT ══════════════ --}}
   <div class="section" id="section-report-sales">
  <div style="font-size:15px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Sales Report</div>
  <div style="font-size:12px;color:#9aa3c2;margin-bottom:18px;">Generate detailed sales analytics for any date range</div>

  {{-- Filter Bar --}}
  <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;margin-bottom:20px;">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div><label class="label">From Date</label><input type="date" id="reportSalesFromDate" class="input-field" style="width:175px;"/></div>
      <div><label class="label">To Date</label><input type="date" id="reportSalesToDate" class="input-field" style="width:175px;"/></div>
      <button class="btn-primary" onclick="loadSalesReport()">
        <span class="material-symbols-rounded" style="font-size:16px;">search</span>Generate Report
      </button>
      <button class="btn-secondary" onclick="exportSalesReportCSV()">
        <span class="material-symbols-rounded" style="font-size:16px;">download</span>Export CSV
      </button>
    </div>
    <div id="reportDateRange" style="font-size:12px;color:#8891b4;margin-top:8px;">Select a date range and click Generate Report</div>
  </div>

  {{-- KPI Cards --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    <div class="stat-card blue"><div class="stat-icon blue"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">receipt_long</span></div><div class="stat-val" id="reportTotalInvoices">—</div><div class="stat-lbl" id="reportInvoicesLbl">Period Invoices</div></div>
    <div class="stat-card green"><div class="stat-icon green"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">payments</span></div><div class="stat-val" id="reportTotalRevenue">—</div><div class="stat-lbl">Period Revenue</div></div>
    <div class="stat-card purple"><div class="stat-icon purple"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">bar_chart</span></div><div class="stat-val" id="reportAOV">—</div><div class="stat-lbl">Avg. Order Value</div></div>
    <div class="stat-card rose"><div class="stat-icon rose"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">cancel</span></div><div class="stat-val" id="reportVoided">—</div><div class="stat-lbl">Voided Orders</div></div>
  </div>

  {{-- Charts Row --}}
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:20px;" id="reportChartsRow" style="display:none;">
    <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;">
      <div class="section-title">Revenue Trend</div>
      <div style="height:220px;"><canvas id="reportTrendChart"></canvas></div>
    </div>
    <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;">
      <div class="section-title">Payment Methods</div>
      <div style="height:180px;"><canvas id="reportPayChart"></canvas></div>
      <div id="reportPayLegend" style="margin-top:12px;display:flex;flex-direction:column;gap:6px;font-size:12px;"></div>
    </div>
  </div>

  {{-- Top Products --}}
  <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;margin-bottom:20px;overflow:hidden;" id="reportTopProdsWrap" style="display:none;">
    <div style="padding:16px 20px;border-bottom:1px solid #eaecf4;font-size:13px;font-weight:700;color:#1a1d2e;">Top Products in Period</div>
    <table class="rtable">
      <thead><tr><th>#</th><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody id="reportTopProdsTbody"></tbody>
    </table>
  </div>

  {{-- Invoice Table --}}
  <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;overflow:hidden;" id="reportInvTableWrap" style="display:none;">
    <div style="padding:16px 20px;border-bottom:1px solid #eaecf4;font-size:13px;font-weight:700;color:#1a1d2e;" id="reportInvTableTitle">Transactions</div>
    <div style="overflow-x:auto;">
      <table class="rtable">
        <thead><tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Payment</th><th>Total</th><th>Status</th></tr></thead>
        <tbody id="reportInvTbody"></tbody>
      </table>
    </div>
  </div>
</div>
      

    {{-- ══════════════ INVENTORY REPORT ══════════════ --}}
    <div class="section" id="section-report-inventory">
      <div style="font-size:15px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Inventory Report</div>
      <div style="font-size:12px;color:#9aa3c2;margin-bottom:18px;">Track stock levels and alerts</div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
        <div class="stat-card blue"><div class="stat-icon blue"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">medication_liquid</span></div><div class="stat-val" id="reportTotalProducts">—</div><div class="stat-lbl">Total Products</div></div>
        <div class="stat-card amber"><div class="stat-icon amber"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">warning</span></div><div class="stat-val" id="reportLowStock">—</div><div class="stat-lbl">Low Stock Items</div></div>
        <div class="stat-card rose"><div class="stat-icon rose"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">schedule</span></div><div class="stat-val" id="reportExpiring">—</div><div class="stat-lbl">Expiring Soon (30d)</div></div>
        <div class="stat-card green"><div class="stat-icon green"><span class="material-symbols-rounded icon-fill" style="font-size:20px;">local_shipping</span></div><div class="stat-val" id="reportTotalSuppliers">—</div><div class="stat-lbl">Active Suppliers</div></div>
      </div>
      <div style="background:white;border-radius:14px;border:1px solid #e8eaf4;padding:20px;box-shadow:0 2px 12px rgba(61,82,213,.04);">
        <div class="section-title">Report Notes</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#6b7494;">
            <span class="material-symbols-rounded" style="font-size:17px;color:#3d52d5;flex-shrink:0;">check_circle</span>
            Low stock items are products with quantity at or below reorder level
          </div>
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#6b7494;">
            <span class="material-symbols-rounded" style="font-size:17px;color:#3d52d5;flex-shrink:0;">check_circle</span>
            Expiring soon shows batches expiring within 30 days
          </div>
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#6b7494;">
            <span class="material-symbols-rounded" style="font-size:17px;color:#3d52d5;flex-shrink:0;">check_circle</span>
            Update reorder levels in product settings to adjust alerts
          </div>
        </div>
      </div>
    </div>

    {{-- ══════════════ CASHIER PERFORMANCE ══════════════ --}}
    <div class="section" id="section-report-cashier">
      <div style="font-size:15px;font-weight:700;color:#1a1d2e;margin-bottom:4px;">Cashier Performance</div>
      <div style="font-size:12px;color:#9aa3c2;margin-bottom:18px;">Monitor cashier activity and sales</div>
      <div class="table-wrap">
        <div style="overflow-x:auto;">
          <table class="rtable">
            <thead>
              <tr>
                <th>Cashier Name</th><th>Today's Invoices</th><th>Today's Revenue</th>
                <th>This Month's Invoices</th><th>This Month's Revenue</th><th>Total Voided</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="cashierPerfTableBody">
              <tr><td colspan="7" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>{{-- /content --}}
</div>
</div>

{{-- ════════════ MODAL: ADD/EDIT PRODUCT ════════════ --}}
<div class="modal-ov" id="productModal" onclick="closeBg(event,'productModal')">
  <div class="modal-box modal-box-lg">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
      <div style="font-size:17px;font-weight:700;color:#1a1d2e;" id="productModalTitle">Add New Product</div>
      <button onclick="closeModal('productModal')" class="btn-secondary" style="width:34px;height:34px;padding:0;border-radius:8px;">
        <span class="material-symbols-rounded" style="font-size:17px;">close</span>
      </button>
    </div>
    <form id="productForm" enctype="multipart/form-data">
      <input type="hidden" id="productId" value=""/>
      <div class="form-grid">
        <div>
          <label class="label">SKU *</label>
          <input type="text" id="pSku" class="input-field" placeholder="e.g. BIOG-500" required/>
        </div>
        <div>
          <label class="label">Barcode</label>
          <input type="text" id="pBarcode" class="input-field" placeholder="Optional"/>
        </div>
        <div class="full">
          <label class="label">Product Name *</label>
          <input type="text" id="pName" class="input-field" required/>
        </div>
        <div>
          <label class="label">Generic Name</label>
          <input type="text" id="pGeneric" class="input-field"/>
        </div>
        <div>
          <label class="label">Brand</label>
          <input type="text" id="pBrand" class="input-field"/>
        </div>
        <div>
          <label class="label">Dosage / Form</label>
          <input type="text" id="pDosage" class="input-field" placeholder="e.g. 500mg, 50ml"/>
        </div>
        <div>
          <label class="label">Category *</label>
          <select id="pCategory" class="select-field">
            <option value="">— Select —</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Supplier</label>
          <select id="pSupplier" class="select-field">
            <option value="">— None —</option>
            @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}">{{ $sup->supplier_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Cost Price (₱) *</label>
          <input type="number" id="pCost" class="input-field" step="0.01" min="0" required/>
        </div>
        <div>
          <label class="label">Selling Price (₱) *</label>
          <input type="number" id="pPrice" class="input-field" step="0.01" min="0" required/>
        </div>
        <div>
          <label class="label">Stock Quantity *</label>
          <input type="number" id="pStock" class="input-field" min="0" required/>
        </div>
        <div>
          <label class="label">Reorder Level *</label>
          <input type="number" id="pReorder" class="input-field" min="0" value="10" required/>
        </div>
        <div>
          <label class="label">Tax Rate</label>
          <select id="pTax" class="select-field">
            <option value="">— Default VAT 12% —</option>
            @foreach($tax_rates as $t)
            <option value="{{ $t->id }}">{{ $t->tax_name }} ({{ $t->rate_percentage }}%)</option>
            @endforeach
          </select>
        </div>
        <div style="display:flex;align-items:center;padding-top:22px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:600;color:#3d4466;">
            <input type="checkbox" id="pRequiresRx" style="width:15px;height:15px;accent-color:#3d52d5;cursor:pointer;"/>
            Requires Prescription (Rx)
          </label>
        </div>
        <div class="full">
          <label class="label">Description</label>
          <textarea id="pDesc" class="input-field" rows="2" style="resize:vertical;"></textarea>
        </div>
        <div class="full">
          <label class="label">Usage &amp; Recommendation</label>
          <textarea id="pUsage" class="input-field" rows="2" style="resize:vertical;" placeholder="Dosage instructions shown in kiosk…"></textarea>
        </div>
        <div class="full">
          <label class="label">Product Image</label>
          <div style="display:flex;align-items:center;gap:12px;">
            <input type="file" id="pImage" accept="image/*" onchange="previewImage(this)" style="flex:1;font-size:13px;"/>
            <img id="pImagePreview" src="" alt="" class="img-preview" style="display:none;width:56px;height:56px;"/>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('productModal')" class="btn-secondary">Cancel</button>
        <button type="submit" class="btn-primary">
          <span class="material-symbols-rounded" style="font-size:17px;">save</span>Save Product
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ════════════ MODAL: ADD BATCH ════════════ --}}
<div class="modal-ov" id="batchModal" onclick="closeBg(event,'batchModal')">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
      <div style="font-size:17px;font-weight:700;color:#1a1d2e;">Record Stock Delivery</div>
      <button onclick="closeModal('batchModal')" class="btn-secondary" style="width:34px;height:34px;padding:0;border-radius:8px;">
        <span class="material-symbols-rounded" style="font-size:17px;">close</span>
      </button>
    </div>
    <form id="batchForm">
      <div class="form-grid">
        <div class="full">
          <label class="label">Product *</label>
          <select id="bProduct" class="select-field" required><option value="">— Select Product —</option></select>
        </div>
        <div>
          <label class="label">Batch Number *</label>
          <input type="text" id="bBatch" class="input-field" placeholder="e.g. BT-2026-001" required/>
        </div>
        <div>
          <label class="label">Lot Number</label>
          <input type="text" id="bLot" class="input-field" placeholder="Optional"/>
        </div>
        <div>
          <label class="label">Expiry Date *</label>
          <input type="date" id="bExpiry" class="input-field" required/>
        </div>
        <div>
          <label class="label">Quantity Received *</label>
          <input type="number" id="bQty" class="input-field" min="1" step="1" required/>
        </div>
        <div>
          <label class="label">Cost Price per Unit (₱)</label>
          <input type="number" id="bCost" class="input-field" step="0.01" min="0"/>
        </div>
        <div>
          <label class="label">Received Date</label>
          <input type="date" id="bReceived" class="input-field" value="{{ now()->format('Y-m-d') }}"/>
        </div>
        <div>
          <label class="label">Supplier</label>
          <select id="bSupplier" class="select-field" onchange="updateSupplierDisplay()">
            <option value="">— None —</option>
            @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}">{{ $sup->supplier_name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="label">Selected Supplier</label>
          <input type="text" id="bSupplierDisplay" class="input-field" readonly style="background:#f5f6fa;color:#8891b4;" placeholder="No supplier selected"/>
        </div>
        <div class="full">
          <label class="label">Notes</label>
          <input type="text" id="bNotes" class="input-field" placeholder="Optional notes about this delivery"/>
        </div>
        <div class="full">
          <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:13px 15px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
              <input type="checkbox" id="bUpdateStock" checked style="width:16px;height:16px;accent-color:#16a34a;cursor:pointer;"/>
              <div>
                <div style="font-size:13.5px;font-weight:700;color:#166534;">Immediately update product stock</div>
                <div style="font-size:11.5px;color:#16a34a;margin-top:2px;">Add batch quantity to the product's current stock count right away</div>
              </div>
            </label>
          </div>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('batchModal')" class="btn-secondary">Cancel</button>
        <button type="submit" class="btn-primary">
          <span class="material-symbols-rounded" style="font-size:17px;">inventory_2</span>Save Batch
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ════════════ MODAL: SUPPLIER ════════════ --}}
<div class="modal-ov" id="supplierModal" onclick="closeBg(event,'supplierModal')">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
      <div style="font-size:17px;font-weight:700;color:#1a1d2e;" id="supplierModalTitle">Add Supplier</div>
      <button onclick="closeModal('supplierModal')" class="btn-secondary" style="width:34px;height:34px;padding:0;border-radius:8px;">
        <span class="material-symbols-rounded" style="font-size:17px;">close</span>
      </button>
    </div>
    <form id="supplierForm">
      <input type="hidden" id="supplierId" value=""/>
      <div class="form-grid">
        <div>
          <label class="label">Supplier Code *</label>
          <input type="text" id="sCode" class="input-field" placeholder="e.g. UNILAB" required/>
        </div>
        <div>
          <label class="label">Supplier Name *</label>
          <input type="text" id="sName" class="input-field" required/>
        </div>
        <div>
          <label class="label">Contact Person</label>
          <input type="text" id="sContact" class="input-field"/>
        </div>
        <div>
          <label class="label">Phone</label>
          <input type="text" id="sPhone" class="input-field"/>
        </div>
        <div>
          <label class="label">Email</label>
          <input type="email" id="sEmail" class="input-field"/>
        </div>
        <div id="sActiveWrap" style="display:none;">
          <label class="label">Status</label>
          <select id="sActive" class="select-field">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        <div class="full">
          <label class="label">Address</label>
          <textarea id="sAddress" class="input-field" rows="2" style="resize:vertical;"></textarea>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('supplierModal')" class="btn-secondary">Cancel</button>
        <button type="submit" class="btn-primary">
          <span class="material-symbols-rounded" style="font-size:17px;">save</span>Save Supplier
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ════════════ MODAL: VOID INVOICE ════════════ --}}
<div class="modal-ov" id="voidInvModal" onclick="closeBg(event,'voidInvModal')">
  <div class="modal-box" style="max-width:480px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
      <div style="width:42px;height:42px;border-radius:11px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;flex-shrink:0;">
        <span class="material-symbols-rounded icon-fill" style="font-size:22px;">cancel</span>
      </div>
      <div>
        <div style="font-size:16px;font-weight:700;color:#1a1d2e;">Void Invoice</div>
        <div style="font-size:12px;color:#9aa3c2;" id="voidInvSubtitle">Invoice —</div>
      </div>
    </div>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:11px 13px;font-size:12.5px;color:#92400e;margin-bottom:16px;display:flex;gap:8px;align-items:flex-start;">
      <span class="material-symbols-rounded" style="font-size:16px;flex-shrink:0;margin-top:1px;">warning</span>
      Voiding a <strong>paid</strong> invoice will automatically restore stock quantities for all items in that invoice.
    </div>
    <div style="margin-bottom:16px;">
      <label class="label">Reason for voiding *</label>
      <textarea id="voidInvReason" class="input-field" rows="3" style="resize:none;" placeholder="Required — state the reason clearly…"></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button onclick="closeModal('voidInvModal')" class="btn-secondary" style="flex:1;">Cancel</button>
      <button onclick="submitVoidInvoice()" style="flex:1;height:42px;border-radius:10px;background:#dc2626;border:none;color:white;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px;">
        <span class="material-symbols-rounded" style="font-size:16px;">cancel</span>Void Invoice
      </button>
    </div>
  </div>
</div>

{{-- ════════════ MODAL: INVOICE ITEMS ════════════ --}}
<div class="modal-ov" id="invItemsModal" onclick="closeBg(event,'invItemsModal')">
  <div class="modal-box modal-box-lg">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <div style="font-size:16px;font-weight:700;color:#1a1d2e;" id="invItemsTitle">Invoice Items</div>
      <button onclick="closeModal('invItemsModal')" class="btn-secondary" style="width:34px;height:34px;padding:0;border-radius:8px;">
        <span class="material-symbols-rounded" style="font-size:17px;">close</span>
      </button>
    </div>
    <div style="overflow-x:auto;">
      <table class="rtable">
        <thead>
          <tr>
            <th>Product</th><th>Generic</th><th>UOM</th><th>Qty</th>
            <th>Unit Price</th><th>Tax %</th><th>Subtotal</th><th>Tax</th><th>Line Total</th>
          </tr>
        </thead>
        <tbody id="invItemsTbody"></tbody>
      </table>
    </div>
  </div>
</div>

{{-- ════════════ MODAL: USER ════════════ --}}
<div class="modal-ov" id="userModal" onclick="closeBg(event,'userModal')">
  <div class="modal-box" style="max-width:500px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
      <div style="font-size:17px;font-weight:700;color:#1a1d2e;" id="userModalTitle">New Cashier Account</div>
      <button onclick="closeModal('userModal')" class="btn-secondary" style="width:34px;height:34px;padding:0;border-radius:8px;">
        <span class="material-symbols-rounded" style="font-size:17px;">close</span>
      </button>
    </div>
    <form id="userForm">
      <input type="hidden" id="userId" value=""/>
      <div style="display:flex;flex-direction:column;gap:13px;">
        <div>
          <label class="label">Full Name *</label>
          <input type="text" id="uName" class="input-field" required/>
        </div>
        <div>
          <label class="label">Email Address *</label>
          <input type="email" id="uEmail" class="input-field" required/>
        </div>
        <div>
          <label class="label">Role *</label>
          <select id="uRole" class="select-field">
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div>
          <label class="label" id="uPassLabel">Password *</label>
          <input type="password" id="uPassword" class="input-field" placeholder="Min. 8 characters"/>
        </div>
        <div>
          <label class="label">Confirm Password</label>
          <input type="password" id="uPasswordConfirm" class="input-field"/>
        </div>
        <div id="uEditNote" style="display:none;background:#eef1ff;border:1px solid #c7d2fe;border-radius:10px;padding:10px 13px;font-size:12px;color:#3d52d5;display:none;align-items:center;gap:8px;">
          <span class="material-symbols-rounded" style="font-size:15px;flex-shrink:0;">info</span>
          Leave password fields blank to keep the current password unchanged.
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:22px;justify-content:flex-end;">
        <button type="button" onclick="closeModal('userModal')" class="btn-secondary">Cancel</button>
        <button type="submit" class="btn-primary">
          <span class="material-symbols-rounded" style="font-size:17px;">save</span>Save Account
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ════════════ TOAST ════════════ --}}
<div class="toast" id="toast">
  <span class="material-symbols-rounded" id="toastIcon" style="font-size:17px;flex-shrink:0;">check_circle</span>
  <span id="toastMsg"></span>
</div>
<script>
    var CSRF = document.querySelector('meta[name=csrf-token]').content;

    var R = {
    products:     "{{ route('admin.products.list') }}",
    prodStore:    "{{ route('admin.products.store') }}",
    batches:      "{{ route('admin.batches.list') }}",
    batchStore:   "{{ route('admin.batches.store') }}",
    suppliers:    "{{ route('admin.suppliers.list') }}",
    supStore:     "{{ route('admin.suppliers.store') }}",
    invoices:     "{{ route('admin.invoices.list') }}",
    invExport:    "{{ route('admin.invoices.export') }}",
    users:        "{{ route('admin.users.list') }}",
    userStore:    "{{ route('admin.users.store') }}",

    prodShow:     function(id){ return "{{ url('admin/products') }}/"  + id; },
    prodUpdate:   function(id){ return "{{ url('admin/products') }}/"  + id; },
    prodToggle:   function(id){ return "{{ url('admin/products') }}/"  + id + '/toggle'; },
    prodImage:    function(id){ return "{{ url('admin/products') }}/"  + id + '/image'; },
    batchConfirm: function(id){ return "{{ url('admin/batches') }}/"   + id + '/confirm-stock'; },
    batchDelete:  function(id){ return "{{ url('admin/batches') }}/"   + id; },
    supUpdate:    function(id){ return "{{ url('admin/suppliers') }}/" + id; },
    invVoid:      function(id){ return "{{ url('admin/invoices') }}/"  + id + '/void'; },
    invItems:     function(id){ return "{{ url('admin/invoices') }}/"  + id + '/items'; },
    userUpdate:   function(id){ return "{{ url('admin/users') }}/"     + id; },
    printInv:     function(id){ return "{{ url('cashier/invoice') }}/" + id + '/print'; },
    cashierView:  function(id){ return "{{ url('admin/cashier') }}/"   + id + '/view'; },
};
    // Stats for inventory report
    var STATS = {
        total_products:   {{ $stats['total_products'] }},
        low_stock:        {{ $stats['low_stock'] }},
        expiring_30:      {{ $stats['expiring_30'] }},
        total_suppliers:  {{ $stats['total_suppliers'] }},
    };
</script>
<script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>