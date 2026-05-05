<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>MediCare — Queue Display</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/display.css') }}">
</head>
<body>

<div class="flash-overlay" id="flashOverlay"></div>

<div class="layout">

  <header class="header">
    <div class="brand">
      <div class="brand-logo">
        <svg width="26" height="26" viewBox="0 0 26 26" fill="none">
          <rect x="10" y="1" width="6" height="24" rx="2.5" fill="white"/>
          <rect x="1" y="10" width="24" height="6" rx="2.5" fill="white"/>
        </svg>
      </div>
      <div>
        <div class="brand-name">MediCare Pharmacy</div>
        <div class="brand-sub">Queue Management</div>
      </div>
    </div>

    <div class="header-center">
      <div class="live-pill">
        <div class="live-dot"></div>
        Live Display
      </div>
    </div>

    <div class="header-right">
      <div class="clock-val" id="clockLbl">00:00:00</div>
      <div class="date-val" id="dateLbl"></div>
    </div>
  </header>

  <div class="body">

    <div class="now-serving-card">
      <div class="ns-inner">
        <div class="ns-eyebrow">
          <div class="ns-eyebrow-line"></div>
          <div class="ns-eyebrow-text">Now Serving</div>
          <div class="ns-eyebrow-line"></div>
        </div>
        <div class="ns-number-wrap" id="nsWrap">
          <div class="ns-glow"></div>
          <div class="burst-ring" id="burstRing"></div>
          <div id="nsNumber" class="idle-state">— — —</div>
        </div>
        <div class="ns-name-badge empty" id="nsNameBadge">Waiting for next customer…</div>
        <div class="ns-status-row">
          <div class="ns-status-dot idle" id="nsDot"></div>
          <span id="nsStatusText">No active queue</span>
        </div>
      </div>
    </div>

    <div class="waiting-card">
      <div class="waiting-hdr">
        <div class="waiting-hdr-left">
          <div class="waiting-hdr-icon">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
              <rect x="3" y="3" width="16" height="16" rx="4" stroke="currentColor" stroke-width="1.8"/>
              <path d="M7 8h8M7 11.5h6M7 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <div class="waiting-title">Up Next</div>
            <div class="waiting-sub">Waiting for pickup</div>
          </div>
        </div>
        <div class="waiting-count-badge" id="waitCount">0 waiting</div>
      </div>
      <div class="waiting-list" id="waitingList">
        <div class="wait-empty">
          <svg width="56" height="56" viewBox="0 0 56 56" fill="none" style="color:#C7D4E8">
            <rect x="10" y="14" width="36" height="32" rx="7" stroke="currentColor" stroke-width="2"/>
            <path d="M18 14v-4a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M20 26h16M20 32h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <circle cx="43" cy="43" r="9" fill="currentColor" fill-opacity=".15" stroke="currentColor" stroke-width="1.8"/>
            <path d="M39.5 43l2.5 2.5L47 39" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="wait-empty-lbl">All clear!</div>
          <div class="wait-empty-sub">No one is waiting right now</div>
        </div>
      </div>
    </div>

  </div>

  <footer class="footer">
    <div class="footer-left">
  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" style="color:#7A8BAA;flex-shrink:0">
    <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.5"/>
    <path d="M9 6.5v4M9 12.5h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
  </svg>
  Please listen for your queue number to be called at the counter.
  &nbsp;·&nbsp; Senior Citizens and PWD enjoy a 20% discount — present your valid ID.
  &nbsp;·&nbsp;
  <button onclick="activateVoice()" id="voiceBtn"
    style="background:#f0fdf4;border:1.5px solid #6ee7b7;color:#059669;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;">
    🔊 Activate Voice
  </button>
</div>
    <div class="footer-right">
      <div class="footer-pulse-dot"></div>
      Updates every 4 seconds
    </div>
  </footer>

</div>

<div class="ticker-wrap">
  <div class="ticker-label">
    <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
      <circle cx="6.5" cy="6.5" r="5.5" stroke="white" stroke-width="1.5"/>
      <path d="M6.5 4.5V7M6.5 9h.01" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    Notice
  </div>
  <div class="ticker-scroll">
    <div class="ticker-text">
      Welcome to MediCare Pharmacy! &nbsp;·&nbsp;
      Please keep your receipt and queue number ready for pickup. &nbsp;·&nbsp;
      Senior Citizens and PWD customers enjoy a 20% discount — please present your valid ID at the counter. &nbsp;·&nbsp;
      Prescription medicines require a valid doctor's prescription. &nbsp;·&nbsp;
      For inquiries, please approach our staff at the counter. &nbsp;·&nbsp;
      Thank you for choosing MediCare Pharmacy — your health is our priority!
    </div>
  </div>
</div>
<script>
   var ROUTE_POLL = "{{ route('queue.display.poll') }}";
</script>
<script src="{{ asset('js/display.js') }}"></script>
</body>
</html>