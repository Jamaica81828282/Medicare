// ── CLOCK ───────────────────────────────────────────────────
function tick(){
  var n=new Date(),h=n.getHours(),m=n.getMinutes(),s=n.getSeconds(),p=h>=12?'PM':'AM';
  h=h%12||12;
  document.getElementById('clock').textContent=(h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s+' '+p;
}
setInterval(tick,1000); tick();

// ── SECTION SWITCH ──────────────────────────────────────────
var TITLES={dashboard:'Dashboard',products:'Products',batches:'Batch Entry',suppliers:'Suppliers',invoices:'Invoice History',users:'Cashier Accounts','report-sales':'Sales Report','report-inventory':'Inventory Report','report-cashier':'Cashier Performance'};
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

// ── FIXED: editProduct takes just the ID now ─────────────
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
    // Image preview — use the image route if product has one
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

// ── FIXED: toggleProduct ─────────────────────────────────
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
    if(!rows.length){ tb.innerHTML='<tr><td colspan="10"><div class="empty-state"><span class="material-symbols-rounded">receipt_long</span><p>No invoices found</p></div></td></tr>'; document.getElementById('invPagination').style.display='none'; return; }
    var SC={paid:'badge-paid',voided:'badge-voided',draft:'badge-draft',issued:'badge-draft'};
    tb.innerHTML=rows.map(function(inv){
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

function viewInvItems(id,num){
  document.getElementById('invItemsTitle').textContent='Items — '+num;
  document.getElementById('invItemsTbody').innerHTML='<tr><td colspan="9" style="text-align:center;padding:20px;color:#9aa3c2;">Loading…</td></tr>';
  openModal('invItemsModal');
  fetch(R.invItems(id),{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(items){
    document.getElementById('invItemsTbody').innerHTML=items.map(function(i){
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
    }).join('');
  });
}

function exportInvoicesCSV(){
  var st=document.getElementById('invStatusFilter').value;
  var df=document.getElementById('invDateFrom').value;
  var dt=document.getElementById('invDateTo').value;
  window.location=R.invExport+'?status='+st+'&date_from='+df+'&date_to='+dt;
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
  var df = document.getElementById('reportSalesFromDate').value;
  var dt = document.getElementById('reportSalesToDate').value;
  if(!df || !dt){ showToast('Please select both From and To dates','warning'); return; }

  document.getElementById('reportDateRange').textContent = 'Loading…';
  ['reportTotalInvoices','reportTotalRevenue','reportAOV','reportVoided']
    .forEach(function(id){ document.getElementById(id).textContent='…'; });

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
    document.getElementById('reportTotalRevenue').textContent  = '₱'+revenue.toFixed(2);
    document.getElementById('reportAOV').textContent           = '₱'+aov.toFixed(2);
    document.getElementById('reportVoided').textContent        = voided.length;
    document.getElementById('reportDateRange').textContent     = paid.length+' paid invoice(s) from '+df+' to '+dt;

    // ── Trend Chart ──
    var byDate={};
    paid.forEach(function(i){
      var d=i.invoice_date?i.invoice_date.slice(0,10):'?';
      byDate[d]=(byDate[d]||0)+(parseFloat(i.grand_total)||0);
    });
    var tLabels=Object.keys(byDate).sort();
    var tData=tLabels.map(function(d){ return byDate[d]; });

    if(_reportTrendChart) _reportTrendChart.destroy();
    var tCtx=document.getElementById('reportTrendChart');
    if(tCtx){
      _reportTrendChart=new Chart(tCtx,{
        type:'bar',
        data:{
          labels:tLabels,
          datasets:[{
            label:'Revenue',data:tData,
            backgroundColor:'rgba(61,82,213,0.15)',
            borderColor:'#3d52d5',borderWidth:2,
            borderRadius:8,borderSkipped:false,
            hoverBackgroundColor:'rgba(61,82,213,0.3)',
            barPercentage: 0.4,     
            categoryPercentage: 0.6,   
          }]
        },
        options:{
          responsive:true,maintainAspectRatio:false,
          plugins:{legend:{display:false},tooltip:{
            callbacks:{label:function(ctx){ return ' ₱'+ctx.parsed.y.toLocaleString('en-PH',{minimumFractionDigits:2}); }}
          }},
          scales:{
            x:{grid:{display:false},border:{display:false},
               ticks:{font:{size:11},color:'#9aa3c2'}},
            y:{grid:{color:'rgba(0,0,0,0.04)'},border:{display:false},
               ticks:{callback:function(v){ return v>=1000?'₱'+(v/1000).toFixed(1)+'k':'₱'+v; },color:'#9aa3c2'}}
          }
        }
      });
    }

    // ── Payment Donut ──
    var byPay={};
    paid.forEach(function(i){
      var m=i.payment_method||'Unknown';
      byPay[m]=(byPay[m]||0)+(parseFloat(i.grand_total)||0);
    });
    var pLabels=Object.keys(byPay);
    var pTotals=pLabels.map(function(k){ return byPay[k]; });
    var COLORS=['#3d52d5','#16a34a','#d97706','#e11d48','#0891b2','#7c3aed'];
    var grand=pTotals.reduce(function(a,b){ return a+b; },0);

    if(_reportPayChart) _reportPayChart.destroy();
    var pCtx=document.getElementById('reportPayChart');
    if(pCtx && pLabels.length){
      _reportPayChart=new Chart(pCtx,{
        type:'doughnut',
        data:{labels:pLabels,datasets:[{
          data:pTotals,
          backgroundColor:COLORS.slice(0,pLabels.length),
          borderWidth:3,borderColor:'#ffffff',hoverOffset:6
        }]},
        options:{responsive:true,maintainAspectRatio:false,cutout:'70%',
                 plugins:{legend:{display:false},tooltip:{
                   callbacks:{label:function(ctx){
                     var pct=grand>0?((ctx.parsed/grand)*100).toFixed(1):0;
                     return ' ₱'+ctx.parsed.toLocaleString('en-PH',{minimumFractionDigits:2})+' ('+pct+'%)';
                   }}
                 }}}
      });
      document.getElementById('reportPayLegend').innerHTML=pLabels.map(function(lbl,i){
        var pct=grand>0?((pTotals[i]/grand)*100).toFixed(1):0;
        return '<div style="display:flex;align-items:center;gap:8px;font-size:12px;">'+
          '<div style="width:9px;height:9px;border-radius:2px;background:'+COLORS[i]+';flex-shrink:0;"></div>'+
          '<span style="color:#6b7494;flex:1;">'+escH(lbl)+'</span>'+
          '<span style="font-weight:600;color:#1a1d2e;">'+pct+'%</span></div>';
      }).join('');
    }

    // ── Top Products — use server-side aggregated top products ──
    var topProducts = res.top_products || [];
    if (!topProducts || !topProducts.length) {
      document.getElementById('reportTopProdsTbody').innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#9aa3c2;">No product data available</td></tr>';
    } else {
      document.getElementById('reportTopProdsTbody').innerHTML = topProducts.map(function(p,i){
        return '<tr>'+ 
          '<td style="color:#9aa3c2;font-weight:700;">'+(i+1)+'</td>'+ 
          '<td style="font-weight:600;">'+escH(p.product_name)+'</td>'+ 
          '<td><span style="background:#eef1ff;color:#3d52d5;font-weight:700;padding:3px 10px;border-radius:8px;font-size:12px;">'+(parseFloat(p.qty_sold)||0).toFixed(0)+' units</span></td>'+ 
          '<td style="font-weight:700;color:#16a34a;">₱'+(parseFloat(p.revenue)||0).toFixed(2)+'</td>'+ 
        '</tr>';
      }).join('');
    }
    // ── Transactions Table ──
    document.getElementById('reportInvTableTitle').textContent='Transactions ('+rows.length+')';
    var SC={paid:'badge-paid',voided:'badge-voided',draft:'badge-draft',issued:'badge-draft'};
    document.getElementById('reportInvTbody').innerHTML=rows.map(function(inv){
      return '<tr>'+
        '<td style="font-family:\'DM Mono\',monospace;font-size:11px;">'+escH(inv.invoice_number)+'</td>'+
        '<td style="color:#9aa3c2;font-size:12px;">'+(inv.invoice_date?inv.invoice_date.slice(0,10):'')+'</td>'+
        '<td style="font-weight:600;">'+escH(inv.customer_name||'Walk-in')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(inv.cashier_name||'—')+'</td>'+
        '<td style="color:#6b7494;font-size:12px;">'+escH(inv.payment_method||'—')+'</td>'+
        '<td style="font-weight:700;">₱'+parseFloat(inv.grand_total).toFixed(2)+'</td>'+
        '<td><span class="badge '+(SC[inv.status]||'badge-draft')+'">'+inv.status.charAt(0).toUpperCase()+inv.status.slice(1)+'</span></td>'+
      '</tr>';
    }).join('');

    // Show sections
    document.getElementById('reportChartsRow').style.display    = 'grid';
    document.getElementById('reportTopProdsWrap').style.display = 'block';
    document.getElementById('reportInvTableWrap').style.display = 'block';
  })
  .catch(function(err){
    console.error('Report error:',err);
    showToast('Failed to generate report: '+err.message,'error');
  });
}

function exportSalesReportCSV(){
  var df=document.getElementById('reportSalesFromDate').value;
  var dt=document.getElementById('reportSalesToDate').value;
  window.location=R.invExport+'?status=paid&date_from='+df+'&date_to='+dt;
}
function loadInventoryReport(){
    document.getElementById('reportTotalProducts').textContent = STATS.total_products;
    document.getElementById('reportLowStock').textContent      = STATS.low_stock;
    document.getElementById('reportExpiring').textContent      = STATS.expiring_30;
    document.getElementById('reportTotalSuppliers').textContent = STATS.total_suppliers;
}

function loadCashierPerformanceReport(){
  fetch(R.users,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
  .then(function(r){return r.json();})
  .then(function(users){
    var cashiers=users.filter(function(u){ return u.role_name==='cashier'; });
    var tb=document.getElementById('cashierPerfTableBody');
    if(!cashiers.length){ tb.innerHTML='<tr><td colspan="7"><div class="empty-state"><span class="material-symbols-rounded">people</span><p>No cashiers found</p></div></td></tr>'; return; }
    fetch(R.invoices+'?page=1',{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(invoiceRes){
      var allInvoices=invoiceRes.data||[];
      var today=new Date().toISOString().slice(0,10);
      var thisMonth=new Date().toISOString().substring(0,7);
      tb.innerHTML=cashiers.map(function(c){
        var ci=allInvoices.filter(function(i){ return i.cashier_id==c.id; });
        var todayInv=ci.filter(function(i){ return i.invoice_date&&i.invoice_date.slice(0,10)===today&&i.status!=='voided'; });
        var todayRev=todayInv.reduce(function(s,i){ return s+(parseFloat(i.grand_total)||0); },0);
        var monthInv=ci.filter(function(i){ return i.invoice_date&&i.invoice_date.substring(0,7)===thisMonth&&i.status!=='voided'; });
        var monthRev=monthInv.reduce(function(s,i){ return s+(parseFloat(i.grand_total)||0); },0);
        var voided=ci.filter(function(i){ return i.status==='voided'; }).length;
        return '<tr>'+
          '<td style="font-weight:600;font-size:13px;">'+escH(c.name)+'</td>'+
          '<td style="text-align:center;">'+todayInv.length+'</td>'+
          '<td style="color:#16a34a;font-weight:600;">₱'+todayRev.toFixed(2)+'</td>'+
          '<td style="text-align:center;">'+monthInv.length+'</td>'+
          '<td style="color:#16a34a;font-weight:600;">₱'+monthRev.toFixed(2)+'</td>'+
          '<td style="text-align:center;color:#dc2626;font-weight:600;">'+voided+'</td>'+
          '<td><button class="btn-sm blue" onclick="window.location.href=R.cashierView('+c.id+')"><span class="material-symbols-rounded">visibility</span>View</button></td>'+
        '</tr>';
      }).join('');
    });
  });
}

// ── INIT ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
  loadProducts();
  loadBatches();
  loadProductsForSelect();
  loadSuppliers();
  loadInvoices();
  loadUsers();
  loadCashierSelector();
  loadInventoryReport();
});