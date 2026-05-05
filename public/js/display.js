var currentNumber = null;
var lastCalledNumber = null;

function updateClock() {
  var now = new Date(), h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  var ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  document.getElementById('clockLbl').textContent =
    (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s+' '+ampm;
  document.getElementById('dateLbl').textContent =
    now.toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}
setInterval(updateClock, 1000); updateClock();

/* Fit the number to always occupy one line, as large as possible */
function fitNumber(el, text) {
  var wrap   = document.getElementById('nsWrap');
  var maxW   = wrap.offsetWidth  - 96;
  var maxH   = wrap.offsetHeight - 24;
  var size   = Math.min(maxH, 280);
  el.textContent  = text;
  el.style.fontSize = size + 'px';
  while (el.scrollWidth > maxW && size > 48) {
    size -= 4;
    el.style.fontSize = size + 'px';
  }
}

function poll() {
  fetch(ROUTE_POLL, { headers: { 'Accept': 'application/json' } })
    .then(function(r){ return r.json(); })
    .then(renderDisplay)
    .catch(function(e){ console.error('Poll error:', e); });
}

function renderDisplay(data) {
  var serving = data.now_serving;
  var name    = data.now_serving_name || '';
  var queue   = data.queue || [];

  var nsEl     = document.getElementById('nsNumber');
  var nsDot    = document.getElementById('nsDot');
  var nsStatus = document.getElementById('nsStatusText');
  var nsBadge  = document.getElementById('nsNameBadge');

  if (serving) {
    var changed = serving !== currentNumber;
    currentNumber = serving;

    nsEl.className = '';
    fitNumber(nsEl, serving);

   if (changed) {
      nsEl.classList.add('pop');
      setTimeout(function(){ nsEl.classList.remove('pop'); }, 500);

      // 🔊 Announce the new number
      if (lastCalledNumber !== serving) {
        lastCalledNumber = serving;
        // Small delay so the visual animation plays first
        setTimeout(function() {
          speak(serving, name);
        }, 300);
      }

      var ring = document.getElementById('burstRing');
      ring.className = 'burst-ring';
      void ring.offsetWidth;
      ring.className = 'burst-ring fire';

      var fo = document.getElementById('flashOverlay');
      fo.className = 'flash-overlay';
      void fo.offsetWidth;
      fo.className = 'flash-overlay fire';
    }

    nsDot.className      = 'ns-status-dot';
    nsStatus.textContent = 'Please proceed to the counter';
    nsBadge.className    = 'ns-name-badge';
    nsBadge.textContent  = name || 'Please proceed to the counter';

  } else {
    currentNumber            = null;
    nsEl.className           = 'idle-state';
    nsEl.textContent         = '— — —';
    nsEl.style.fontSize      = '';
    nsDot.className          = 'ns-status-dot idle';
    nsStatus.textContent     = 'No active queue';
    nsBadge.className        = 'ns-name-badge empty';
    nsBadge.textContent      = 'Waiting for next customer…';
  }

  var waitEl  = document.getElementById('waitingList');
  var countEl = document.getElementById('waitCount');
  countEl.textContent = queue.length + ' waiting';

  if (!queue.length) {
    waitEl.innerHTML =
      '<div class="wait-empty">' +
        '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" style="color:#C7D4E8">' +
          '<rect x="10" y="14" width="36" height="32" rx="7" stroke="currentColor" stroke-width="2"/>' +
          '<path d="M18 14v-4a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
          '<path d="M20 26h16M20 32h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
          '<circle cx="43" cy="43" r="9" fill="currentColor" fill-opacity=".15" stroke="currentColor" stroke-width="1.8"/>' +
          '<path d="M39.5 43l2.5 2.5L47 39" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>' +
        '<div class="wait-empty-lbl">All clear!</div>' +
        '<div class="wait-empty-sub">No one is waiting right now</div>' +
      '</div>';
    return;
  }

  waitEl.innerHTML = queue.map(function(item, i) {
    var isNext = i === 0;
    return '<div class="wait-item' + (isNext ? ' next-up' : '') + '">' +
      '<div class="wait-left">' +
        '<div class="wait-seq' + (isNext ? ' next' : '') + '">' + (i + 1) + '</div>' +
        '<div>' +
          '<div class="wait-num">' + escHtml(item.queue_number) + '</div>' +
          '<div class="wait-name">' + escHtml(item.customer_name || 'Customer') + '</div>' +
        '</div>' +
      '</div>' +
      (isNext
        ? '<div class="next-badge"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 6h8M6.5 2.5L10 6l-3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Next Up</div>'
        : '<div class="num-badge">#' + (i + 1) + '</div>') +
    '</div>';
  }).join('');
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.addEventListener('resize', function() {
  if (currentNumber) fitNumber(document.getElementById('nsNumber'), currentNumber);
});

poll();
setInterval(poll, 4000);
// ── VOICE CALLER ──────────────────────────────────────────────

function speak(queueNumber, customerName) {
  if (!window.speechSynthesis) return;

  window.speechSynthesis.cancel();

  // Split into separate utterances — sounds more natural with pauses
  var lines = [
    'Queue number ' + queueNumber + '.',
    customerName ? customerName + ',' : '',
    'please proceed to the counter.',
    'Thank you.',
  ].filter(Boolean);

  function doSpeak() {
    var voices = window.speechSynthesis.getVoices();

    // Priority list — female voices across Windows, Mac, Android
    var femaleKeywords = [
      'Zira',         // Windows — Microsoft Zira (US female, clear and pleasant)
      'Susan',        // Windows
      'Hazel',        // Windows UK female
      'Samantha',     // macOS/iOS — best natural female voice
      'Karen',        // macOS Australian female
      'Moira',        // macOS Irish female
      'Fiona',        // macOS Scottish female
      'Victoria',     // macOS female
      'Google UK English Female',  // Chrome/Android — sounds most "mall-like"
      'Google US English',         // Chrome fallback
      'female',       // generic fallback
    ];

    var chosen = null;
    for (var i = 0; i < femaleKeywords.length; i++) {
      chosen = voices.find(function(v) {
        return v.name.toLowerCase().indexOf(femaleKeywords[i].toLowerCase()) !== -1;
      });
      if (chosen) break;
    }

    // If still none found, try to avoid obviously male voices
    if (!chosen) {
      chosen = voices.find(function(v) {
        return (v.lang.startsWith('en')) &&
               v.name.toLowerCase().indexOf('male') === -1 &&
               v.name.toLowerCase().indexOf('david') === -1 &&
               v.name.toLowerCase().indexOf('mark') === -1 &&
               v.name.toLowerCase().indexOf('james') === -1 &&
               v.name.toLowerCase().indexOf('daniel') === -1;
      });
    }

    lines.forEach(function(line, index) {
      var utter = new SpeechSynthesisUtterance(line);
      if (chosen) utter.voice = chosen;
      utter.lang   = 'en-US';
      utter.rate   = 0.82;   // slightly slow — mall PA system pacing
      utter.pitch  = 1.15;   // slightly higher = more feminine, pleasant
      utter.volume = 1;
      window.speechSynthesis.speak(utter);
    });
  }

  var voices = window.speechSynthesis.getVoices();
  if (voices.length) {
    doSpeak();
  } else {
    window.speechSynthesis.onvoiceschanged = function() {
      window.speechSynthesis.onvoiceschanged = null;
      doSpeak();
    };
  }
}
function activateVoice() {
  window.speechSynthesis.cancel();
  // Pre-load voices
  window.speechSynthesis.getVoices();
  // Unlock with a silent utterance
  var u = new SpeechSynthesisUtterance(' ');
  u.volume = 0;
  window.speechSynthesis.speak(u);

  var btn = document.getElementById('voiceBtn');
  btn.textContent = '🔊 Voice Active';
  btn.style.background = '#dcfce7';
  btn.disabled = true;
}
