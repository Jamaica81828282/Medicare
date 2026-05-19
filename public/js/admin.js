// ── CLOCK ───────────────────────────────────────────────────
function tick(){
  var n=new Date(),h=n.getHours(),m=n.getMinutes(),s=n.getSeconds(),p=h>=12?'PM':'AM';
  h=h%12||12;
  document.getElementById('clock').textContent=(h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s+' '+p;
}
setInterval(tick,1000); tick();

// ── SECTION SWITCH ──────────────────────────────────────────
var TITLES={dashboard:'Dashboard',products:'Products',batches:'Batch Entry',suppliers:'Suppliers',alerts:'Stock Alerts',invoices:'Invoice History',users:'Cashier Accounts','report-sales':'Sales Report','report-inventory':'Inventory Report','report-cashier':'Cashier Performance'};
function switchSection(name,btn){
  document.querySelectorAll('.section').forEach(function(s){s.classList.remove('active');});
  document.getElementById('section-'+name).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(function(n){n.classList.remove('active');});
  if(btn) btn.classList.add('active');
  document.getElementById('topTitle').textContent=TITLES[name]||'';
  if(name==='products')           loadProducts();
  if(name==='batches')            { loadBatches(); loadProductsForSelect(); }
  if(name==='suppliers')          loadSuppliers();
  if(name==='invoices')           loadInvoices();
  if(name==='users')              loadUsers();
  if(name==='report-sales')       loadSalesReport();
  if(name==='report-inventory')   loadInventoryReport();
  if(name==='report-cashier')     loadCashierPerformanceReport();
}

// ── DEBOUNCE ────────────────────────────────────────────────
var _timers={};
function debounce(fn,ms){ return function(){ clearTimeout(_timers[fn]); _timers[fn]=setTimeout(fn,ms); }; }

// ── TOAST ───────────────────────────────────────────────────
var _tt;
function showToast(msg,type){
  var t=document.getElementById('toast');
  var icons={success:'check_circle',error:'error',warning:'warning','':`notifications`};
  document.getElementById('toastMsg').textContent=msg;
  document.getElementById('toastIcon').textContent=icons[type||'']||'notifications';
  t.className='toast show'+(type?' '+type:'');
  clearTimeout(_tt); _tt=setTimeout(function(){t.className='toast';},3500);
}

function resolveAlert(id){
  if(!confirm('Resolve this alert?')) return;
  fetch(R.alertResolve(id), { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
  .then(function(r){ return r.json(); })
  .then(function(data){
    if(data.success){
      showToast('Alert resolved','success');
      setTimeout(function(){ location.reload(); }, 300);
    } else {
      showToast(data.error||'Could not resolve alert','error');
    }
  })
  .catch(function(){ showToast('Could not resolve alert','error'); });
}

window.resolveAlert = resolveAlert;

// ── MODALS ──────────────────────────────────────────────────
function closeModal(id){ document.getElementById(id).classList.remove('show'); }
function closeBg(e,id){ if(e.target===document.getElementById(id)) closeModal(id); }
function openModal(id){ document.getElementById(id).classList.add('show'); }

// ── ESCAPE HTML ─────────────────────────────────────────────
function escH(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ════════════════════════════════════════════
//  PRODUCTS
// ════════════════════════════════════════════
function loadProducts(){
  var q  = document.getElementById('prodSearch').value;
  var c  = document.getElementById('prodCatFilter').value;
  var st = document.getElementById('prodStatusFilter').value;
  var url= R.products+'?q='+encodeURIComponent(q)+'&category='+c+'&status='+st;

  document.getElementById('prodTableBody').innerHTML='<tr><td colspan="10" class="empty-state"><span class="material-symbols-rounded spin">refresh</span></td></tr>';

  fetch(url,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
  .then(function(rows){
    var tb=document.getElementById('prodTableBody');
    if(!rows.length){
      tb.innerHTML='<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-rounded">search_off</span><p>No products found</p></div></td></tr>';
      return;
    }
    tb.innerHTML=rows.map(function(p){
      var stockColor=p.stock_quantity===0?'#dc2626':(p.stock_quantity<=p.reorder_level?'#d97706':'#1a1d2e');
      var img=p.has_image
        ?'<img src="'+R.prodImage(p.id)+'" alt="'+escH(p.product_name)+'" class="img-preview" style="display:block;" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">'
         +'<div class="img-preview" style="display:none;align-items:center;justify-content:center;color:#c4c9dd;"><span class="material-symbols-rounded" style="font-size:18px;">image_not_supported</span></div>'
        :'<div class="img-preview" style="display:flex;align-items:center;justify-content:center;color:#c4c9dd;"><span class="material-symbols-rounded" style="font-size:18px;">image_not_supported</span></div>';
      var isActive=parseInt(p.is_active)===1;
      return '<tr>'+
        '<td>'+img+'</td>'+
        '<td style="font-family:\'DM Mono\',monospace;font-size:11.5px;color:#6b7494;font-weight:500;">'+escH(p.sku)+'</td>'+
        '<td><div style="font-weight:600;color:#1a1d2e;font-size:13px;">'+escH(p.product_name)+'</div>'+
          '<div style="font-size:11px;color:#9aa3c2;margin-top:1px;">'+escH(p.generic_name||'')+'</div>'+
          (p.requires_rx?'<span class="badge-rx" style="margin-top:3px;display:inline-flex;align-items:center;gap:3px;"><span class="material-symbols-rounded" style="font-size:10px;">medication</span>Rx</span>':'')+
        '</td>'+
        '<td style="font-size:12px;color:#6b7494;">'+escH(p.category_name||'—')+'</td>'+
        '<td style="font-weight:700;font-size:13px;">₱'+parseFloat(p.selling_price).toFixed(2)+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">₱'+parseFloat(p.cost_price).toFixed(2)+'</td>'+
        '<td style="font-weight:700;color:'+stockColor+';">'+p.stock_quantity+'</td>'+
        '<td style="color:#9aa3c2;font-size:12px;">'+p.reorder_level+'</td>'+
        '<td><span class="badge '+(isActive?'badge-active':'badge-inactive')+'">'+(isActive?'Active':'Inactive')+'</span></td>'+
        '<td><div style="display:flex;gap:5px;flex-wrap:nowrap;">'+
          '<button class="btn-sm blue" onclick="editProduct('+p.id+')">'+
            '<span class="material-symbols-rounded">edit</span>Edit</button>'+
          '<button class="btn-sm '+(isActive?'red':'green')+'" onclick="toggleProduct('+p.id+','+isActive+')">'+
            '<span class="material-symbols-rounded">'+(isActive?'block':'check_circle')+'</span>'+(isActive?'Disable':'Enable')+
          '</button>'+
        '</div></td>'+
      '</tr>';
    }).join('');
  })
  .catch(function(err){
    document.getElementById('prodTableBody').innerHTML='<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-rounded">error</span><p>'+err.message+'</p></div></td></tr>';
    showToast('Failed to load products','error');
  });
}

function openProductModal(){
  document.getElementById('productId').value='';
  document.getElementById('productForm').reset();
  document.getElementById('pImagePreview').style.display='none';
  document.getElementById('pImagePreview').src='';
  document.getElementById('productModalTitle').textContent='Add New Product';
  document.getElementById('pReorder').value='10';
  openModal('productModal');
}

function editProduct(id){
  fetch(R.prodShow(id),{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
  .then(function(p){
    document.getElementById('productModalTitle').textContent='Edit Product';
    document.getElementById('productId').value   = p.id;
    document.getElementById('pSku').value        = p.sku||'';
    document.getElementById('pBarcode').value    = p.barcode||'';
    document.getElementById('pName').value       = p.product_name||'';
    document.getElementById('pGeneric').value    = p.generic_name||'';
    document.getElementById('pBrand').value      = p.brand||'';
    document.getElementById('pDosage').value     = p.dosage||'';
    document.getElementById('pCategory').value   = p.category_id||'';
    document.getElementById('pSupplier').value   = p.supplier_id||'';
    document.getElementById('pCost').value       = p.cost_price;
    document.getElementById('pPrice').value      = p.selling_price;
    document.getElementById('pStock').value      = p.stock_quantity;
    document.getElementById('pReorder').value    = p.reorder_level;
    document.getElementById('pTax').value        = p.tax_rate_id||'';
    document.getElementById('pRequiresRx').checked = !!parseInt(p.requires_rx);
    document.getElementById('pDesc').value       = p.description||'';
    document.getElementById('pUsage').value      = p.usage_recommendation||'';
    var prev=document.getElementById('pImagePreview');
    if(p.has_image){
      prev.src=R.prodImage(p.id)+'?t='+Date.now();
      prev.style.display='block';
    } else {
      prev.style.display='none';
      prev.src='';
    }
    openModal('productModal');
  })
  .catch(function(err){ showToast('Failed to load product: '+err.message,'error'); });
}

document.getElementById('productForm').addEventListener('submit',function(e){
  e.preventDefault();
  var id=document.getElementById('productId').value;
  var fd=new FormData();
  fd.append('sku',                  document.getElementById('pSku').value);
  fd.append('barcode',              document.getElementById('pBarcode').value);
  fd.append('product_name',         document.getElementById('pName').value);
  fd.append('generic_name',         document.getElementById('pGeneric').value);
  fd.append('brand',                document.getElementById('pBrand').value);
  fd.append('dosage',               document.getElementById('pDosage').value);
  fd.append('category_id',          document.getElementById('pCategory').value);
  fd.append('supplier_id',          document.getElementById('pSupplier').value);
  fd.append('cost_price',           document.getElementById('pCost').value);
  fd.append('selling_price',        document.getElementById('pPrice').value);
  fd.append('stock_quantity',       document.getElementById('pStock').value);
  fd.append('reorder_level',        document.getElementById('pReorder').value);
  fd.append('tax_rate_id',          document.getElementById('pTax').value);
  fd.append('requires_rx',          document.getElementById('pRequiresRx').checked?'1':'0');
  fd.append('description',          document.getElementById('pDesc').value);
  fd.append('usage_recommendation', document.getElementById('pUsage').value);
  var img=document.getElementById('pImage').files[0];
  if(img) fd.append('image',img);
  fd.append('_token',CSRF);
  if(id) fd.append('_method','PUT');

  var btn=document.querySelector('#productForm button[type=submit]');
  btn.disabled=true; btn.innerHTML='<span class="material-symbols-rounded spin" style="font-size:16px;">refresh</span> Saving…';

  fetch(id?R.prodUpdate(id):R.prodStore,{method:'POST',body:fd})
  .then(function(r){return r.json();})
  .then(function(data){
    btn.disabled=false; btn.innerHTML='<span class="material-symbols-rounded" style="font-size:17px;">save</span>Save Product';
    if(data.success){ showToast('Product saved successfully','success'); closeModal('productModal'); loadProducts(); }
    else showToast(data.errors?Object.values(data.errors)[0][0]:(data.error||data.message||'Error saving product'),'error');
  })
  .catch(function(){ btn.disabled=false; btn.innerHTML='<span class="material-symbols-rounded" style="font-size:17px;">save</span>Save Product'; showToast('Network error','error'); });
});

function toggleProduct(id,isCurrentlyActive){
  var action=isCurrentlyActive?'disable':'enable';
  if(!confirm('Are you sure you want to '+action+' this product?')) return;
  fetch(R.prodToggle(id),{method:'PATCH',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
  .then(function(data){
    if(data.success){ showToast('Product '+(isCurrentlyActive?'disabled':'enabled'),'success'); loadProducts(); }
    else showToast(data.error||'Could not update product','error');
  })
  .catch(function(err){ showToast('Network error: '+err.message,'error'); });
}

function previewImage(input){
  if(input.files&&input.files[0]){
    var r=new FileReader(); r.onload=function(e){
      var prev=document.getElementById('pImagePreview'); prev.src=e.target.result; prev.style.display='block';
    }; r.readAsDataURL(input.files[0]);
  }
}

// ════════════════════════════════════════════
//  BATCHES
// ════════════════════════════════════════════
var allProducts=[];
function loadProductsForSelect(){
  fetch(R.products+'?status=1',{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(rows){
    allProducts=rows;
    var sel=document.getElementById('bProduct');
    sel.innerHTML='<option value="">— Select Product —</option>'+
      rows.map(function(p){ return '<option value="'+p.id+'">'+escH(p.product_name)+' ('+escH(p.sku)+')</option>'; }).join('');
  });
}

function loadBatches(){
  var q=document.getElementById('batchSearch').value;
  fetch(R.batches+'?q='+encodeURIComponent(q),{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(rows){
    var tb=document.getElementById('batchTableBody');
    if(!rows.length){
      tb.innerHTML='<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-rounded">inventory_2</span><p>No batches yet. Record a delivery to get started.</p></div></td></tr>';
      return;
    }
    tb.innerHTML=rows.map(function(b){
      var days=Math.round((new Date(b.expiry_date)-new Date())/(1000*60*60*24));
      var dColor=days<0?'#dc2626':(days<=7?'#dc2626':(days<=14?'#d97706':'#1a1d2e'));
      var dText=days<0?'EXPIRED':days+'d';
      return '<tr>'+
        '<td><div style="font-weight:600;color:#1a1d2e;font-size:13px;">'+escH(b.product_name)+'</div>'+
          '<div style="font-family:\'DM Mono\',monospace;font-size:10px;color:#c4c9dd;">'+escH(b.sku)+'</div></td>'+
        '<td style="font-family:\'DM Mono\',monospace;font-weight:600;font-size:12px;">'+escH(b.batch_number)+'</td>'+
        '<td style="font-family:\'DM Mono\',monospace;font-size:12px;color:#6b7494;">'+escH(b.lot_number||'—')+'</td>'+
        '<td style="font-size:12px;color:#6b7494;">'+escH(b.supplier_name||'—')+'</td>'+
        '<td style="font-weight:700;">'+parseFloat(b.quantity).toFixed(0)+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+(b.cost_price?'₱'+parseFloat(b.cost_price).toFixed(2):'—')+'</td>'+
        '<td style="font-size:12px;color:#9aa3c2;">'+(b.received_date||'—')+'</td>'+
        '<td style="font-family:\'DM Mono\',monospace;font-weight:600;font-size:12px;">'+b.expiry_date+'</td>'+
        '<td style="font-weight:800;color:'+dColor+';">'+dText+'</td>'+
        '<td><div style="display:flex;gap:5px;">'+
          '<button class="btn-sm green" onclick="confirmBatchStock('+b.id+')"><span class="material-symbols-rounded">add_circle</span>+Stock</button>'+
          '<button class="btn-sm red" onclick="deleteBatch('+b.id+')"><span class="material-symbols-rounded">delete</span>Remove</button>'+
        '</div></td>'+
      '</tr>';
    }).join('');
  })
  .catch(function(){ showToast('Failed to load batches','error'); });
}

function openBatchModal(){
  document.getElementById('batchForm').reset();
  document.getElementById('bReceived').value=new Date().toISOString().slice(0,10);
  document.getElementById('bUpdateStock').checked=true;
  document.getElementById('bSupplierDisplay').value='';
  openModal('batchModal');
}

function updateSupplierDisplay(){
  var sel=document.getElementById('bSupplier');
  var display=document.getElementById('bSupplierDisplay');
  if(sel.value){
    var opt=sel.options[sel.selectedIndex];
    display.value=opt.text.replace(' — ','');
  } else {
    display.value='';
  }
}

document.getElementById('batchForm').addEventListener('submit',function(e){
  e.preventDefault();
  var payload={
    product_id:   document.getElementById('bProduct').value,
    batch_number: document.getElementById('bBatch').value,
    lot_number:   document.getElementById('bLot').value,
    expiry_date:  document.getElementById('bExpiry').value,
    quantity:     document.getElementById('bQty').value,
    cost_price:   document.getElementById('bCost').value,
    received_date:document.getElementById('bReceived').value,
    supplier_id:  document.getElementById('bSupplier').value,
    notes:        document.getElementById('bNotes').value,
    update_stock: document.getElementById('bUpdateStock').checked,
  };
  fetch(R.batchStore,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(payload)})
  .then(function(r){return r.json();})
  .then(function(data){
    if(data.success){ showToast('Batch recorded','success'); closeModal('batchModal'); loadBatches(); }
    else showToast(data.error||'Error saving batch','error');
  })
  .catch(function(){ showToast('Network error','error'); });
});

function confirmBatchStock(id){
  if(!confirm('Add this batch\'s quantity to the product\'s current stock?')) return;
  fetch(R.batchConfirm(id),{method:'PATCH',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(data){ if(data.success){ showToast('Stock updated','success'); loadBatches(); } else showToast(data.error||'Error','error'); });
}

function deleteBatch(id){
  if(!confirm('Remove this batch record? Stock will NOT be adjusted.')) return;
  fetch(R.batchDelete(id),{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(data){ if(data.success){ showToast('Batch removed','success'); loadBatches(); } });
}

// ════════════════════════════════════════════
//  SUPPLIERS
// ════════════════════════════════════════════
function loadSuppliers(){
  var q=document.getElementById('supplierSearch').value;
  fetch(R.suppliers+'?q='+encodeURIComponent(q),{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(rows){
    var tb=document.getElementById('supplierTableBody');
    if(!rows.length){ tb.innerHTML='<tr><td colspan="7"><div class="empty-state"><span class="material-symbols-rounded">local_shipping</span><p>No suppliers found</p></div></td></tr>'; return; }
    tb.innerHTML=rows.map(function(s){
      return '<tr>'+
        '<td style="font-family:\'DM Mono\',monospace;font-size:11.5px;color:#6b7494;font-weight:600;">'+escH(s.supplier_code)+'</td>'+
        '<td style="font-weight:600;color:#1a1d2e;font-size:13px;">'+escH(s.supplier_name)+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(s.contact_person||'—')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(s.phone||'—')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(s.email||'—')+'</td>'+
        '<td><span class="badge '+(s.is_active?'badge-active':'badge-inactive')+'">'+(s.is_active?'Active':'Inactive')+'</span></td>'+
        '<td><button class="btn-sm blue" onclick="editSupplier('+JSON.stringify(JSON.stringify(s))+')"><span class="material-symbols-rounded">edit</span>Edit</button></td>'+
      '</tr>';
    }).join('');
  });
}

function openSupplierModal(){
  document.getElementById('supplierId').value=''; document.getElementById('supplierForm').reset();
  document.getElementById('supplierModalTitle').textContent='Add Supplier';
  document.getElementById('sActiveWrap').style.display='none';
  openModal('supplierModal');
}

function editSupplier(jsonStr){
  var s=JSON.parse(jsonStr);
  document.getElementById('supplierModalTitle').textContent='Edit Supplier';
  document.getElementById('supplierId').value=s.id;
  document.getElementById('sCode').value=s.supplier_code;
  document.getElementById('sName').value=s.supplier_name;
  document.getElementById('sContact').value=s.contact_person||'';
  document.getElementById('sPhone').value=s.phone||'';
  document.getElementById('sEmail').value=s.email||'';
  document.getElementById('sAddress').value=s.address||'';
  document.getElementById('sActiveWrap').style.display='block';
  document.getElementById('sActive').value=s.is_active;
  openModal('supplierModal');
}

document.getElementById('supplierForm').addEventListener('submit',function(e){
  e.preventDefault();
  var id=document.getElementById('supplierId').value;
  var payload={
    supplier_code:  document.getElementById('sCode').value,
    supplier_name:  document.getElementById('sName').value,
    contact_person: document.getElementById('sContact').value,
    phone:          document.getElementById('sPhone').value,
    email:          document.getElementById('sEmail').value,
    address:        document.getElementById('sAddress').value,
    is_active:      document.getElementById('sActive').value,
  };
  fetch(id?R.supUpdate(id):R.supStore,{method:id?'PUT':'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(payload)})
  .then(function(r){return r.json();})
  .then(function(data){
    if(data.success){ showToast('Supplier saved','success'); closeModal('supplierModal'); loadSuppliers(); }
    else showToast(data.error||'Error','error');
  });
});

// ════════════════════════════════════════════
//  INVOICES
// ════════════════════════════════════════════

// Cache: stores items per invoice id so View Items is instant after first load
var _invItemsCache = {};

var invPage=1,invTotal=0,invPerPage=25;
function loadInvoices(page){
  invPage=page||1;
  var q  =document.getElementById('invSearch').value;
  var st =document.getElementById('invStatusFilter').value;
  var df =document.getElementById('invDateFrom').value;
  var dt =document.getElementById('invDateTo').value;
  var url=R.invoices+'?q='+encodeURIComponent(q)+'&status='+st+'&date_from='+df+'&date_to='+dt+'&page='+invPage;
  fetch(url,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(res){
    invTotal=res.total; invPerPage=res.per_page;
    var tb=document.getElementById('invTableBody');
    var rows=res.data;
    if(!rows.length){
      tb.innerHTML='<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-rounded">receipt_long</span><p>No invoices found</p></div></td></tr>';
      document.getElementById('invPagination').style.display='none';
      return;
    }
    var SC={paid:'badge-paid',voided:'badge-voided',draft:'badge-draft',issued:'badge-draft'};
    tb.innerHTML=rows.map(function(inv){
      // If the server returns items embedded, cache them immediately so the
      // modal opens instantly without any extra network request.
      if(inv.items && inv.items.length){
        _invItemsCache[inv.id] = inv.items;
      }
      return '<tr>'+
        '<td style="font-family:\'DM Mono\',monospace;font-size:11.5px;font-weight:500;">'+escH(inv.invoice_number)+'</td>'+
        '<td style="color:#9aa3c2;font-size:12px;">'+escH(inv.invoice_date?inv.invoice_date.slice(0,10):'')+'</td>'+
        '<td style="font-weight:600;font-size:13px;">'+escH(inv.customer_name||'Walk-in')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(inv.cashier_name||'—')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(inv.payment_method||'—')+'</td>'+
        '<td style="font-size:13px;">₱'+parseFloat(inv.subtotal).toFixed(2)+'</td>'+
        '<td style="color:#dc2626;font-size:12px;">—₱'+parseFloat(inv.total_discount).toFixed(2)+'</td>'+
        '<td style="font-weight:700;">₱'+parseFloat(inv.grand_total).toFixed(2)+'</td>'+
        '<td><span class="badge '+(SC[inv.status]||'badge-draft')+'">'+inv.status.charAt(0).toUpperCase()+inv.status.slice(1)+'</span></td>'+
        '<td><div style="display:flex;gap:4px;flex-wrap:nowrap;">'+
          '<button class="btn-sm blue" onclick="viewInvItems('+inv.id+',\''+escH(inv.invoice_number)+'\')"><span class="material-symbols-rounded">visibility</span>Items</button>'+
          (inv.status==='paid'?'<a href="'+R.printInv(inv.id)+'" target="_blank" class="btn-sm amber" style="text-decoration:none;"><span class="material-symbols-rounded">print</span>Print</a>':'')+
          (inv.status!=='voided'?'<button class="btn-sm red" onclick="openVoidInv('+inv.id+',\''+escH(inv.invoice_number)+'\')"><span class="material-symbols-rounded">cancel</span>Void</button>':'')+
        '</div></td>'+
      '</tr>';
    }).join('');
    renderPagination();
  })
  .catch(function(){ showToast('Failed to load invoices','error'); });
}

function renderPagination(){
  var pages=Math.ceil(invTotal/invPerPage);
  if(pages<=1){ document.getElementById('invPagination').style.display='none'; return; }
  document.getElementById('invPagination').style.display='flex';
  var html='<span style="font-size:12px;color:#9aa3c2;margin-right:6px;">Page '+invPage+' of '+pages+' ('+invTotal+' records)</span>';
  if(invPage>1) html+='<button class="page-btn" onclick="loadInvoices('+(invPage-1)+')">‹</button>';
  var start=Math.max(1,invPage-2),end=Math.min(pages,invPage+2);
  for(var i=start;i<=end;i++) html+='<button class="page-btn'+(i===invPage?' active':'')+'" onclick="loadInvoices('+i+')">'+i+'</button>';
  if(invPage<pages) html+='<button class="page-btn" onclick="loadInvoices('+(invPage+1)+')">›</button>';
  document.getElementById('invPagination').innerHTML=html;
}

var _voidInvId=null;
function openVoidInv(id,num){
  _voidInvId=id;
  document.getElementById('voidInvSubtitle').textContent='Invoice '+num;
  document.getElementById('voidInvReason').value='';
  openModal('voidInvModal');
}

function submitVoidInvoice(){
  var reason=document.getElementById('voidInvReason').value.trim();
  if(!reason){ showToast('Please enter a reason','error'); return; }
  fetch(R.invVoid(_voidInvId),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify({reason:reason})})
  .then(function(r){return r.json();})
  .then(function(data){
    if(data.success){ showToast('Invoice voided','success'); closeModal('voidInvModal'); loadInvoices(invPage); }
    else showToast(data.error||'Error voiding','error');
  });
}

// ── FIX: View Items uses cache — opens instantly on repeat clicks ────────────
function viewInvItems(id, num){
  document.getElementById('invItemsTitle').textContent = 'Items — ' + num;
  openModal('invItemsModal');

  // Helper: render rows into the modal table
  function renderItems(items){
    document.getElementById('invItemsTbody').innerHTML = items.length
      ? items.map(function(i){
          return '<tr>'+
            '<td style="font-weight:600;font-size:13px;">'+escH(i.product_name)+'</td>'+
            '<td style="color:#9aa3c2;font-size:12px;">'+escH(i.generic_name||'—')+'</td>'+
            '<td style="color:#9aa3c2;font-size:12px;">'+escH(i.uom_code||'PC')+'</td>'+
            '<td style="font-weight:700;">'+parseFloat(i.quantity).toFixed(0)+'</td>'+
            '<td>₱'+parseFloat(i.unit_price).toFixed(2)+'</td>'+
            '<td style="color:#9aa3c2;">'+parseFloat(i.tax_rate_pct).toFixed(1)+'%</td>'+
            '<td>₱'+parseFloat(i.line_subtotal).toFixed(2)+'</td>'+
            '<td>₱'+parseFloat(i.line_tax).toFixed(2)+'</td>'+
            '<td style="font-weight:700;">₱'+parseFloat(i.line_total).toFixed(2)+'</td>'+
          '</tr>';
        }).join('')
      : '<tr><td colspan="9" style="text-align:center;padding:20px;color:#9aa3c2;">No items found</td></tr>';
  }

  // Already fetched before? Show instantly, no spinner, no request.
  if(_invItemsCache[id]){
    renderItems(_invItemsCache[id]);
    return;
  }

  // First time: show spinner, fetch, then cache so next click is instant.
  document.getElementById('invItemsTbody').innerHTML =
    '<tr><td colspan="9" style="text-align:center;padding:20px;color:#9aa3c2;"><span class="material-symbols-rounded spin">refresh</span></td></tr>';

  fetch(R.invItems(id),{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){ return r.json(); })
  .then(function(items){
    _invItemsCache[id] = items;   // cache it — next open is instant
    renderItems(items);
  })
  .catch(function(){ showToast('Failed to load items','error'); });
}

// ── FIX: Export CSV now includes the search query so it matches what's on screen ──
function exportInvoicesCSV(){
  var q  = document.getElementById('invSearch').value;
  var st = document.getElementById('invStatusFilter').value;
  var df = document.getElementById('invDateFrom').value;
  var dt = document.getElementById('invDateTo').value;
  window.location = R.invExport
    + '?q='         + encodeURIComponent(q)
    + '&status='    + encodeURIComponent(st)
    + '&date_from=' + encodeURIComponent(df)
    + '&date_to='   + encodeURIComponent(dt);
}

// ════════════════════════════════════════════
//  USERS
// ════════════════════════════════════════════
function loadUsers(){
  fetch(R.users,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(rows){
    var tb=document.getElementById('usersTableBody');
    if(!rows.length){ tb.innerHTML='<tr><td colspan="5"><div class="empty-state"><span class="material-symbols-rounded">manage_accounts</span><p>No accounts found</p></div></td></tr>'; return; }
    var roleColors={admin:'background:#f5f3ff;color:#7c3aed;',cashier:'background:#eef1ff;color:#3d52d5;'};
    tb.innerHTML=rows.map(function(u){
      var rc=roleColors[u.role_name]||'background:#f5f6fa;color:#6b7494;';
      return '<tr>'+
        '<td><div style="font-weight:600;color:#1a1d2e;font-size:13px;">'+escH(u.name)+'</div></td>'+
        '<td style="color:#6b7494;font-size:12.5px;">'+escH(u.email)+'</td>'+
        '<td><span style="padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:700;'+rc+'">'+escH(u.role_name)+'</span></td>'+
        '<td style="color:#9aa3c2;font-size:12px;">'+(u.created_at?u.created_at.slice(0,10):'—')+'</td>'+
        '<td><button class="btn-sm blue" onclick="editUser('+JSON.stringify(JSON.stringify(u))+')"><span class="material-symbols-rounded">edit</span>Edit</button></td>'+
      '</tr>';
    }).join('');
  });
}

function openUserModal(){
  document.getElementById('userId').value=''; document.getElementById('userForm').reset();
  document.getElementById('userModalTitle').textContent='New Cashier Account';
  document.getElementById('uPassLabel').textContent='Password *';
  document.getElementById('uEditNote').style.display='none';
  document.getElementById('uPassword').required=true;
  openModal('userModal');
}

function editUser(jsonStr){
  var u=JSON.parse(jsonStr);
  document.getElementById('userModalTitle').textContent='Edit Account — '+u.name;
  document.getElementById('userId').value=u.id;
  document.getElementById('uName').value=u.name;
  document.getElementById('uEmail').value=u.email;
  document.getElementById('uRole').value=u.role_name;
  document.getElementById('uPassword').value='';
  document.getElementById('uPasswordConfirm').value='';
  document.getElementById('uPassLabel').textContent='New Password';
  document.getElementById('uEditNote').style.display='flex';
  document.getElementById('uPassword').required=false;
  openModal('userModal');
}

document.getElementById('userForm').addEventListener('submit',function(e){
  e.preventDefault();
  var id=document.getElementById('userId').value;
  var pw=document.getElementById('uPassword').value;
  var pwc=document.getElementById('uPasswordConfirm').value;
  if(pw&&pw!==pwc){ showToast('Passwords do not match','error'); return; }
  if(!id&&!pw){ showToast('Password is required for new accounts','error'); return; }
  var payload={
    name:document.getElementById('uName').value,
    email:document.getElementById('uEmail').value,
    role:document.getElementById('uRole').value,
    password:pw,
    password_confirmation:pwc,
  };
  fetch(id?R.userUpdate(id):R.userStore,{method:id?'PUT':'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(payload)})
  .then(function(r){return r.json();})
  .then(function(data){
    if(data.success){ showToast('Account saved','success'); closeModal('userModal'); loadUsers(); }
    else showToast(data.errors?Object.values(data.errors)[0][0]:(data.error||'Error'),'error');
  })
  .catch(function(){ showToast('Network error','error'); });
});

// ════════════════════════════════════════════
//  CASHIER SELECTOR
// ════════════════════════════════════════════
function loadCashierSelector(){
  fetch(R.users,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(users){
    var sel=document.getElementById('cashierSelector');
    var cashiers=users.filter(function(u){ return u.role_name==='cashier'; });
    sel.innerHTML='<option value="">Select Cashier...</option>'+
      (cashiers.length?cashiers.map(function(c){ return '<option value="'+c.id+'">'+escH(c.name)+'</option>'; }).join(''):'<option disabled>No cashiers available</option>');
  });
}

function viewCashierDashboard(){
  var id=document.getElementById('cashierSelector').value;
  if(!id){ showToast('Please select a cashier','warning'); return; }
  window.location.href=R.cashierView(id);
}

// ════════════════════════════════════════════
//  REPORTS
// ════════════════════════════════════════════

var _reportTrendChart = null;
var _reportPayChart   = null;
 
function loadSalesReport(){
  var BAR_COLORS = ['#3d52d5','#16a34a','#d97706','#e11d48','#0891b2','#7c3aed','#ea580c','#0f766e','#db2777','#ca8a04'];
  var df = document.getElementById('reportSalesFromDate').value;
  var dt = document.getElementById('reportSalesToDate').value;
  if(!df || !dt){ showToast('Please select both From and To dates','warning'); return; }

  document.getElementById('reportDateRange').textContent = 'Loading…';
  document.getElementById('reportEmptyState').style.display = 'none';
  ['reportTotalInvoices','reportTotalRevenue','reportAOV','reportVoided']
    .forEach(function(id){ document.getElementById(id).textContent = '…'; });

  fetch(R.invoices+'?date_from='+df+'&date_to='+dt+'&page=1',{
    headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}
  })
  .then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
  .then(function(res){
    var rows   = res.data || [];
    var paid   = rows.filter(function(i){ return i.status==='paid'; });
    var voided = rows.filter(function(i){ return i.status==='voided'; });
    var revenue = paid.reduce(function(s,i){ return s+(parseFloat(i.grand_total)||0); },0);
    var aov     = paid.length ? revenue/paid.length : 0;

    document.getElementById('reportTotalInvoices').textContent = paid.length;
    document.getElementById('reportTotalRevenue').textContent  = '₱'+revenue.toLocaleString('en-PH',{minimumFractionDigits:2});
    document.getElementById('reportAOV').textContent           = '₱'+aov.toLocaleString('en-PH',{minimumFractionDigits:2});
    document.getElementById('reportVoided').textContent        = voided.length;
    document.getElementById('reportDateRange').textContent     = paid.length+' paid · '+df+' → '+dt;

    document.getElementById('reportChartsRow').style.display = 'grid';
    document.getElementById('reportBottomRow').style.display = 'grid';
    document.getElementById('reportEmptyState').style.display = 'none';

   // ── Revenue Trend ──
var byDate = {};
paid.forEach(function(i){
  var d = i.invoice_date ? i.invoice_date.slice(0,10) : '?';
  byDate[d] = (byDate[d] || 0) + (parseFloat(i.grand_total) || 0);
});

// Build a full date range (every day from df to dt, no gaps)
var tLabels = [], tData = [], tRawKeys = [];
var cur = new Date(df);
var end = new Date(dt);
while(cur <= end){
  var key = cur.toISOString().slice(0,10);
  tRawKeys.push(key);
  tLabels.push(cur.toLocaleDateString('en-PH', {month:'short', day:'numeric'}));
  tData.push(byDate[key] || 0);
  cur.setDate(cur.getDate() + 1);
}

document.getElementById('reportTrendSubtitle').textContent =
  '₱' + tData.reduce(function(a,b){ return a+b; }, 0)
        .toLocaleString('en-PH', {minimumFractionDigits:2}) + ' total';

// Reset the wrapper — no horizontal scroll needed for a line chart
var wrap = document.getElementById('reportTrendWrap');
wrap.style.overflowX = 'hidden';

if(_reportTrendChart) _reportTrendChart.destroy();
var tCtx = document.getElementById('reportTrendChart');
if(tCtx){
  tCtx.style.width  = '';   // let Chart.js handle sizing
  tCtx.style.height = '160px';

  _reportTrendChart = new Chart(tCtx, {
    type: 'line',
    data: {
      labels: tLabels,
      datasets: [{
        data: tData,
        borderColor: '#3d52d5',
        borderWidth: 2,
        backgroundColor: 'rgba(61,82,213,0.06)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#3d52d5',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0f1117',
          titleFont: { size: 10, weight: '700' },
          bodyFont: { size: 12 },
          padding: 10,
          cornerRadius: 6,
          callbacks: { label: function(ctx){
            return ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits:2});
          }}
        }
      },
      scales: {
        x: {
          grid: { display: false },
          border: { display: false },
          ticks: { font: { size: 10, weight: '600' }, color: '#b0b8d4', maxRotation: 0 }
        },
        y: {
          grid: { color: 'rgba(0,0,0,0.04)' },
          border: { display: false },
          ticks: {
            font: { size: 10 }, color: '#b0b8d4',
            callback: function(v){ return v >= 1000 ? '₱'+(v/1000).toFixed(1)+'k' : '₱'+v; }
          }
        }
      }
    }
  });
}
    // ── Payment Mix Donut ──
    var byPay={};
    paid.forEach(function(i){
      var m=i.payment_method||'Unknown';
      byPay[m]=(byPay[m]||0)+(parseFloat(i.grand_total)||0);
    });
    var pLabels=Object.keys(byPay);
    var pTotals=pLabels.map(function(k){ return byPay[k]; });
    var grand=pTotals.reduce(function(a,b){ return a+b; },0);

    if(_reportPayChart) _reportPayChart.destroy();
    var pCtx=document.getElementById('reportPayChart');
    if(pCtx && pLabels.length){
      _reportPayChart=new Chart(pCtx,{
        type:'doughnut',
        data:{
          labels:pLabels,
          datasets:[{
            data:pTotals,
            backgroundColor:BAR_COLORS.slice(0,pLabels.length),
            borderWidth:3,borderColor:'#ffffff',hoverOffset:4
          }]
        },
        options:{
          responsive:true,maintainAspectRatio:false,cutout:'70%',
          plugins:{
            legend:{ display:false },
            tooltip:{
              backgroundColor:'#0f1117',
              callbacks:{ label:function(ctx){
                var pct=grand>0?((ctx.parsed/grand)*100).toFixed(1):0;
                return ' ₱'+ctx.parsed.toLocaleString('en-PH',{minimumFractionDigits:2})+' ('+pct+'%)';
              }}
            }
          }
        }
      });
      document.getElementById('reportPayLegend').innerHTML=pLabels.map(function(lbl,i){
        var pct=grand>0?((pTotals[i]/grand)*100).toFixed(1):0;
        return '<div style="display:flex;align-items:center;gap:6px;">'+
          '<div style="width:8px;height:8px;border-radius:2px;background:'+BAR_COLORS[i]+';flex-shrink:0;"></div>'+
          '<span style="font-size:11px;color:#6b7494;flex:1;">'+escH(lbl)+'</span>'+
          '<span style="font-size:11px;font-weight:800;color:#0f1117;">'+pct+'%</span></div>';
      }).join('');
    }

    // ── Top Products ──
    var topProducts = res.top_products || [];
    var tpContainer = document.getElementById('reportTopProdsTbody');
    if(!topProducts.length){
      tpContainer.innerHTML = '<div style="padding:20px;text-align:center;color:#9aa3c2;font-size:12px;">No product data</div>';
    } else {
      var maxRev = Math.max.apply(null, topProducts.map(function(p){ return parseFloat(p.revenue)||0; }));
      var RANK_COLORS = ['#3d52d5','#16a34a','#d97706','#e11d48','#7c3aed'];
      tpContainer.innerHTML = topProducts.map(function(p,i){
        var rev = parseFloat(p.revenue)||0;
        var pct = maxRev > 0 ? Math.round((rev/maxRev)*100) : 0;
        var rc  = RANK_COLORS[i] || '#c4c9dd';
        return '<div style="padding:9px 16px;border-bottom:1px solid #f5f6fa;">'+
          '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">'+
            '<div style="display:flex;align-items:center;gap:8px;">'+
              '<span style="display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;border-radius:4px;background:'+rc+';font-size:9px;font-weight:900;color:#fff;flex-shrink:0;font-family:monospace;">'+(i+1)+'</span>'+
              '<span style="font-size:12px;font-weight:600;color:#0f1117;">'+escH(p.product_name)+'</span>'+
            '</div>'+
            '<span style="font-size:11px;font-weight:800;color:#16a34a;font-variant-numeric:tabular-nums;">₱'+rev.toLocaleString('en-PH',{minimumFractionDigits:2})+'</span>'+
          '</div>'+
          '<div style="display:flex;align-items:center;gap:8px;">'+
            '<div style="flex:1;height:3px;background:#f0f2f8;border-radius:4px;">'+
              '<div style="width:'+pct+'%;height:3px;background:'+rc+';border-radius:4px;transition:width 0.6s ease;"></div>'+
            '</div>'+
            '<span style="font-size:10px;color:#9aa3c2;white-space:nowrap;font-variant-numeric:tabular-nums;">'+(parseFloat(p.qty_sold)||0).toFixed(0)+' units</span>'+
          '</div>'+
        '</div>';
      }).join('');
    }

    // ── Transactions Table ──
    document.getElementById('reportInvTableTitle').textContent = 'Transactions ('+rows.length+')';
    var SC={paid:'badge-paid',voided:'badge-voided',draft:'badge-draft',issued:'badge-draft'};
    document.getElementById('reportInvTbody').innerHTML = rows.length
      ? rows.map(function(inv){
          return '<tr>'+
            '<td style="font-family:\'DM Mono\',monospace;font-size:11px;font-weight:600;color:#3d52d5;">'+escH(inv.invoice_number)+'</td>'+
            '<td style="color:#9aa3c2;font-size:11px;font-variant-numeric:tabular-nums;">'+(inv.invoice_date?inv.invoice_date.slice(0,10):'')+'</td>'+
            '<td style="font-weight:600;font-size:12px;color:#0f1117;">'+escH(inv.customer_name||'Walk-in')+'</td>'+
            '<td style="color:#6b7494;font-size:11.5px;">'+escH(inv.cashier_name||'—')+'</td>'+
            '<td style="color:#6b7494;font-size:11.5px;">'+escH(inv.payment_method||'—')+'</td>'+
            '<td style="font-weight:800;font-size:12.5px;color:#0f1117;font-variant-numeric:tabular-nums;">₱'+parseFloat(inv.grand_total).toFixed(2)+'</td>'+
            '<td><span class="badge '+(SC[inv.status]||'badge-draft')+'">'+inv.status.charAt(0).toUpperCase()+inv.status.slice(1)+'</span></td>'+
          '</tr>';
        }).join('')
      : '<tr><td colspan="7" class="empty-state"><span class="material-symbols-rounded">receipt_long</span><p>No transactions in this period</p></td></tr>';
  })
  .catch(function(err){
    showToast('Failed to generate report: '+err.message,'error');
    document.getElementById('reportEmptyState').style.display = 'block';
  });
}

function exportSalesReportCSV(){
  var df=document.getElementById('reportSalesFromDate').value;
  var dt=document.getElementById('reportSalesToDate').value;
  window.location=R.invExport+'?status=paid&date_from='+df+'&date_to='+dt;
}

function loadInventoryReport(){
  // KPI cards from server stats
  document.getElementById('reportTotalProducts').textContent  = STATS.total_products;
  document.getElementById('reportLowStock').textContent       = STATS.low_stock;
  document.getElementById('reportExpiring').textContent       = STATS.expiring_30;
  document.getElementById('reportTotalSuppliers').textContent = STATS.total_suppliers;

  // ── Low Stock Products ──
  fetch(R.products + '?status=1', { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
  .then(function(r){ return r.json(); })
  .then(function(products){
    var lowStock = products.filter(function(p){
      return parseInt(p.stock_quantity) <= parseInt(p.reorder_level);
    }).sort(function(a,b){ return a.stock_quantity - b.stock_quantity; });

    // Badge
    document.getElementById('invLowStockBadge').textContent = lowStock.length + ' items';

    // Low stock table
    var lb = document.getElementById('invLowStockBody');
    if(!lowStock.length){
      lb.innerHTML = '<tr><td colspan="4"><div class="empty-state"><span class="material-symbols-rounded">check_circle</span><p>All products sufficiently stocked</p></div></td></tr>';
    } else {
      lb.innerHTML = lowStock.map(function(p){
        var pct = p.reorder_level > 0 ? Math.round((p.stock_quantity / p.reorder_level) * 100) : 0;
        var barColor = p.stock_quantity === 0 ? '#dc2626' : (pct <= 50 ? '#d97706' : '#16a34a');
        return '<tr>'+
          '<td><div style="font-weight:600;color:#1a1d2e;font-size:12.5px;">'+escH(p.product_name)+'</div>'+
            '<div style="width:100%;background:#f0f2f8;border-radius:4px;height:4px;margin-top:5px;">'+
              '<div style="width:'+Math.min(pct,100)+'%;background:'+barColor+';height:4px;border-radius:4px;transition:width .3s;"></div>'+
            '</div></td>'+
          '<td style="font-family:\'DM Mono\',monospace;font-size:11px;color:#9aa3c2;">'+escH(p.sku)+'</td>'+
          '<td style="font-weight:800;color:'+(p.stock_quantity===0?'#dc2626':'#d97706')+';">'+p.stock_quantity+'</td>'+
          '<td style="color:#9aa3c2;font-size:12px;">'+p.reorder_level+'</td>'+
        '</tr>';
      }).join('');
    }

    // ── Full Stock List ──
    document.getElementById('invStockListCount').textContent = products.length + ' products';
    var fb = document.getElementById('invFullStockBody');
    if(!products.length){
      fb.innerHTML = '<tr><td colspan="8"><div class="empty-state"><span class="material-symbols-rounded">medication_liquid</span><p>No products found</p></div></td></tr>';
    } else {
      fb.innerHTML = products.map(function(p){
        var stockColor = p.stock_quantity === 0 ? '#dc2626' : (parseInt(p.stock_quantity) <= parseInt(p.reorder_level) ? '#d97706' : '#16a34a');
        var isLow = parseInt(p.stock_quantity) <= parseInt(p.reorder_level);
        return '<tr>'+
          '<td><div style="font-weight:600;color:#1a1d2e;font-size:13px;">'+escH(p.product_name)+'</div>'+
            '<div style="font-size:11px;color:#9aa3c2;">'+escH(p.generic_name||'')+'</div></td>'+
          '<td style="font-family:\'DM Mono\',monospace;font-size:11px;color:#9aa3c2;">'+escH(p.sku)+'</td>'+
          '<td style="font-size:12px;color:#6b7494;">'+escH(p.category_name||'—')+'</td>'+
          '<td><span style="font-weight:800;color:'+stockColor+';">'+p.stock_quantity+'</span>'+
            (isLow ? '<span style="margin-left:6px;font-size:9px;font-weight:700;background:'+(p.stock_quantity===0?'#fef2f2':'#fffbeb')+';color:'+(p.stock_quantity===0?'#dc2626':'#92400e')+';padding:1px 6px;border-radius:10px;">'+(p.stock_quantity===0?'OUT':'LOW')+'</span>' : '')+
          '</td>'+
          '<td style="color:#9aa3c2;font-size:12px;">'+p.reorder_level+'</td>'+
          '<td style="color:#6b7494;font-size:12px;">₱'+parseFloat(p.cost_price).toFixed(2)+'</td>'+
          '<td style="font-weight:600;font-size:13px;">₱'+parseFloat(p.selling_price).toFixed(2)+'</td>'+
          '<td><span class="badge '+(parseInt(p.is_active)?'badge-active':'badge-inactive')+'">'+(parseInt(p.is_active)?'Active':'Inactive')+'</span></td>'+
        '</tr>';
      }).join('');
    }
  })
  .catch(function(){ showToast('Failed to load stock data','error'); });

  // ── Expiring Batches ──
  fetch(R.batches, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
  .then(function(r){ return r.json(); })
  .then(function(batches){
    var now = new Date();
    var expiring = batches.filter(function(b){
      var days = Math.round((new Date(b.expiry_date) - now) / (1000*60*60*24));
      return days <= 30;
    }).sort(function(a,b){ return new Date(a.expiry_date) - new Date(b.expiry_date); });

    document.getElementById('invExpiringBadge').textContent = expiring.length + ' batches';

    var eb = document.getElementById('invExpiringBody');
    if(!expiring.length){
      eb.innerHTML = '<tr><td colspan="5"><div class="empty-state"><span class="material-symbols-rounded">check_circle</span><p>No batches expiring in 30 days</p></div></td></tr>';
    } else {
      eb.innerHTML = expiring.map(function(b){
        var days = Math.round((new Date(b.expiry_date) - now) / (1000*60*60*24));
        var dColor = days < 0 ? '#dc2626' : (days <= 7 ? '#dc2626' : (days <= 14 ? '#d97706' : '#92400e'));
        var dBg    = days < 0 ? '#fef2f2' : (days <= 7 ? '#fef2f2' : '#fffbeb');
        var dText  = days < 0 ? 'EXPIRED' : days + 'd';
        return '<tr>'+
          '<td style="font-weight:600;font-size:12.5px;color:#1a1d2e;">'+escH(b.product_name)+'</td>'+
          '<td style="font-family:\'DM Mono\',monospace;font-size:11px;color:#9aa3c2;">'+escH(b.batch_number)+'</td>'+
          '<td style="font-family:\'DM Mono\',monospace;font-size:11px;font-weight:600;">'+b.expiry_date+'</td>'+
          '<td><span style="font-size:10px;font-weight:800;background:'+dBg+';color:'+dColor+';padding:2px 7px;border-radius:10px;">'+dText+'</span></td>'+
          '<td style="font-weight:700;">'+parseFloat(b.quantity).toFixed(0)+'</td>'+
        '</tr>';
      }).join('');
    }
  })
  .catch(function(){ showToast('Failed to load batch data','error'); });
}

var _cpActiveId = null;
var _cpCharts   = {};

function loadCashierPerformanceReport() {
  fetch(R.users, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
  .then(function(r) { return r.json(); })
  .then(function(users) {
    var cashiers = users.filter(function(u) { return u.role_name === 'cashier'; });
    if (!cashiers.length) {
      document.getElementById('cashierCardsGrid').innerHTML =
        '<div class="empty-state"><span class="material-symbols-rounded">people</span><p>No cashiers found</p></div>';
      return;
    }
    fetch(R.invoices + '?page=1', { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(invoiceRes) {
      var allInvoices = invoiceRes.data || [];
      var today      = new Date().toISOString().slice(0, 10);
      var thisMonth  = new Date().toISOString().substring(0, 7);

      var data = cashiers.map(function(c) {
        var ci       = allInvoices.filter(function(i) { return i.cashier_id == c.id; });
        var todayPaid  = ci.filter(function(i) { return i.invoice_date && i.invoice_date.slice(0,10) === today && i.status !== 'voided'; });
        var todayRev   = todayPaid.reduce(function(s, i) { return s + (parseFloat(i.grand_total) || 0); }, 0);
        var monthPaid  = ci.filter(function(i) { return i.invoice_date && i.invoice_date.substring(0,7) === thisMonth && i.status !== 'voided'; });
        var monthRev   = monthPaid.reduce(function(s, i) { return s + (parseFloat(i.grand_total) || 0); }, 0);
        var voided     = ci.filter(function(i) { return i.status === 'voided'; }).length;
        var recentTxns = ci.slice(0, 5);

        // Build 7-day trend
        var trendMap = {};
        for (var d = 6; d >= 0; d--) {
          var dd = new Date(); dd.setDate(dd.getDate() - d);
          trendMap[dd.toISOString().slice(0,10)] = 0;
        }
        ci.filter(function(i){ return i.status !== 'voided'; }).forEach(function(i) {
          var k = i.invoice_date ? i.invoice_date.slice(0,10) : '';
          if (trendMap[k] !== undefined) trendMap[k] += parseFloat(i.grand_total) || 0;
        });

        return { cashier: c, todayInvoices: todayPaid.length, todayRev, monthInvoices: monthPaid.length, monthRev, voided, recentTxns, trend: Object.values(trendMap) };
      });

      renderCashierCards(data);
    });
  });
}

function renderCashierCards(data) {
  var grid = document.getElementById('cashierCardsGrid');
  grid.innerHTML = '';

  data.forEach(function(item) {
    var c       = item.cashier;
    var isOpen  = _cpActiveId === c.id;
    var inits   = c.name.split(' ').map(function(p){ return p[0]; }).slice(0,2).join('');
    var avgTxn  = item.monthInvoices ? (item.monthRev / item.monthInvoices) : 0;

    var card = document.createElement('div');
    card.className = 'cp-card' + (isOpen ? ' active' : '');

    card.innerHTML =
      '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">' +
        '<div class="cp-avatar">' + escH(inits) + '</div>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-weight:600;font-size:13px;color:#1a1d2e;">' + escH(c.name) + '</div>' +
          '<div style="font-size:11px;color:#9aa3c2;">Cashier</div>' +
        '</div>' +
'<span class="material-symbols-rounded cp-chevron" style="font-size:16px;color:#c4c9dd;">' + (isOpen ? 'expand_less' : 'expand_more') + '</span>' +      '</div>' +
      '<div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px;">' +
        '<div><div style="font-size:10px;color:#9aa3c2;">Today\'s revenue</div>' +
          '<div style="font-size:13px;font-weight:700;color:#1a1d2e;">₱' + item.todayRev.toFixed(2) + '</div></div>' +
        '<div style="text-align:right;"><div style="font-size:10px;color:#9aa3c2;">Invoices</div>' +
          '<div style="font-size:13px;font-weight:700;color:#1a1d2e;">' + item.todayInvoices + '</div></div>' +
      '</div>' +
      '<div style="position:relative;height:44px;margin-bottom:2px;">' +
        '<canvas id="spark-' + c.id + '" style="display:block;"></canvas>' +
      '</div>' +
      '<div class="cp-expand' + (isOpen ? ' open' : '') + '" id="cpExp-' + c.id + '">' +
        '<div style="border-top:1px solid #f0f2f8;padding-top:12px;margin-top:12px;">' +
          '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:12px;">' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Today txns</div><div style="font-size:15px;font-weight:700;">' + item.todayInvoices + '</div></div>' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Today rev</div><div style="font-size:13px;font-weight:700;color:#16a34a;">₱' + item.todayRev.toFixed(2) + '</div></div>' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Voided</div><div style="font-size:15px;font-weight:700;color:#dc2626;">' + item.voided + '</div></div>' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Month txns</div><div style="font-size:15px;font-weight:700;">' + item.monthInvoices + '</div></div>' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Month rev</div><div style="font-size:13px;font-weight:700;color:#16a34a;">₱' + item.monthRev.toFixed(2) + '</div></div>' +
            '<div class="cp-stat-box"><div style="font-size:10px;color:#9aa3c2;">Avg/txn</div><div style="font-size:13px;font-weight:700;">₱' + avgTxn.toFixed(2) + '</div></div>' +
          '</div>' +
          '<div style="font-size:10px;font-weight:700;color:#9aa3c2;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">Recent transactions</div>' +
          (item.recentTxns.length ? item.recentTxns.map(function(t) {
            var isPaid = t.status !== 'voided';
            return '<div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f5f6fa;font-size:12px;">' +
              '<div><div style="font-weight:600;color:#1a1d2e;">' + escH(t.invoice_number) + '</div>' +
                '<div style="font-size:11px;color:#9aa3c2;">' + (t.invoice_date ? t.invoice_date.slice(0,10) : '—') + ' · ' + escH(t.payment_method||'—') + '</div></div>' +
              '<div style="display:flex;align-items:center;gap:8px;">' +
                '<span style="font-weight:700;">₱' + parseFloat(t.grand_total).toFixed(2) + '</span>' +
                '<span style="font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;background:' + (isPaid?'#eefbf2':'#fef2f2') + ';color:' + (isPaid?'#15803d':'#b91c1c') + ';">' + t.status + '</span>' +
              '</div>' +
            '</div>';
          }).join('') : '<div style="font-size:12px;color:#9aa3c2;padding:8px 0;">No transactions yet</div>') +
          '<button class="btn-sm blue" style="margin-top:12px;width:100%;" onclick="window.location.href=R.cashierView(' + c.id + ')">' +
            '<span class="material-symbols-rounded">visibility</span>View full dashboard</button>' +
        '</div>' +
      '</div>';

card.addEventListener('click', function(e) {
  if (e.target.closest('button') || e.target.closest('a')) return;

  var wasActive = _cpActiveId === c.id;
  _cpActiveId = wasActive ? null : c.id;

  // Close ALL cards
  document.querySelectorAll('.cp-card').forEach(function(el) {
    el.classList.remove('active');
    var exp = el.querySelector('.cp-expand');
    var chevron = el.querySelector('.cp-chevron');
    if (exp) exp.classList.remove('open');
    if (chevron) chevron.textContent = 'expand_more';
  });

  // Open clicked one
  if (!wasActive) {
    card.classList.add('active');
    var exp = card.querySelector('.cp-expand');
    var chevron = card.querySelector('.cp-chevron');
    if (exp) exp.classList.add('open');
    if (chevron) chevron.textContent = 'expand_less';
  }

  setTimeout(function() {
    data.forEach(function(item) {
      var canvas = document.getElementById('spark-' + item.cashier.id);
      if (!canvas) return;
      if (_cpCharts[item.cashier.id]) _cpCharts[item.cashier.id].destroy();
      var isOpen = _cpActiveId === item.cashier.id;
      _cpCharts[item.cashier.id] = new Chart(canvas, {
        type: 'line',
        data: {
          labels: ['','','','','','',''],
          datasets: [{ data: item.trend, borderColor: isOpen ? '#3d52d5' : '#c4c9dd', borderWidth: 1.5, fill: true, backgroundColor: isOpen ? 'rgba(61,82,213,0.07)' : 'rgba(196,201,221,0.1)', tension: 0.4, pointRadius: 0 }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend:{display:false}, tooltip:{enabled:false} },
          scales: { x:{display:false}, y:{display:false} },
          animation: { duration: 150 }
        }
      });
    });
  }, 50);
});
    grid.appendChild(card);
  });

  // Draw sparklines after DOM is ready
  requestAnimationFrame(function() {
    data.forEach(function(item) {
      var canvas = document.getElementById('spark-' + item.cashier.id);
      if (!canvas) return;
      if (_cpCharts[item.cashier.id]) _cpCharts[item.cashier.id].destroy();
      var isOpen = _cpActiveId === item.cashier.id;
      _cpCharts[item.cashier.id] = new Chart(canvas, {
        type: 'line',
        data: {
          labels: ['','','','','','',''],
          datasets: [{ data: item.trend, borderColor: isOpen ? '#3d52d5' : '#c4c9dd', borderWidth: 1.5, fill: true, backgroundColor: isOpen ? 'rgba(61,82,213,0.07)' : 'rgba(196,201,221,0.1)', tension: 0.4, pointRadius: 0 }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: { x: { display: false }, y: { display: false } },
          animation: { duration: 150 }
        }
      });
    });
  });
}