(function () {

  var labels   = weekly.map(function(d){ return d.date; });
  var revenues = weekly.map(function(d){ return d.total; });
  var txCounts = weekly.map(function(d){ return d.count; });

  var BLUE  = '#3d52d5';
  var GREEN = '#16a34a';
  var SLATE = '#8891b4';
  var CHART_COLORS = ['#3d52d5','#16a34a','#d97706','#e11d48','#0891b2','#7c3aed','#059669'];

  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.color       = SLATE;

  // ── Bar Chart: 7-Day Revenue ───────────────────────────────────────
  var barCtx = document.getElementById('barChart');
  if (barCtx) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Revenue (₱)',
          data: revenues,
          backgroundColor: revenues.map(function(_, i) {
            return i === revenues.length - 1 ? BLUE : 'rgba(61,82,213,0.18)';
          }),
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) { return ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits:2}); }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: {
            grid: { color: '#f1f3f9' },
            border: { display: false },
            ticks: {
              callback: function(v) { return v >= 1000 ? '₱'+(v/1000).toFixed(1)+'k' : '₱'+v; }
            }
          }
        }
      }
    });
  }

  // ── Line Chart: Transaction Trend ──────────────────────────────────
  var lineCtx = document.getElementById('lineChart');
  if (lineCtx) {
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Transactions',
          data: txCounts,
          borderColor: GREEN,
          backgroundColor: 'rgba(22,163,74,0.08)',
          borderWidth: 2.5,
          pointBackgroundColor: GREEN,
          pointRadius: 4,
          pointHoverRadius: 6,
          fill: true,
          tension: 0.4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) { return ' ' + ctx.parsed.y + ' transaction(s)'; }
            }
          }
        },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: {
            grid: { color: '#f1f3f9' },
            border: { display: false },
            ticks: { stepSize: 1, precision: 0 }
          }
        }
      }
    });
  }

  // ── Donut Chart: Payment Methods ───────────────────────────────────
  var donutCtx = document.getElementById('donutChart');
  if (donutCtx && byPay.length) {
    var payLabels = byPay.map(function(p){ return p.method_name; });
    var payTotals = byPay.map(function(p){ return parseFloat(p.total); });
    var grandPay  = payTotals.reduce(function(a,b){ return a+b; }, 0);

    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: payLabels,
        datasets: [{
          data: payTotals,
          backgroundColor: CHART_COLORS.slice(0, payLabels.length),
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                var pct = grandPay > 0 ? ((ctx.parsed / grandPay) * 100).toFixed(1) : 0;
                return ' ₱' + ctx.parsed.toLocaleString('en-PH', {minimumFractionDigits:2}) + ' (' + pct + '%)';
              }
            }
          }
        }
      }
    });

    var legend = document.getElementById('donutLegend');
    if (legend) {
      legend.innerHTML = payLabels.map(function(lbl, i) {
        var pct = grandPay > 0 ? ((payTotals[i] / grandPay) * 100).toFixed(1) : 0;
        return '<div style="display:flex;align-items:center;gap:8px;">' +
          '<div style="width:10px;height:10px;border-radius:3px;background:' + CHART_COLORS[i] + ';flex-shrink:0;"></div>' +
          '<div>' +
            '<div style="font-size:12px;font-weight:600;color:#1a1d2e;">' + lbl + '</div>' +
            '<div style="font-size:11px;color:#8891b4;">' + pct + '%</div>' +
          '</div>' +
        '</div>';
      }).join('');
    }
  }

  // ── Top Products Horizontal Bar ────────────────────────────────────
  var topCtx = document.getElementById('topProdChart');
  if (topCtx && topP.length) {
    var topLabels = topP.map(function(p){
      return p.product_name.length > 22 ? p.product_name.substring(0,22)+'…' : p.product_name;
    });
    var topQtys = topP.map(function(p){ return parseFloat(p.qty_sold); });
    var maxQty  = Math.max.apply(null, topQtys);

    new Chart(topCtx, {
      type: 'bar',
      data: {
        labels: topLabels,
        datasets: [{
          label: 'Units Sold',
          data: topQtys,
          backgroundColor: CHART_COLORS.slice(0, topLabels.length),
          borderRadius: 5,
          borderSkipped: false,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) { return ' ' + ctx.parsed.x + ' unit(s)'; }
            }
          }
        },
        scales: {
          x: {
            grid: { color: '#f1f3f9' },
            border: { display: false },
            ticks: { precision: 0, stepSize: 1 },
            max: maxQty + 1,
          },
          y: { grid: { display: false }, border: { display: false } }
        }
      }
    });
  }

})();

// ── Queue state ────────────────────────────────────────────────
var successQueueTicketId = null;
var currentServingId     = null;
var selectedInvoice   = null;
var selectedPayMethod = null;

var SECTION_TITLES = {
  'pending':       'Pending Orders',
   'queue':         'Ready for Pickup',
  'completed':     "Today's Sales",
  'search':        'Search Invoice',
  'inv-search':    'Product Lookup',
  'lowstock':      'Low Stock Alerts',
  'expiring':      'Expiring Soon',
  'sales-summary': 'Sales Summary',
  'shift':         'Shift Report',
};

// ── CLOCK ──────────────────────────────────────────────────────────────
function updateClock() {
  var now = new Date(), h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  var ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  document.getElementById('clock').textContent =
    (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s+' '+ampm;
}
setInterval(updateClock, 1000); updateClock();

// ── SECTION SWITCH ─────────────────────────────────────────────────────
function switchSection(name, btn) {
  document.querySelectorAll('.section').forEach(function(s){ s.classList.remove('active'); });
  var sec = document.getElementById('section-' + name);
  if (sec) sec.classList.add('active');
  document.querySelectorAll('.nav-item').forEach(function(n){ n.classList.remove('active'); });
  if (btn) btn.classList.add('active');
  document.getElementById('topTitle').textContent = SECTION_TITLES[name] || '';
  if (name !== 'pending') {
    clearPanel();
    document.getElementById('rightPanel').style.display = 'none';
  } else {
    document.getElementById('rightPanel').style.display = 'flex';
  }
}

// ── SELECT ORDER ───────────────────────────────────────────────────────
function selectOrder(id) {
  document.querySelectorAll('.order-card').forEach(function(c){ c.classList.remove('selected'); });
  var card = document.getElementById('card-' + id);
  if (card) card.classList.add('selected');

  fetch(ROUTE_GET.replace('__ID__', id), {
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  })
  .then(function(r){ return r.json(); })
  .then(function(inv) { selectedInvoice = inv; renderPanel(inv); })
  .catch(function(){ showToast('Could not load order', 'error'); });
}

function renderPanel(inv) {
  document.getElementById('panelEmpty').style.display = 'none';
  document.getElementById('panelContent').style.display = 'flex';
  document.getElementById('panelInvNo').textContent    = inv.invoice_number;
  document.getElementById('panelCustName').textContent = inv.customer_name || 'Walk-in Customer';
  document.getElementById('panelCustMeta').textContent = (inv.phone || '') + (inv.address ? ' · ' + inv.address : '');

  var hasRx = false;
  var itemsHtml = (inv.items || []).map(function(item) {
    if (item.requires_rx) hasRx = true;
    return '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:#f8f9fd;border-radius:8px;">' +
      '<div>' +
        '<div style="font-size:13px;font-weight:600;color:#1a1d2e;">' + escHtml(item.product_name) +
          (item.requires_rx ? ' <span class="badge-rx" style="vertical-align:2px"><span class="rx-dot"></span>Rx</span>' : '') +
        '</div>' +
        '<div style="font-size:11px;color:#8891b4;">Qty: ' + item.quantity + ' × ₱' + parseFloat(item.unit_price).toFixed(2) + '</div>' +
      '</div>' +
      '<div style="font-size:14px;font-weight:700;color:#1a1d2e;">₱' + parseFloat(item.line_total).toFixed(2) + '</div>' +
    '</div>';
  }).join('');
  document.getElementById('panelItems').innerHTML = itemsHtml;
  document.getElementById('rxSection').style.display = hasRx ? 'block' : 'none';

  document.getElementById('discountSelect').value = '';
  document.getElementById('idNumberWrap').style.display = 'none';
  document.getElementById('tenderedInput').value = '';
  document.getElementById('changeRow').style.display = 'none';
  selectedPayMethod = null;
  document.querySelectorAll('.pay-btn').forEach(function(b){ b.classList.remove('active'); });
  recalc();
}

function clearPanel() {
  selectedInvoice = null;
  document.getElementById('panelEmpty').style.display = 'flex';
  document.getElementById('panelContent').style.display = 'none';
  document.querySelectorAll('.order-card').forEach(function(c){ c.classList.remove('selected'); });
}

// ── PAY METHOD ─────────────────────────────────────────────────────────
function selectPayMethod(btn) {
  document.querySelectorAll('.pay-btn').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  selectedPayMethod = btn.dataset.id;
  recalc();
}

// ── RECALC ─────────────────────────────────────────────────────────────
function recalc() {
  if (!selectedInvoice) return;
  var subtotal = parseFloat(selectedInvoice.subtotal)    || 0;
  var tax      = parseFloat(selectedInvoice.total_tax)   || 0;
  var grand    = parseFloat(selectedInvoice.grand_total) || 0;
  var discAmt  = 0;

  var sel    = document.getElementById('discountSelect');
  var opt    = sel.options[sel.selectedIndex];
  var code   = opt ? opt.dataset.code   : '';
  var method = opt ? opt.dataset.method : '';
  var val    = parseFloat(opt ? opt.dataset.value : 0) || 0;

  document.getElementById('idNumberWrap').style.display =
    (code === 'SENIOR20' || code === 'PWD20') ? 'block' : 'none';

  if (val > 0) {
    if (code === 'SENIOR20' || code === 'PWD20') {
      var vatExemptBase = subtotal / 1.12;
      discAmt = vatExemptBase * (val / 100);
      grand   = vatExemptBase - discAmt;
      tax     = 0;
    } else if (method === 'percentage') {
      discAmt = grand * (val / 100);
      grand   = grand - discAmt;
      tax     = grand * 0.12 / 1.12;
    } else {
      discAmt = val;
      grand   = grand - discAmt;
      tax     = grand * 0.12 / 1.12;
    }
  }

  document.getElementById('calcSub').textContent   = '₱' + subtotal.toFixed(2);
  document.getElementById('calcDisc').textContent  = '— ₱' + discAmt.toFixed(2);
  document.getElementById('calcVat').textContent   = '₱' + tax.toFixed(2);
  document.getElementById('calcTotal').textContent = '₱' + grand.toFixed(2);

  var tendered = parseFloat(document.getElementById('tenderedInput').value) || 0;
  if (tendered > 0) {
    document.getElementById('calcChange').textContent = '₱' + Math.max(0, tendered - grand).toFixed(2);
    document.getElementById('changeRow').style.display = 'flex';
  } else {
    document.getElementById('changeRow').style.display = 'none';
  }
  document.getElementById('btnPay').disabled = !(tendered >= grand && tendered > 0 && !!selectedPayMethod);
}

// ── PROCESS PAYMENT ────────────────────────────────────────────────────
function processPayment() {
  if (!selectedInvoice || !selectedPayMethod) return;
  var sel      = document.getElementById('discountSelect');
  var discId   = sel.value || null;
  var idNum    = document.getElementById('idNumberInput').value.trim();
  var tendered = parseFloat(document.getElementById('tenderedInput').value) || 0;
  var rxNo     = document.getElementById('rxInput') ? document.getElementById('rxInput').value.trim() : '';

  document.getElementById('btnPay').disabled = true;
  document.getElementById('btnPay').innerHTML = '<span class="material-symbols-rounded" style="animation:spin 1s linear infinite">sync</span> Processing...';

  fetch(ROUTE_PAY, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({
      invoice_id:        selectedInvoice.id,
      payment_method_id: selectedPayMethod,
      amount_tendered:   tendered,
      discount_type_id:  discId,
      id_number_used:    idNum,
      prescription_no:   rxNo || null,
    }),
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (data.success) {
       document.getElementById('successInvNo').textContent  = 'Invoice ' + data.invoice_number;
      document.getElementById('successTotal').textContent  = '₱' + parseFloat(data.grand_total).toFixed(2);
      document.getElementById('successChange').textContent = '₱' + parseFloat(data.change_amount).toFixed(2);
      document.getElementById('successPrintBtn').href = ROUTE_PRINT.replace('__ID__', data.invoice_id);
 
      // NEW — show queue info if server returned a ticket
      successQueueTicketId = data.queue_ticket_id || null;
      var queueBox     = document.getElementById('successQueueBox');
      var queueNumEl   = document.getElementById('successQueueNum');
      var callBtn      = document.getElementById('callPickupBtn');
      var callLbl      = document.getElementById('callPickupNumLabel');
      if (data.queue_number && data.queue_ticket_id) {
        queueNumEl.textContent = data.queue_number;
        callLbl.textContent    = data.queue_number;
        queueBox.style.display = 'block';
        callBtn.style.display  = 'flex';
      } else {
        queueBox.style.display = 'none';
        callBtn.style.display  = 'none';
      }
 
      document.getElementById('successModal').classList.add('show');
      loadQueue();
 
      var card = document.getElementById('card-' + selectedInvoice.id);
      if (card) { card.style.transition='all .3s'; card.style.opacity='0'; setTimeout(function(){ card.remove(); }, 300); }
      clearPanel();
    } else {
      showToast(data.error || 'Payment failed', 'error');
      document.getElementById('btnPay').disabled = false;
      document.getElementById('btnPay').innerHTML = '<span class="material-symbols-rounded icon-fill">payments</span> Confirm Payment';
    }
  })
  .catch(function(err) {
    console.error(err);
    showToast('Network error. Please try again.', 'error');
    document.getElementById('btnPay').disabled = false;
    document.getElementById('btnPay').innerHTML = '<span class="material-symbols-rounded icon-fill">payments</span> Confirm Payment';
  });
}

// ── VOID ───────────────────────────────────────────────────────────────
function showVoidModal()  { if (selectedInvoice) document.getElementById('voidModal').classList.add('show'); }
function closeVoidModal(e) {
  if (!e || e.target === document.getElementById('voidModal')) {
    document.getElementById('voidModal').classList.remove('show');
    document.getElementById('voidReason').value = '';
  }
}
function submitVoid() {
  var reason = document.getElementById('voidReason').value.trim();
  if (!reason) { showToast('Please enter a reason', 'error'); return; }
  fetch(ROUTE_VOID, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ invoice_id: selectedInvoice.id, reason: reason }),
  })
  .then(function(r){ return r.json(); })
  .then(function(data) {
    if (data.success) {
      closeVoidModal();
      var card = document.getElementById('card-' + selectedInvoice.id);
      if (card) { card.style.opacity='0'; setTimeout(function(){ card.remove(); }, 300); }
      clearPanel();
      showToast('Order voided successfully', 'success');
    } else {
      showToast(data.error || 'Could not void order', 'error');
    }
  });
}

// ── SUCCESS MODAL ──────────────────────────────────────────────────────
function closeSuccess() {
  document.getElementById('successModal').classList.remove('show');
  location.reload();
}

// ── INVOICE SEARCH ─────────────────────────────────────────────────────
var searchDebounce;
function debounceSearch(val) {
  clearTimeout(searchDebounce);
  if (!val.trim()) {
    document.getElementById('searchEmpty').style.display   = 'block';
    document.getElementById('searchLoading').style.display = 'none';
    document.getElementById('searchTable').style.display   = 'none';
    return;
  }
  document.getElementById('searchEmpty').style.display   = 'none';
  document.getElementById('searchLoading').style.display = 'block';
  document.getElementById('searchTable').style.display   = 'none';
  searchDebounce = setTimeout(function(){ doSearch(val.trim()); }, 350);
}
function doSearch(q) {
  fetch(ROUTE_SEARCH + '?q=' + encodeURIComponent(q), {
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  })
  .then(function(r){ return r.json(); })
  .then(function(rows) {
    document.getElementById('searchLoading').style.display = 'none';
    if (!rows.length) {
      document.getElementById('searchEmpty').style.display = 'block';
      document.getElementById('searchEmpty').innerHTML =
        '<span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">search_off</span>' +
        '<div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">No results found</div>' +
        '<div style="font-size:13px;">Try a different invoice number or customer name</div>';
      return;
    }
    document.getElementById('searchEmpty').style.display = 'none';
    var STATUS_CLASS = { paid:'status-paid', voided:'status-void', draft:'status-draft', issued:'status-draft' };
    var html = rows.map(function(inv) {
      var sc = STATUS_CLASS[inv.status] || 'status-draft';
      var printBtn = (inv.status === 'paid')
        ? '<a href="' + ROUTE_PRINT.replace('__ID__', inv.id) + '" target="_blank" style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:#3d52d5;text-decoration:none;padding:4px 10px;border:1.5px solid #a5b4fc;border-radius:7px;">' +
          '<span class="material-symbols-rounded" style="font-size:14px;">print</span> Print</a>'
        : '—';
      return '<tr>' +
        '<td style="font-family:\'DM Mono\',monospace;font-size:12px;color:#6b7494;">' + escHtml(inv.invoice_number) + '</td>' +
        '<td style="font-weight:600;">' + escHtml(inv.customer_name || 'Walk-in') + '</td>' +
        '<td style="color:#8891b4;">' + escHtml(inv.invoice_date) + '</td>' +
        '<td style="font-weight:700;">₱' + parseFloat(inv.grand_total).toFixed(2) + '</td>' +
        '<td style="color:#8891b4;">' + escHtml(inv.payment_method || '—') + '</td>' +
        '<td><span class="' + sc + '">' + inv.status.charAt(0).toUpperCase() + inv.status.slice(1) + '</span></td>' +
        '<td>' + printBtn + '</td>' +
      '</tr>';
    }).join('');
    document.getElementById('searchTbody').innerHTML = html;
    document.getElementById('searchTable').style.display = 'block';
  })
  .catch(function() {
    document.getElementById('searchLoading').style.display = 'none';
    showToast('Search failed', 'error');
  });
}

// ── PRODUCT LOOKUP (NEW) ───────────────────────────────────────────────
var invSearchDebounce;
function debounceInvSearch(val) {
  clearTimeout(invSearchDebounce);
  var empty   = document.getElementById('invSearchEmpty');
  var loading = document.getElementById('invSearchLoading');
  var results = document.getElementById('invSearchResults');

  if (!val.trim() || val.trim().length < 2) {
    empty.style.display   = 'block';
    loading.style.display = 'none';
    results.style.display = 'none';
    return;
  }
  empty.style.display   = 'none';
  loading.style.display = 'block';
  results.style.display = 'none';
  invSearchDebounce = setTimeout(function(){ doInvSearch(val.trim()); }, 300);
}

function doInvSearch(q) {
  fetch(ROUTE_PROD_LOOKUP + '?q=' + encodeURIComponent(q), {
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  })
  .then(function(r){ return r.json(); })
  .then(function(rows) {
    var loading = document.getElementById('invSearchLoading');
    var results = document.getElementById('invSearchResults');
    var empty   = document.getElementById('invSearchEmpty');
    loading.style.display = 'none';

    if (!rows.length) {
      empty.style.display = 'block';
      empty.innerHTML =
        '<span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">search_off</span>' +
        '<div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">No products found</div>' +
        '<div style="font-size:13px;">Try a different name, brand, or SKU</div>';
      return;
    }

    document.getElementById('invResultCount').textContent = rows.length + ' result(s) found';
    document.getElementById('invResultsList').innerHTML = rows.map(function(p) {
      var stock    = parseInt(p.stock_quantity) || 0;
      var reorder  = parseInt(p.reorder_level)  || 0;
      var pillCls  = stock === 0 ? 'out' : (stock <= reorder ? 'low-stock' : 'in-stock');
      var pillText = stock === 0 ? 'Out of Stock' : (stock <= reorder ? 'Low Stock (' + stock + ')' : 'In Stock (' + stock + ')');

      return '<div class="inv-result-row">' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-size:14px;font-weight:700;color:#1a1d2e;display:flex;align-items:center;gap:6px;">' +
            escHtml(p.product_name) +
            (p.requires_rx ? '<span class="badge-rx" style="font-size:9px;"><span class="rx-dot"></span>Rx</span>' : '') +
          '</div>' +
          '<div style="font-size:11px;color:#8891b4;margin-top:2px;">' +
            (p.generic_name ? escHtml(p.generic_name) + ' · ' : '') +
            (p.brand        ? escHtml(p.brand)         + ' · ' : '') +
            escHtml(p.category) +
          '</div>' +
          '<div style="font-family:\'DM Mono\',monospace;font-size:10px;color:#c4c9dd;margin-top:2px;">' + escHtml(p.sku) + '</div>' +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:14px;flex-shrink:0;">' +
          '<div style="text-align:right;">' +
            '<div style="font-size:15px;font-weight:700;color:#1a1d2e;">₱' + parseFloat(p.selling_price).toFixed(2) + '</div>' +
            '<div style="font-size:10px;color:#8891b4;">per unit</div>' +
          '</div>' +
          '<span class="inv-stock-pill ' + pillCls + '">' + pillText + '</span>' +
        '</div>' +
      '</div>';
    }).join('');

    results.style.display = 'block';
  })
  .catch(function() {
    document.getElementById('invSearchLoading').style.display = 'none';
    showToast('Product search failed', 'error');
  });
}

// ── HELPERS ────────────────────────────────────────────────────────────
function refreshPage() { location.reload(); }
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
var toastTimer;
function showToast(msg, type) {
  var t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.className = 'toast show' + (type ? ' ' + type : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(function(){ t.className = 'toast'; }, 3500);
}

var style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
 // ── QUEUE FUNCTIONS ────────────────────────────────────────────
 
    // Load / refresh the queue list
    function loadQueue() {
      fetch(ROUTE_QUEUE_LIST, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
        .then(function(r){ return r.json(); })
        .then(function(tickets) {
          renderQueueList(tickets);
          // Update nav badge
          var paid = tickets.filter(function(t){ return t.status === 'paid'; }).length;
          var badge = document.getElementById('queueNavBadge');
          badge.textContent = paid;
          badge.style.display = paid > 0 ? 'inline-flex' : 'none';
        })
        .catch(function(){ showToast('Could not load queue', 'error'); });
    }
 
    function renderQueueList(tickets) {
      var serving = tickets.find(function(t){ return t.status === 'serving'; });
      var waiting = tickets.filter(function(t){ return t.status === 'paid'; });
 
      // Banner
      var banner = document.getElementById('nowServingBanner');
      if (serving) {
        currentServingId = serving.id;
        document.getElementById('nowServingNum').textContent  = serving.queue_number;
        document.getElementById('nowServingName').textContent = serving.customer_name || '';
        banner.style.display = 'flex';
      } else {
        currentServingId = null;
        banner.style.display = 'none';
      }
 
      // List
      var listEl = document.getElementById('queueList');
      if (!waiting.length && !serving) {
        listEl.innerHTML =
          '<div style="background:white;border-radius:16px;border:1.5px dashed #dde1ec;padding:60px;text-align:center;color:#8891b4;">' +
            '<span class="material-symbols-rounded" style="font-size:48px;opacity:.25;display:block;margin-bottom:12px;">local_pharmacy</span>' +
            '<div style="font-size:15px;font-weight:600;color:#1a1d2e;margin-bottom:4px;">Queue is empty</div>' +
            '<div style="font-size:13px;">Paid orders will appear here once payment is processed</div>' +
          '</div>';
        return;
      }
 
      listEl.innerHTML = '<div style="display:flex;flex-direction:column;gap:10px;">' +
        waiting.map(function(t, i) {
          return '<div style="background:white;border:1.5px solid #eaecf4;border-radius:14px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;">' +
            '<div style="display:flex;align-items:center;gap:16px;">' +
              '<div style="font-size:32px;font-weight:800;color:#002045;font-family:\'DM Mono\',monospace;letter-spacing:-.01em;">' + escHtml(t.queue_number) + '</div>' +
              '<div>' +
                '<div style="font-size:14px;font-weight:600;color:#1a1d2e;">' + escHtml(t.customer_name || 'Customer') + '</div>' +
                '<div style="font-size:12px;color:#8891b4;">Waiting for pickup</div>' +
              '</div>' +
            '</div>' +
            '<div style="display:flex;gap:8px;">' +
              (i === 0
                ? '<button onclick="callTicket(' + t.id + ')" style="padding:8px 18px;background:#002045;border:none;border-radius:9px;color:white;font-family:\'DM Sans\',sans-serif;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;">' +
                    '<span class="material-symbols-rounded" style="font-size:16px;">campaign</span>Call for Pickup</button>'
                : '<button onclick="callTicket(' + t.id + ')" style="padding:8px 18px;background:#f5f6fa;border:1.5px solid #eaecf4;border-radius:9px;color:#6b7494;font-family:\'DM Sans\',sans-serif;font-size:13px;font-weight:600;cursor:pointer;">Call</button>'
              ) +
              '<button onclick="skipTicket(' + t.id + ')" style="padding:8px 12px;background:white;border:1.5px solid #fca5a5;border-radius:9px;color:#dc2626;font-family:\'DM Sans\',sans-serif;font-size:13px;font-weight:600;cursor:pointer;" title="Skip">Skip</button>' +
            '</div>' +
          '</div>';
        }).join('') +
      '</div>';
    }
 
    function callTicket(ticketId) {
      fetch(ROUTE_QUEUE_CALL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId }),
      })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (data.success) {
          showToast('Now calling ' + data.queue_number, 'success');
          loadQueue();
        } else {
          showToast('Could not call ticket', 'error');
        }
      });
    }
 
    function markDone(ticketId) {
      if (!ticketId) return;
      fetch(ROUTE_QUEUE_DONE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId }),
      })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        if (data.success) { showToast('Ticket marked done', 'success'); loadQueue(); }
      });
    }
 
    function skipTicket(ticketId) {
      fetch(ROUTE_QUEUE_SKIP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId }),
      })
      .then(function(r){ return r.json(); })
      .then(function(data) { if (data.success) { showToast('Ticket skipped', ''); loadQueue(); } });
    }
 
    function resetQueue() {
      if (!confirm('Reset the entire queue for today? This cannot be undone.')) return;
      fetch(ROUTE_QUEUE_RESET, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({}),
      })
      .then(function(r){ return r.json(); })
      .then(function(data) { if (data.success) { showToast('Queue reset', 'success'); loadQueue(); } });
    }
 
    // Called from the "Call for Pickup" button on the payment success modal
    function callFromSuccess() {
      if (!successQueueTicketId) return;
      callTicket(successQueueTicketId);
      // Switch to queue section so cashier can see the board
      var queueNavBtn = document.getElementById('navQueue');
      if (queueNavBtn) { switchSection('queue', queueNavBtn); loadQueue(); }
      closeSuccess();
    }
 
    // Auto-load queue when section opens
    var origSwitchSection = switchSection;
    switchSection = function(name, btn) {
      origSwitchSection(name, btn);
      if (name === 'queue') loadQueue();
    };
 
    // Refresh queue badge every 30 seconds in background
    setInterval(function() {
      fetch(ROUTE_QUEUE_LIST, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
        .then(function(r){ return r.json(); })
        .then(function(tickets) {
          var paid = tickets.filter(function(t){ return t.status === 'paid'; }).length;
          var badge = document.getElementById('queueNavBadge');
          badge.textContent = paid;
          badge.style.display = paid > 0 ? 'inline-flex' : 'none';
        }).catch(function(){});
    }, 30000);
 