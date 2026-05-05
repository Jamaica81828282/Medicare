<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>MediCare Pharmacy — Kiosk</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">
</head>
<body class="font-manrope bg-slate-50 text-slate-900 overflow-hidden">

{{-- ═══════════════════════════════════════════════ --}}
{{-- SCREEN 1: WELCOME                               --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="screen active" id="screen-welcome">
  <div class="w-full h-screen flex flex-col items-center justify-center relative overflow-hidden"
       style="background: linear-gradient(145deg, #001530 0%, #002045 50%, #0a3060 100%);">
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>
    <div class="absolute -top-32 -left-32 w-[450px] h-[450px] rounded-full opacity-[0.12] blur-3xl"
         style="background: #3b82f6;"></div>
    <div class="absolute -bottom-24 -right-24 w-[350px] h-[350px] rounded-full opacity-[0.10] blur-3xl"
         style="background: #0d9488;"></div>

    <div class="relative z-10 flex flex-col items-center text-center px-8">
      <div class="flex items-center gap-4 mb-14">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center border border-white/20"
             style="background: rgba(255,255,255,0.1);">
          <span class="material-symbols-outlined icon-fill text-white text-3xl">medication</span>
        </div>
        <div class="text-left">
          <div class="text-2xl font-extrabold text-white tracking-tight leading-none">MediCare</div>
          <div class="text-[10px] text-white/40 tracking-[0.18em] uppercase mt-1">Pharmacy System</div>
        </div>
      </div>

      <h1 class="text-[52px] font-extrabold text-white leading-tight tracking-tight mb-4">
        Welcome to<br><span style="color: #7ab4f0;">MediCare Pharmacy</span>
      </h1>
      <p class="text-white/50 text-base mb-14 max-w-md leading-relaxed">
        Browse our full range of medicines and health products.<br>Select an option below to get started.
      </p>

      <div class="flex gap-4">
        <button onclick="goProfile('new')"
          class="flex items-center gap-3 bg-white text-[#002045] h-14 px-10 rounded-xl font-bold text-sm
                 shadow-xl hover:shadow-2xl hover:-translate-y-0.5 active:scale-95 transition-all duration-200">
          <span class="material-symbols-outlined text-xl">person_add</span>
          New Customer
        </button>
        <button onclick="goProfile('returning')"
          class="flex items-center gap-3 h-14 px-10 rounded-xl font-bold text-sm text-white
                 border border-white/25 hover:bg-white/15 hover:-translate-y-0.5 active:scale-95 transition-all duration-200"
          style="background: rgba(255,255,255,0.1);">
          <span class="material-symbols-outlined text-xl">group</span>
          Returning Customer
        </button>
      </div>
    </div>

    <div class="absolute bottom-6 text-white/20 text-xs tracking-widest">
      MediCare Pharmacy — Customer Kiosk
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- SCREEN 2: PROFILE                               --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="screen" id="screen-profile">
  <div class="w-full h-screen bg-slate-100 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl relative overflow-hidden"
         style="padding: 44px 52px 40px; box-shadow: 0 20px 60px rgba(0,32,69,.12);">
      <div class="absolute top-0 left-0 right-0 h-[3px]"
           style="background: linear-gradient(90deg, #2563eb, #0d9488);"></div>

      <button onclick="showScreen('screen-welcome')"
        class="absolute top-5 left-5 w-9 h-9 rounded-xl bg-slate-100 hover:bg-blue-50 flex items-center
               justify-center text-slate-500 hover:text-blue-700 transition-colors">
        <span class="material-symbols-outlined text-xl">arrow_back</span>
      </button>

      <h2 class="text-2xl font-extrabold text-[#002045] mb-1" id="profileTitle">New Customer</h2>
      <p class="text-xs text-slate-400 mb-2" id="profileSub">Fill in your details to continue</p>
      <div class="w-9 h-0.5 rounded-full mb-6" style="background: linear-gradient(90deg,#2563eb,#0d9488);"></div>

      <div id="returnSearch" style="display:none;" class="mb-5">
        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">Search by Name or Phone Number</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
            <input type="text" id="returnInput"
              placeholder="e.g. Juan dela Cruz or 09123456789"
              oninput="searchCustomer(this.value)" autocomplete="off"
              class="w-full h-12 pl-11 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                     focus:border-blue-500 focus:bg-white outline-none transition-all"/>
          </div>
          <div id="searchDropdown" class="search-dropdown">
            <div id="searchLoading" style="display:none;" class="search-loading">Searching...</div>
            <div id="searchResultsList"></div>
            <div id="searchNoResult" style="display:none;" class="search-no-result">No customers found</div>
          </div>
        </div>
        <div class="text-center text-xs text-slate-400 my-4">— or create a new profile below —</div>
      </div>

      <div id="profileAlert" class="profile-alert"></div>

      <form id="profileForm" onsubmit="submitProfile(event)">
        <div class="grid grid-cols-2 gap-x-5 gap-y-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">First Name</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person</span>
              <input type="text" id="firstName" placeholder="Juan" required
                class="w-full h-12 pl-10 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                       focus:border-blue-500 focus:bg-white outline-none transition-all"/>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">Last Name</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person</span>
              <input type="text" id="lastName" placeholder="Dela Cruz" required
                class="w-full h-12 pl-10 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                       focus:border-blue-500 focus:bg-white outline-none transition-all"/>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">Phone Number</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">phone</span>
              <input type="tel" id="phone" placeholder="09XXXXXXXXX" required
                class="w-full h-12 pl-10 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                       focus:border-blue-500 focus:bg-white outline-none transition-all"/>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">Age</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">calendar_today</span>
              <input type="number" id="age" placeholder="25" min="1" max="120" required
                class="w-full h-12 pl-10 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                       focus:border-blue-500 focus:bg-white outline-none transition-all"/>
            </div>
          </div>
          <div class="col-span-2 flex flex-col gap-1.5">
            <label class="text-[10px] font-bold uppercase tracking-[0.08em] text-slate-400">
              Address <span class="normal-case font-normal opacity-60">(Optional)</span>
            </label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">location_on</span>
              <input type="text" id="address" placeholder="House No., Street, Barangay, City"
                class="w-full h-12 pl-10 pr-4 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm
                       focus:border-blue-500 focus:bg-white outline-none transition-all"/>
            </div>
          </div>
          <div class="col-span-2">
            <div class="bg-blue-50 border-2 border-blue-100 rounded-xl p-4">
              <div class="text-[10px] font-bold uppercase tracking-[0.08em] text-blue-600 mb-3">Discount Eligibility</div>
              <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-700">
                  <input type="checkbox" id="isSenior" onchange="toggleDiscId()" class="accent-blue-600 w-4 h-4 cursor-pointer"/>
                  Senior Citizen (60+)
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-700">
                  <input type="checkbox" id="isPwd" onchange="toggleDiscId()" class="accent-blue-600 w-4 h-4 cursor-pointer"/>
                  Person with Disability (PWD)
                </label>
              </div>
              <div id="discIdWrap" class="disc-id">
                <div class="relative">
                  <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">badge</span>
                  <input type="text" id="discountId" placeholder="Senior/PWD ID Number (cashier will verify)"
                    class="w-full h-12 pl-10 pr-4 bg-white border-2 border-blue-200 rounded-xl text-sm
                           focus:border-blue-500 outline-none transition-all"/>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- FIX: hidden field carries the existing customer ID so the backend never creates a duplicate --}}
        <input type="hidden" id="customerId" value=""/>

        <button type="submit"
          class="mt-6 w-full h-14 bg-[#002045] hover:bg-[#1a3a6b] text-white rounded-xl font-bold text-sm
                 flex items-center justify-center gap-2 transition-all hover:-translate-y-0.5 active:scale-95"
          style="box-shadow: 0 4px 18px rgba(0,32,69,.25);">
          <span>Continue to Shop</span>
          <span class="material-symbols-outlined text-xl">arrow_forward</span>
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- SCREEN 3: SHOP                                  --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="screen" id="screen-shop">
  <header class="bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0"
          style="height: 66px; box-shadow: 0 2px 8px rgba(0,32,69,.06);">
    <div class="flex items-center gap-3 flex-shrink-0">
      <div class="w-9 h-9 bg-[#002045] rounded-xl flex items-center justify-center">
        <span class="material-symbols-outlined icon-fill text-white text-xl">medication</span>
      </div>
      <span class="text-lg font-extrabold text-[#002045] tracking-tight">MediCare</span>
    </div>

    <div class="relative flex-1 max-w-xl mx-8">
      <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
      <input type="text" id="shopSearch"
        placeholder="Search medicines by name, brand, or condition..."
        oninput="renderProducts()"
        class="w-full h-12 pl-12 pr-4 bg-slate-100 border-2 border-transparent
               focus:border-[#002045] focus:bg-white rounded-xl text-sm outline-none transition-all"/>
    </div>

    <div class="flex items-center gap-4 flex-shrink-0">
      <div class="text-sm text-slate-500">
        Good day, <strong class="text-[#002045] font-bold" id="greetName">Customer</strong>
      </div>
      <button onclick="toggleCart()"
        class="relative flex items-center gap-2 bg-[#002045] hover:bg-[#1a3a6b] text-white h-12 px-5
               rounded-xl font-bold text-sm transition-all active:scale-95">
        <span class="material-symbols-outlined icon-fill text-xl">shopping_cart</span>
        <span>Cart</span>
        <span id="cartBadge"
          class="cart-badge absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold
                 w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">0</span>
      </button>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">
    <aside class="w-60 bg-white border-r border-slate-100 flex flex-col py-5 flex-shrink-0 overflow-y-auto hide-scrollbar"
           style="box-shadow: 2px 0 8px rgba(0,32,69,.04);">
      <div class="px-5 mb-4">
        <h2 class="text-sm font-extrabold text-[#002045]">Categories</h2>
        <p class="text-xs text-slate-400">Browse by department</p>
      </div>
      <nav class="flex flex-col gap-0.5 px-2">
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm active" data-cat="all" onclick="setCat(this,'all')">
          <span class="material-symbols-outlined text-xl">grid_view</span><span>All Products</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="RX" onclick="setCat(this,'RX')">
          <span class="material-symbols-outlined text-xl">medication</span><span>Prescription</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="OTC" onclick="setCat(this,'OTC')">
          <span class="material-symbols-outlined text-xl">pill</span><span>Over-the-Counter</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="VITAMINS" onclick="setCat(this,'VITAMINS')">
          <span class="material-symbols-outlined text-xl">vaccines</span><span>Vitamins</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="MEDICAL" onclick="setCat(this,'MEDICAL')">
          <span class="material-symbols-outlined text-xl">health_and_safety</span><span>Medical Supplies</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="BEAUTY" onclick="setCat(this,'BEAUTY')">
          <span class="material-symbols-outlined text-xl">face</span><span>Beauty & Care</span>
        </button>
        <button class="cat-pill flex items-center gap-3 px-4 py-3 rounded-xl text-sm" data-cat="BABY" onclick="setCat(this,'BABY')">
          <span class="material-symbols-outlined text-xl">child_care</span><span>Baby & Maternal</span>
        </button>
      </nav>
    </aside>

    <main class="flex-1 overflow-y-auto hide-scrollbar bg-slate-50 p-6">
      <div id="draftBanner" class="draft-banner" style="display:none;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Your order is being held. Add more items and click <strong style="margin: 0 4px;">Proceed to Cashier</strong> when done — your invoice will be updated.
      </div>
      <div id="productsGrid" class="grid gap-5"
           style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));"></div>
    </main>
  </div>
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- SCREEN 4: CONFIRM                               --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="screen" id="screen-confirm">
  <div class="w-full h-screen bg-slate-100 flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md relative overflow-hidden text-center"
         style="padding: 44px 52px; box-shadow: 0 20px 60px rgba(0,32,69,.12);">
      <div class="absolute top-0 left-0 right-0 h-[3px]"
           style="background: linear-gradient(90deg,#2563eb,#0d9488);"></div>

      <div class="w-18 h-18 rounded-full flex items-center justify-center mx-auto mb-5"
           style="width:72px;height:72px;background:#f0fdf4;border:2px solid #bbf7d0;">
        <span class="material-symbols-outlined icon-fill text-green-600" style="font-size:36px;">check_circle</span>
      </div>

       <h2 class="text-3xl font-extrabold text-[#002045] mb-2">Order Sent!</h2>
      <div class="text-sm font-bold text-blue-600 mb-1" id="confirmInvNumber"></div>
 
      {{-- NEW: Queue number display --}}
      <div id="queueNumberBox" style="display:none;margin:10px 0 14px;">
        <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">Your Queue Number</div>
        <div id="queueNumberDisplay"
             style="font-size:72px;font-weight:900;color:#002045;line-height:1;letter-spacing:-.02em;font-family:'Manrope',sans-serif;">
          —
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">Watch the display screen and listen for your number</div>
      </div>
 
      <p class="text-sm text-slate-400 leading-relaxed mb-7">
        Your order has been sent to the cashier.<br>Please pay at the counter, then wait for your number to be called.
      </p>

      <div class="bg-slate-50 rounded-xl p-5 mb-5 text-left">
        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Order Summary</div>
        <div id="confirmItems"></div>
        <div class="flex justify-between text-base font-extrabold text-[#002045] mt-4 pt-4 border-t-2 border-slate-200">
          <span>Total (incl. VAT)</span>
          <span id="confirmTotal"></span>
        </div>
      </div>

      <div class="flex items-center gap-2 justify-center bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 text-sm text-amber-700 font-semibold mb-6">
        <span class="material-symbols-outlined text-xl">info</span>
        Status: Pending — please proceed to the cashier counter
      </div>

      <div class="flex gap-3">
        <button onclick="addMoreItems()"
          class="flex-1 h-12 rounded-xl border-2 border-slate-200 bg-white text-[#002045] font-bold text-sm
                 flex items-center justify-center gap-2 hover:border-blue-400 hover:bg-blue-50 transition-all">
          <span class="material-symbols-outlined text-xl">arrow_back</span>
          Add More Items
        </button>
        <button onclick="finishAndReset()"
          class="flex-1 h-12 rounded-xl bg-[#002045] hover:bg-[#1a3a6b] text-white font-bold text-sm
                 flex items-center justify-center gap-2 transition-all"
          style="box-shadow: 0 3px 12px rgba(0,32,69,.25);">
          <span class="material-symbols-outlined text-xl icon-fill">check</span>
          Done
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- PRODUCT DETAIL MODAL                            --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="modal-ov" id="productModal" onclick="closeBg(event)">
  <div class="prod-modal">
    <button class="modal-close" onclick="closeModal()">
      <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    {{-- FIX: image container uses object-fit:contain so full product image is always visible --}}
    <div class="modal-img" id="modalImgWrap">
      <svg class="modal-placeholder" viewBox="0 0 24 24">
        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
      </svg>
      <span class="modal-rx-badge" id="modalRxBadge" style="display:none;">Rx Required</span>
    </div>

    <div class="modal-body">
      <div class="modal-name" id="modalName"></div>
      <div class="modal-generic" id="modalGeneric"></div>
      <div class="modal-meta" id="modalMeta"></div>

      <div class="rx-warn" id="modalRxWarn">
        <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        This medicine requires a valid prescription. The cashier will verify your prescription before dispensing this item.
      </div>

      {{-- Description --}}
      <div class="modal-sec" id="modalDesc"></div>

      {{-- NEW: Usage & Recommendation section — only shown when data exists --}}
      <div class="modal-usage-box" id="modalUsageBox">
        <div class="modal-usage-title">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Usage &amp; Recommendation
        </div>
        <p id="modalUsageText"></p>
      </div>

      <div class="modal-price-row">
        <div class="modal-price" id="modalPrice"></div>
        <div class="modal-qty-add">
          <div class="qty-ctrl">
            <button class="qty-btn" onclick="changeQty(-1)">&#8722;</button>
            <span class="qty-val" id="modalQty">1</span>
            <button class="qty-btn" onclick="changeQty(1)">&#43;</button>
          </div>
          <button class="btn-modal-add" id="modalAddBtn" onclick="addFromModal()">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Add to Cart
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════ --}}
{{-- CART DRAWER                                     --}}
{{-- ═══════════════════════════════════════════════ --}}
<div class="cart-ov" id="cartOv" onclick="toggleCart()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-hdr">
    <div class="cart-hdr-left">
      <div class="cart-hdr-icon">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      </div>
      <div>
        <div class="cart-title">My Cart</div>
        <div class="cart-count-lbl" id="cartCountLbl">0 items</div>
      </div>
    </div>
    <button class="cart-close" onclick="toggleCart()">
      <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="cart-items" id="cartItemsList"></div>
  <div class="cart-ftr" id="cartFtr" style="display:none;">
    <div class="cart-sum">
      <div class="cart-sum-row"><span>Subtotal</span><span id="cartSub">₱0.00</span></div>
      <div class="cart-sum-row"><span>VAT (12%)</span><span id="cartVat">₱0.00</span></div>
      <div class="cart-sum-row total"><span>Total</span><span id="cartTotal">₱0.00</span></div>
    </div>
    <button class="btn-proceed" onclick="proceedToOrder()">
      <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
      Proceed to Cashier
    </button>
  </div>
</div>

<div class="toast" id="toast">
  <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg"></span>
</div>

 <script>
    var CSRF        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var ROUTE_SEARCH     = "{{ route('kiosk.search') }}";
    var ROUTE_SUBMIT     = "{{ route('kiosk.submit') }}";
    var ROUTE_UPDATE     = "{{ route('kiosk.update') }}";
    var ROUTE_QUEUE_CALL = "{{ route('cashier.queue.call') }}";
    var products         = {!! \Illuminate\Support\Js::from($products) !!};
</script>
<script src="{{ asset('js/kiosk.js') }}"></script>
</body>
</html>