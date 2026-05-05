// ── STATE ──────────────────────────────────────────────────────────────
var profile            = null;
var cart               = [];
var activeCat          = 'all';
var modalProduct       = null;
var draftInvoiceId     = null;
var draftInvoiceNumber = null;
var queueNumber        = null;   // NEW — e.g. "Q-001"

 

// ── SCREENS ───────────────────────────────────────────────────────────
function showScreen(id) {
  document.querySelectorAll('.screen').forEach(function(s){ s.classList.remove('active'); });
  document.getElementById(id).classList.add('active');
}
function goProfile(type) {
  document.getElementById('profileTitle').textContent = type === 'returning' ? 'Welcome Back' : 'New Customer';
  document.getElementById('profileSub').textContent   = type === 'returning' ? 'Search for your profile or fill in your details below' : 'Fill in your details to continue';
  document.getElementById('returnSearch').style.display = type === 'returning' ? 'block' : 'none';
  // Clear any previously selected customer
  document.getElementById('customerId').value = '';
  document.getElementById('profileForm').reset();
  document.getElementById('discIdWrap').classList.remove('show');
  showScreen('screen-profile');
}

// ── RETURNING CUSTOMER SEARCH ──────────────────────────────────────────
var sTimer;
function searchCustomer(val) {
  clearTimeout(sTimer);
  var dropdown = document.getElementById('searchDropdown');
  var loading  = document.getElementById('searchLoading');
  var noResult = document.getElementById('searchNoResult');
  var list     = document.getElementById('searchResultsList');

  if (val.length < 2) { dropdown.classList.remove('show'); return; }

  dropdown.classList.add('show');
  loading.style.display  = 'block';
  noResult.style.display = 'none';
  list.innerHTML         = '';

  sTimer = setTimeout(function() {
    fetch(ROUTE_SEARCH + '?q=' + encodeURIComponent(val), {
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(function(r){ return r.json(); })
    .then(function(customers) {
      loading.style.display = 'none';
      if (!customers.length) { noResult.style.display = 'block'; return; }
      list.innerHTML = customers.map(function(c) {
        var senior  = c.is_senior ? ' · Senior' : '';
        var pwd     = c.is_pwd    ? ' · PWD'    : '';
        var encoded = escHtml(JSON.stringify(c));
        return '<div class="search-result-item" onclick="useProfile(\'' + encoded + '\')">' +
          '<div>' +
            '<div class="sr-name">' + escHtml(c.first_name + ' ' + c.last_name) + '</div>' +
            '<div class="sr-detail">' + escHtml(c.phone) + ' · ' + c.order_count + ' past order(s)' + senior + pwd + '</div>' +
          '</div>' +
          '<button class="btn-use" onclick="event.stopPropagation();useProfile(\'' + encoded + '\')">Use Profile</button>' +
        '</div>';
      }).join('');
    })
    .catch(function(err) {
      loading.style.display  = 'none';
      noResult.style.display = 'block';
      console.error('Search error:', err);
    });
  }, 350);
}

// FIX: useProfile now populates the hidden customerId field AND all form fields
// so the backend knows this is a returning customer and reuses the existing record.
function useProfile(jsonStr) {
  var c = JSON.parse(jsonStr);

  profile = {
    id:        c.id,
    first_name: c.first_name,
    last_name:  c.last_name,
    phone:      c.phone,
    age:        c.age || '',
    address:    c.address || '',
    is_senior:  c.is_senior ? 1 : 0,
    is_pwd:     c.is_pwd    ? 1 : 0,
    id_number:  c.id_number || '',
  };

  // Populate hidden ID — KEY FIX: backend reads this to reuse the existing record
  document.getElementById('customerId').value = c.id;

  // Also fill form fields so the customer can review/update their info
  document.getElementById('firstName').value  = c.first_name;
  document.getElementById('lastName').value   = c.last_name;
  document.getElementById('phone').value      = c.phone;
  document.getElementById('age').value        = c.age || '';
  document.getElementById('address').value    = c.address || '';
  document.getElementById('isSenior').checked = !!c.is_senior;
  document.getElementById('isPwd').checked    = !!c.is_pwd;
  document.getElementById('discountId').value = c.id_number || '';
  if (c.is_senior || c.is_pwd) {
    document.getElementById('discIdWrap').classList.add('show');
  }

  document.getElementById('greetName').textContent = c.first_name;
  document.getElementById('searchDropdown').classList.remove('show');
  renderProducts();
  showScreen('screen-shop');
  showToast('Welcome back, ' + c.first_name + '!', 'success');
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── PROFILE FORM ───────────────────────────────────────────────────────
function toggleDiscId() {
  var s = document.getElementById('isSenior').checked;
  var p = document.getElementById('isPwd').checked;
  document.getElementById('discIdWrap').classList.toggle('show', s || p);
}

function submitProfile(e) {
  e.preventDefault();
  var fn = document.getElementById('firstName').value.trim();
  var ln = document.getElementById('lastName').value.trim();
  var ph = document.getElementById('phone').value.trim();
  var ag = document.getElementById('age').value;
  if (!fn || !ln || !ph || !ag) { showPAlert('Please fill in all required fields.'); return; }

  profile = {
    id:        document.getElementById('customerId').value || null,
    first_name: fn, last_name: ln, phone: ph, age: ag,
    address:   document.getElementById('address').value.trim(),
    is_senior: document.getElementById('isSenior').checked ? 1 : 0,
    is_pwd:    document.getElementById('isPwd').checked    ? 1 : 0,
    id_number: document.getElementById('discountId').value.trim(),
  };

  document.getElementById('greetName').textContent = fn;
  renderProducts();
  showScreen('screen-shop');
}

function showPAlert(m) {
  var el = document.getElementById('profileAlert');
  el.textContent = m;
  el.className   = 'profile-alert error';
}

// ── PRODUCTS ───────────────────────────────────────────────────────────
function placeholderIcon() {
  return '<svg class="pc-placeholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>' +
    '<polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
}
function productImage(p) {
  if (p.image_base64) return '<img src="' + escHtml(p.image_base64) + '" alt="' + escHtml(p.product_name) + '" loading="lazy"/>';
  return placeholderIcon();
}
function catLabel(c) {
  return { RX:'Prescription', OTC:'OTC', VITAMINS:'Vitamins', MEDICAL:'Medical', BEAUTY:'Beauty', BABY:'Baby' }[c] || c;
}
function renderProducts() {
  var grid = document.getElementById('productsGrid');
  var q    = (document.getElementById('shopSearch').value || '').toLowerCase();
  var list = products.filter(function(p) {
    var mc = activeCat === 'all' || p.category === activeCat;
    var mq = !q || (p.product_name||'').toLowerCase().includes(q)
                || (p.generic_name||'').toLowerCase().includes(q)
                || (p.brand||'').toLowerCase().includes(q);
    return mc && mq;
  });
  if (!list.length) {
    grid.innerHTML = '<div class="no-results">' +
      '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
      '<p>No products found</p><span>Try a different search or category</span></div>';
    return;
  }
  grid.innerHTML = list.map(function(p) {
    var oos = p.stock_quantity === 0;
    return '<div class="product-card' + (oos ? ' oos' : '') + '" onclick="openModal(' + p.id + ')">' +
      '<div class="pc-img">' + productImage(p) +
        (p.requires_rx ? '<span class="badge-rx">Rx</span>' : '') +
        (oos ? '<span class="badge-oos">Out of Stock</span>' : '') +
        '<span class="badge-cat">' + catLabel(p.category) + '</span>' +
      '</div>' +
      '<div class="pc-info">' +
        '<div class="pc-name">' + escHtml(p.product_name) + '</div>' +
        '<div class="pc-generic">' + escHtml(p.generic_name || '') + '</div>' +
        '<div class="pc-brand">' + escHtml(p.brand || '') + '</div>' +
        '<div class="pc-price">₱' + parseFloat(p.selling_price).toFixed(2) + '</div>' +
      '</div>' +
      '<div class="pc-footer">' +
        '<button class="btn-add" onclick="event.stopPropagation();quickAdd(' + p.id + ')"' + (oos ? ' disabled' : '') + '>' +
          '<svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
          (oos ? 'Out of Stock' : 'Add to Cart') +
        '</button>' +
      '</div>' +
    '</div>';
  }).join('');
}
function setCat(el, cat) {
  document.querySelectorAll('.cat-pill').forEach(function(p){ p.classList.remove('active'); });
  el.classList.add('active');
  activeCat = cat;
  renderProducts();
}

// ── MODAL ──────────────────────────────────────────────────────────────
function openModal(id) {
  var p = products.find(function(x){ return x.id == id; });
  if (!p) return;
  modalProduct = p;
  var oos = p.stock_quantity === 0;

  // FIX: image — set as contain so full image is visible without cropping
  var imgWrap = document.getElementById('modalImgWrap');
  var rxBadge = document.getElementById('modalRxBadge');
  var oldImg  = imgWrap.querySelector('img');
  if (oldImg) oldImg.remove();
  if (p.image_base64) {
    var img = document.createElement('img');
    img.src = p.image_base64;
    img.alt = p.product_name;
    imgWrap.insertBefore(img, imgWrap.firstChild);
    imgWrap.querySelector('.modal-placeholder').style.display = 'none';
  } else {
    imgWrap.querySelector('.modal-placeholder').style.display = '';
  }
  rxBadge.style.display = p.requires_rx ? 'block' : 'none';

  document.getElementById('modalName').textContent    = p.product_name;
  document.getElementById('modalGeneric').textContent = p.generic_name || '';
  document.getElementById('modalPrice').textContent   = '₱' + parseFloat(p.selling_price).toFixed(2);
  document.getElementById('modalQty').textContent     = '1';
  document.getElementById('modalRxWarn').classList.toggle('show', p.requires_rx);

  var meta = '<span class="m-tag">' + catLabel(p.category) + '</span>' +
    '<span class="m-tag">' + escHtml(p.brand || 'Generic') + '</span>' +
    (p.dosage ? '<span class="m-tag">' + escHtml(p.dosage) + '</span>' : '') +
    (oos ? '<span class="m-tag red">Out of Stock</span>' : '<span class="m-tag green">In Stock (' + p.stock_quantity + ')</span>');
  document.getElementById('modalMeta').innerHTML = meta;

  // Description
  document.getElementById('modalDesc').innerHTML =
    '<div class="modal-sec-title">Description</div>' +
    '<p>' + escHtml(p.description || 'No description available.') + '</p>';

  // NEW: Usage & Recommendation — only show the box if the field has data
  var usageBox  = document.getElementById('modalUsageBox');
  var usageText = document.getElementById('modalUsageText');
  if (p.usage_recommendation) {
    usageText.textContent = p.usage_recommendation;
    usageBox.classList.add('show');
  } else {
    usageBox.classList.remove('show');
    usageText.textContent = '';
  }

  var btn = document.getElementById('modalAddBtn');
  btn.disabled  = oos;
  btn.innerHTML = oos
    ? 'Out of Stock'
    : '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>' +
      '<line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Add to Cart';

  document.getElementById('productModal').classList.add('show');
}
function closeModal() {
  document.getElementById('productModal').classList.remove('show');
  modalProduct = null;
}
function closeBg(e) {
  if (e.target === document.getElementById('productModal')) closeModal();
}
function changeQty(d) {
  var el = document.getElementById('modalQty');
  var v  = parseInt(el.textContent) + d;
  if (v < 1) v = 1;
  if (modalProduct && v > modalProduct.stock_quantity) v = modalProduct.stock_quantity;
  el.textContent = v;
}
function addFromModal() {
  if (!modalProduct) return;
  addToCart(modalProduct, parseInt(document.getElementById('modalQty').textContent));
  closeModal();
}

// ── CART ───────────────────────────────────────────────────────────────
function quickAdd(id) {
  var p = products.find(function(x){ return x.id == id; });
  if (p && p.stock_quantity > 0) addToCart(p, 1);
}
function addToCart(p, qty) {
  var ex = cart.find(function(i){ return i.id == p.id; });
  if (ex) ex.qty = Math.min(ex.qty + qty, p.stock_quantity);
  else    cart.push({
    id: p.id, name: p.product_name, price: parseFloat(p.selling_price),
    qty: qty, requires_rx: p.requires_rx, stock: p.stock_quantity, image: p.image_base64 || null
  });
  updateCart();
  showToast(p.product_name + ' added to cart', 'success');
}
function removeItem(id) {
  cart = cart.filter(function(i){ return i.id != id; });
  updateCart();
}
function changeItemQty(id, d) {
  var i = cart.find(function(x){ return x.id == id; });
  if (!i) return;
  i.qty += d;
  if (i.qty <= 0)      { removeItem(id); return; }
  if (i.qty > i.stock)  i.qty = i.stock;
  updateCart();
}
function cartItemIcon(item) {
  if (item.image) {
    return '<img src="' + escHtml(item.image) + '" alt="' + escHtml(item.name) + '" ' +
      'style="width:100%;height:100%;object-fit:contain;padding:4px;border-radius:12px;"/>';
  }
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#93c5fd">' +
    '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>' +
    '<polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
}
function updateCart() {
  var count = cart.reduce(function(s,i){ return s + i.qty; }, 0);
  var sub   = cart.reduce(function(s,i){ return s + i.price * i.qty; }, 0);
  var vat   = sub * 0.12;
  var grand = sub + vat;

  var badge = document.getElementById('cartBadge');
  badge.textContent = count;
  badge.classList.toggle('show', count > 0);
  document.getElementById('cartCountLbl').textContent = count + ' item' + (count !== 1 ? 's' : '');

  var list = document.getElementById('cartItemsList');
  if (!cart.length) {
    list.innerHTML = '<div class="cart-empty">' +
      '<svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>' +
      '<line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>' +
      '<p>Your cart is empty</p><span>Browse and add items to get started</span></div>';
    document.getElementById('cartFtr').style.display = 'none';
    return;
  }
  list.innerHTML = cart.map(function(i) {
    return '<div class="cart-item">' +
      '<div class="ci-icon">' + cartItemIcon(i) + '</div>' +
      '<div class="ci-info">' +
        '<div class="ci-name">' + escHtml(i.name) + '</div>' +
        '<div class="ci-price">₱' + i.price.toFixed(2) + ' each</div>' +
        (i.requires_rx ? '<div class="ci-rx">Prescription required</div>' : '') +
      '</div>' +
      '<div class="ci-qty">' +
        '<button class="cq-btn" onclick="changeItemQty(' + i.id + ',-1)">&#8722;</button>' +
        '<span class="cq-val">' + i.qty + '</span>' +
        '<button class="cq-btn" onclick="changeItemQty(' + i.id + ',1)">&#43;</button>' +
      '</div>' +
      '<button class="ci-remove" onclick="removeItem(' + i.id + ')">' +
        '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>' +
        '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>' +
      '</button>' +
    '</div>';
  }).join('');

  document.getElementById('cartSub').textContent   = '₱' + sub.toFixed(2);
  document.getElementById('cartVat').textContent   = '₱' + vat.toFixed(2);
  document.getElementById('cartTotal').textContent = '₱' + grand.toFixed(2);
  document.getElementById('cartFtr').style.display = 'block';
}
function toggleCart() {
  document.getElementById('cartDrawer').classList.toggle('open');
  document.getElementById('cartOv').classList.toggle('show');
}

// ── PROCEED TO CASHIER ─────────────────────────────────────────────────
function proceedToOrder() {
  if (!cart.length) return;
  var sub   = cart.reduce(function(s,i){ return s + i.price * i.qty; }, 0);
  var grand = sub + sub * 0.12;

  document.getElementById('confirmItems').innerHTML = cart.map(function(i){
    return '<div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid #f1f5f9;">' +
      '<span style="color:#334155;">' + escHtml(i.name) + ' x' + i.qty + '</span>' +
      '<span style="color:#002045;font-weight:700;">₱' + (i.price * i.qty).toFixed(2) + '</span></div>';
  }).join('');
  document.getElementById('confirmTotal').textContent    = '₱' + grand.toFixed(2);
  document.getElementById('confirmInvNumber').textContent = draftInvoiceId
    ? ('Updating ' + draftInvoiceNumber + '…') : 'Sending order…';

  toggleCart();
  showScreen('screen-confirm');

  var isUpdate = !!draftInvoiceId;
  var url      = isUpdate ? ROUTE_UPDATE : ROUTE_SUBMIT;
  var payload  = {
    customer: profile,
    items: cart.map(function(i){ return { id: i.id, qty: i.qty }; }),
  };
  if (isUpdate) payload.invoice_id = draftInvoiceId;

  fetch(url, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body:    JSON.stringify(payload),
  })
  .then(function(r){ return r.json(); })
  .then(function(data) { if (data.success) {
      draftInvoiceId     = data.invoice_id;
      draftInvoiceNumber = data.invoice_number;
      document.getElementById('confirmInvNumber').textContent = 'Invoice #: ' + data.invoice_number;
 
      // NEW — show queue number if the server returned one
      if (data.queue_number) {
        queueNumber = data.queue_number;
        document.getElementById('queueNumberDisplay').textContent = data.queue_number;
        document.getElementById('queueNumberBox').style.display = 'block';
      }
 
      showToast((isUpdate ? 'Order updated! ' : 'Order saved! ') + data.invoice_number, 'success');
    } else {
      showToast('Error saving order. Please see cashier.', 'error-toast');
      document.getElementById('confirmInvNumber').textContent = 'Error — please see cashier';
    }
  })
  .catch(function(err) {
    console.error('Submit error:', err);
    document.getElementById('confirmInvNumber').textContent = 'Network error — see cashier';
    showToast('Network error. Please inform cashier.', 'error-toast');
  });
}

// ── ADD MORE ITEMS ─────────────────────────────────────────────────────
function addMoreItems() {
  document.getElementById('draftBanner').style.display = draftInvoiceId ? 'flex' : 'none';
  showScreen('screen-shop');
}

// ── RESET (full session end) ───────────────────────────────────────────
function finishAndReset() {
  cart = []; profile = null; activeCat = 'all';
  draftInvoiceId = null; draftInvoiceNumber = null;
  queueNumber = null;   // NEW
  document.getElementById('queueNumberBox').style.display = 'none';
  document.getElementById('queueNumberDisplay').textContent = '—';
  document.getElementById('profileForm').reset();
  document.getElementById('customerId').value = '';
  document.getElementById('shopSearch').value = '';
  document.getElementById('discIdWrap').classList.remove('show');
  document.getElementById('searchDropdown').classList.remove('show');
  document.getElementById('returnSearch').style.display = 'none';
  document.getElementById('profileAlert').className = 'profile-alert';
  document.getElementById('draftBanner').style.display = 'none';
  document.querySelectorAll('.cat-pill').forEach(function(p){ p.classList.remove('active'); });
  document.querySelector('[data-cat="all"]').classList.add('active');
  updateCart();
  showScreen('screen-welcome');
}

// ── TOAST ──────────────────────────────────────────────────────────────
var tTimer;
function showToast(msg, type) {
  var t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.className = 'toast show ' + (type || '');
  clearTimeout(tTimer);
  tTimer = setTimeout(function(){ t.className = 'toast'; }, 3000);
}

document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
updateCart();
