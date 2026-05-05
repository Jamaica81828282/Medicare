<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>MediCare Pharmacy — Log In</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --navy: #0c2240;
  --navy-d: #081830;
  --blue: #1a5fa8;
  --blue-m: #2878cc;
  --blue-l: #d4e8f7;
  --teal: #0e7a7a;
  --white: #f8fbff;
  --text: #0d1f35;
  --muted: #4f6e8a;
  --border: #c2d8ee;
  --error: #c0392b;
  --error-bg: #fdf0ef;
}
html, body {
  height: 100%;
  font-family: 'Sora', sans-serif;
  background: var(--navy-d);
  color: var(--text);
  overflow: hidden;
}
.bg {
  position: fixed; inset: 0;
  background: linear-gradient(155deg, #0e2d58 0%, #0c2240 40%, #091c36 100%);
  z-index: 0;
}
.bg::before {
  content: ''; position: absolute; inset: 0;
  background-image: radial-gradient(rgba(255,255,255,.045) 1px, transparent 1px);
  background-size: 28px 28px; pointer-events: none;
}
.hb { position: absolute; border-radius: 50%; pointer-events: none; animation: blobD 20s ease-in-out infinite alternate; }
.hb1 { width:620px; height:620px; background:rgba(26,95,168,.18); top:-200px; left:-180px; }
.hb2 { width:440px; height:440px; background:rgba(14,122,122,.14); bottom:-140px; right:-120px; animation-delay:-10s; }
.hb3 { width:300px; height:300px; background:rgba(40,120,204,.12); top:50%; left:60%; animation-delay:-6s; }
.hb4 { width:260px; height:260px; background:rgba(14,122,122,.1); top:10%; right:20%; animation-delay:-14s; }
@keyframes blobD { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(20px,15px) scale(1.08)} }
.hero-ring { position:absolute; border-radius:50%; pointer-events:none; border:1.5px solid rgba(255,255,255,.06); }
.hr1 { width:600px; height:600px; right:-180px; bottom:-220px; }
.hr2 { width:380px; height:380px; left:-100px; top:-100px; border-color:rgba(255,255,255,.04); }
.hr3 { width:200px; height:200px; right:15%; top:10%; border-color:rgba(255,255,255,.05); }
.pill-bg { position:absolute; inset:0; overflow:hidden; pointer-events:none; z-index:1; }
.fp { position:absolute; opacity:0; animation:fpRise linear infinite; }
@keyframes fpRise {
  0%   { opacity:0; transform:translateY(100%) rotate(0deg); }
  10%  { opacity:.35; }
  90%  { opacity:.25; }
  100% { opacity:0; transform:translateY(-110%) rotate(280deg); }
}
.page {
  position: relative; z-index: 2;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  min-height: 100vh; padding: 24px;
}
.brand-header { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
.brand-icon-box {
  width: 44px; height: 44px;
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
  border-radius: 13px; display:flex; align-items:center; justify-content:center;
}
.brand-icon-box svg { width:24px; height:24px; fill:white; }
.brand-txt-name { font-family:'DM Serif Display',serif; font-size:22px; color:white; letter-spacing:-.4px; line-height:1; }
.brand-txt-sub { font-size:9.5px; color:rgba(255,255,255,.4); letter-spacing:.14em; text-transform:uppercase; margin-top:3px; }
.login-card {
  width: 100%; max-width: 440px;
  background: rgba(255,255,255,0.97);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 28px;
  padding: 40px 44px 36px;
  box-shadow: 0 32px 80px rgba(8,24,48,.38), 0 8px 24px rgba(8,24,48,.22), 0 0 0 1px rgba(255,255,255,.12);
  position: relative; overflow: hidden;
}
.login-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  background: linear-gradient(90deg, var(--blue), var(--teal));
  border-radius: 28px 28px 0 0;
}
.bear-wrap { display:flex; justify-content:center; margin-bottom:12px; height:90px; }
.bear-svg { width:90px; height:90px; overflow:visible; }
.eye-l,.eye-r { transition:opacity .2s,transform .3s cubic-bezier(.34,1.56,.64,1); }
.blush-l,.blush-r { transition:opacity .3s; opacity:0; }
.hand-l { transition:transform .45s cubic-bezier(.34,1.56,.64,1); }
.hand-r { transition:transform .45s cubic-bezier(.34,1.56,.64,1); }
.bear-svg.peek .hand-l { transform:translate(14px,-30px); }
.bear-svg.peek .hand-r { transform:translate(-14px,-30px); }
.bear-svg.peek .blush-l,.bear-svg.peek .blush-r { opacity:1; }
.login-title { font-family:'DM Serif Display',serif; font-size:22px; color:var(--navy); text-align:center; margin-bottom:3px; }
.login-sub { font-size:10.5px; color:var(--muted); text-align:center; letter-spacing:.09em; text-transform:uppercase; margin-bottom:24px; }
.divider { width:40px; height:2px; background:linear-gradient(90deg,var(--blue),var(--teal)); border-radius:2px; margin:0 auto 22px; }
.alert { padding:11px 14px; border-radius:10px; font-size:13px; margin-bottom:14px; width:100%; }
.alert.error { background:var(--error-bg); color:var(--error); border:1px solid #f5c6c2; }
.alert.success { background:#ebf7f1; color:#1a6e3a; border:1px solid #aadec0; }
.fields { width:100%; }
.field { margin-bottom:15px; }
.field label { display:block; font-size:10px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
.input-wrap { position:relative; }
.i-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); pointer-events:none; color:#9ab8cc; }
.i-icon svg { width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; display:block; }
.input-wrap input {
  width:100%; height:46px;
  background:#f0f6fc; border:1.5px solid var(--border); border-radius:12px;
  padding:0 46px 0 40px;
  font-family:'Sora',sans-serif; font-size:13.5px; color:var(--text);
  outline:none; transition:border-color .2s,background .2s,box-shadow .2s;
}
.input-wrap input:focus { border-color:var(--blue); background:#fff; box-shadow:0 0 0 3px rgba(40,120,204,.1); }
.input-wrap input::placeholder { color:#b0c8dc; }
.eye-btn { position:absolute; right:0; top:0; width:44px; height:46px; background:none; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--muted); transition:color .15s; }
.eye-btn:hover { color:var(--blue); }
.eye-btn svg { width:15px; height:15px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.row { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; width:100%; }
.check-label { display:flex; align-items:center; gap:7px; font-size:11.5px; color:var(--muted); cursor:pointer; }
.check-label input[type=checkbox] { accent-color:var(--blue); width:13px; height:13px; cursor:pointer; }
.forgot { font-size:11.5px; color:var(--blue); font-weight:600; text-decoration:none; }
.forgot:hover { text-decoration:underline; }
.btn-login {
  width:100%; height:48px;
  background:linear-gradient(135deg, #1a5fa8 0%, #0e3d7a 100%);
  color:white; border:none; border-radius:12px;
  font-family:'Sora',sans-serif; font-size:14.5px; font-weight:600;
  cursor:pointer; letter-spacing:.03em; transition:all .18s;
  box-shadow:0 4px 18px rgba(26,95,168,.3), 0 1px 0 rgba(255,255,255,.1) inset;
  display:flex; align-items:center; justify-content:center; gap:9px;
}
.btn-login:hover { background:linear-gradient(135deg, #2878cc 0%, #1a5fa8 100%); box-shadow:0 6px 24px rgba(26,95,168,.42); transform:translateY(-1px); }
.btn-login:active { transform:scale(.985); }
.btn-login:disabled { background:#9ab8d4; cursor:not-allowed; box-shadow:none; transform:none; }
.card-footer { margin-top:20px; display:flex; align-items:center; justify-content:center; gap:8px; font-size:10.5px; color:#9ab8cc; }
.rx-pill { background:var(--navy); color:white; font-size:9px; font-weight:700; padding:2px 7px; border-radius:4px; letter-spacing:.06em; }
.tagline { margin-top: 20px; font-size: 12px; color: rgba(255,255,255,.3); letter-spacing: .04em; text-align: center; }
.tagline em { color: rgba(255,255,255,.5); font-style: normal; }
.spinner { width:18px; height:18px; border:2.5px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
@media (max-width:520px) {
  .login-card { padding: 32px 24px 28px; border-radius: 20px; }
  html,body { overflow-y:auto; }
}
</style>
</head>
<body>

<div class="bg">
  <div class="hb hb1"></div><div class="hb hb2"></div>
  <div class="hb hb3"></div><div class="hb hb4"></div>
  <div class="hero-ring hr1"></div><div class="hero-ring hr2"></div><div class="hero-ring hr3"></div>
  <div class="pill-bg" id="pillBg"></div>
</div>

<div class="page">

  <div class="brand-header">
    <div class="brand-icon-box">
      <svg viewBox="0 0 24 24"><path d="M12 2a1 1 0 011 1v7h7a1 1 0 010 2h-7v7a1 1 0 01-2 0v-7H4a1 1 0 010-2h7V3a1 1 0 011-1z"/></svg>
    </div>
    <div>
      <div class="brand-txt-name">MediCare</div>
      <div class="brand-txt-sub">Pharmacy System</div>
    </div>
  </div>

  <div class="login-card">

    <div class="bear-wrap">
      <svg class="bear-svg" id="bear" viewBox="0 0 110 120" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="22" cy="30" rx="13" ry="13" fill="#1a5fa8"/>
        <ellipse cx="22" cy="30" rx="7.5" ry="7.5" fill="#3478c8"/>
        <ellipse cx="88" cy="30" rx="13" ry="13" fill="#1a5fa8"/>
        <ellipse cx="88" cy="30" rx="7.5" ry="7.5" fill="#3478c8"/>
        <ellipse cx="55" cy="58" rx="36" ry="34" fill="#2878cc"/>
        <ellipse cx="55" cy="45" rx="20" ry="9" fill="rgba(255,255,255,.09)"/>
        <ellipse cx="55" cy="66" rx="22" ry="17" fill="#3d8de0"/>
        <path d="M28 72 Q20 90 25 110 L85 110 Q90 90 82 72 Q68 80 55 80 Q42 80 28 72z" fill="white" opacity=".93"/>
        <path d="M55 80 L55 110" stroke="rgba(26,95,168,.18)" stroke-width="1" stroke-dasharray="3 3"/>
        <rect x="42" y="85" width="14" height="10" rx="2" fill="rgba(26,95,168,.2)"/>
        <line x1="49" y1="88" x2="49" y2="92" stroke="rgba(26,95,168,.6)" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="47" y1="90" x2="51" y2="90" stroke="rgba(26,95,168,.6)" stroke-width="1.5" stroke-linecap="round"/>
        <g class="eye-l" id="eyeL">
          <ellipse cx="43" cy="55" rx="5.5" ry="6" fill="white"/>
          <ellipse class="pupil-l" cx="44" cy="56" rx="3" ry="3.5" fill="#0d1f35"/>
          <ellipse cx="45.5" cy="54" rx="1.2" ry="1.2" fill="white" opacity=".75"/>
        </g>
        <g class="eye-r" id="eyeR">
          <ellipse cx="67" cy="55" rx="5.5" ry="6" fill="white"/>
          <ellipse class="pupil-r" cx="68" cy="56" rx="3" ry="3.5" fill="#0d1f35"/>
          <ellipse cx="69.5" cy="54" rx="1.2" ry="1.2" fill="white" opacity=".75"/>
        </g>
        <ellipse cx="55" cy="66" rx="5" ry="3.5" fill="#1249a0"/>
        <ellipse cx="54" cy="65" rx="1.5" ry="1" fill="rgba(255,255,255,.3)"/>
        <path id="mouth" d="M49 72 Q55 78 61 72" fill="none" stroke="#1249a0" stroke-width="2" stroke-linecap="round"/>
        <ellipse class="blush-l" cx="34" cy="65" rx="7.5" ry="4.5" fill="#7aadec" opacity=".5"/>
        <ellipse class="blush-r" cx="76" cy="65" rx="7.5" ry="4.5" fill="#7aadec" opacity=".5"/>
        <g>
          <path d="M38 80 Q30 85 30 95 Q30 104 38 104 Q46 104 46 95" fill="none" stroke="#1249a0" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="46" cy="95" r="5" fill="none" stroke="#1249a0" stroke-width="2.5"/>
          <circle cx="46" cy="95" r="2" fill="#1249a0"/>
          <path d="M38 80 Q44 76 50 78 Q54 79 55 80" fill="none" stroke="#1249a0" stroke-width="2" stroke-linecap="round"/>
          <circle cx="52" cy="79" r="2.5" fill="#1a5fa8"/>
          <circle cx="57" cy="79" r="2.5" fill="#1a5fa8"/>
        </g>
        <g class="hand-l" id="handL">
          <ellipse cx="33" cy="86" rx="11" ry="9" fill="#1a5fa8"/>
          <ellipse cx="24" cy="80" rx="5" ry="5" fill="#1a5fa8"/>
          <ellipse cx="31" cy="76" rx="5" ry="5" fill="#1a5fa8"/>
          <ellipse cx="38" cy="76" rx="5" ry="5" fill="#1a5fa8"/>
          <ellipse cx="44" cy="80" rx="4.5" ry="4.5" fill="#1a5fa8"/>
        </g>
        <g class="hand-r" id="handR">
          <ellipse cx="77" cy="86" rx="11" ry="9" fill="#1a5fa8"/>
          <ellipse cx="66" cy="80" rx="4.5" ry="4.5" fill="#1a5fa8"/>
          <ellipse cx="72" cy="76" rx="5" ry="5" fill="#1a5fa8"/>
          <ellipse cx="79" cy="76" rx="5" ry="5" fill="#1a5fa8"/>
          <ellipse cx="86" cy="80" rx="5" ry="5" fill="#1a5fa8"/>
        </g>
      </svg>
    </div>

    <div class="login-title">Welcome back</div>
    <p class="login-sub">Pharmacy Management System</p>
    <div class="divider"></div>

    {{-- Session error from Laravel --}}
    @if(session('error'))
      <div class="alert error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
      <div class="alert error">{{ $errors->first() }}</div>
    @endif

    {{-- The form now posts to Laravel --}}
    <form method="POST" action="{{ route('login') }}" id="loginForm">
      @csrf

      <div class="fields">
        <div class="field">
          <label>Email Address</label>
          <div class="input-wrap">
            <span class="i-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            <input type="email" name="email" id="emailInput" placeholder="Enter your email" value="{{ old('email') }}" autocomplete="email" required/>
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-wrap">
            <span class="i-icon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
            <input type="password" name="password" id="pwInput" placeholder="Enter your password" autocomplete="current-password" required/>
            <button class="eye-btn" id="eyeToggle" type="button" aria-label="Toggle visibility">
              <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="row">
        <label class="check-label">
          <input type="checkbox" name="remember" id="rememberMe"/> Keep me signed in
        </label>
        <a href="#" class="forgot">Forgot password?</a>
      </div>

      <button class="btn-login" id="loginBtn" type="submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
          <polyline points="10 17 15 12 10 7"/>
          <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Log In
      </button>

    </form>

    <div class="card-footer">
      <span class="rx-pill">Rx</span>
      <span>Secure portal — authorized personnel only</span>
    </div>
  </div>

  <div class="tagline">Your trusted dose of care, <em>delivered.</em></div>

</div>

<script>
(function(){
  var bg=document.getElementById('pillBg');
  var mk=[
    function(c1,c2,s){return '<svg width="'+(s*2.2)+'" height="'+s+'" viewBox="0 0 '+(s*2.2)+' '+s+'"><rect width="'+(s*1.1)+'" height="'+s+'" rx="'+(s/2)+'" fill="'+c1+'"/><rect x="'+(s*1.1)+'" width="'+(s*1.1)+'" height="'+s+'" rx="'+(s/2)+'" fill="'+c2+'"/></svg>';},
    function(c1,c2,s){return '<svg width="'+s+'" height="'+s+'" viewBox="0 0 '+s+' '+s+'"><circle cx="'+(s/2)+'" cy="'+(s/2)+'" r="'+(s/2-1)+'" fill="'+c1+'"/><line x1="'+(s/2)+'" y1="4" x2="'+(s/2)+'" y2="'+(s-4)+'" stroke="'+c2+'" stroke-width="1.5"/></svg>';},
    function(c1,c2,s){var w=s*.5,h=s;return '<svg width="'+w+'" height="'+h+'" viewBox="0 0 '+w+' '+h+'"><rect x="'+(w*.15)+'" y="0" width="'+(w*.7)+'" height="'+(h*.12)+'" rx="'+(h*.06)+'" fill="'+c2+'"/><rect x="0" y="'+(h*.12)+'" width="'+w+'" height="'+(h*.88)+'" rx="'+(w*.15)+'" fill="'+c1+'"/></svg>';}
  ];
  var cols=[['rgba(255,255,255,.28)','rgba(255,255,255,.13)'],['rgba(40,120,204,.42)','rgba(255,255,255,.2)'],['rgba(14,122,122,.38)','rgba(255,255,255,.16)'],['rgba(255,255,255,.16)','rgba(40,120,204,.26)']];
  for(var i=0;i<24;i++){
    var el=document.createElement('div');el.className='fp';
    var s=10+Math.random()*22;
    var c=cols[Math.floor(Math.random()*cols.length)];
    var f=mk[Math.floor(Math.random()*mk.length)];
    el.style.cssText='left:'+(Math.random()*100)+'%;bottom:0;animation-duration:'+(14+Math.random()*18)+'s;animation-delay:'+(Math.random()*24)+'s;';
    el.innerHTML=f(c[0],c[1],s);
    bg.appendChild(el);
  }
})();

var bear=document.getElementById('bear'),eyeL=document.getElementById('eyeL'),eyeR=document.getElementById('eyeR'),mouth=document.getElementById('mouth');
var emailInput=document.getElementById('emailInput'),pwInput=document.getElementById('pwInput'),eyeToggle=document.getElementById('eyeToggle'),eyeIcon=document.getElementById('eyeIcon');
var pwVisible=false,isPeeking=false;
emailInput.addEventListener('focus',function(){setLooking(true);});
emailInput.addEventListener('blur',function(){setNeutral();});
emailInput.addEventListener('input',function(){var px=44+Math.min(this.value.length*.35,5);movePupils(px,56,px+24,56);});
pwInput.addEventListener('focus',function(){if(!pwVisible)setPeek(true);});
pwInput.addEventListener('blur',function(){if(!pwVisible)setPeek(false);setNeutral();});
eyeToggle.addEventListener('click',function(){
  pwVisible=!pwVisible;pwInput.type=pwVisible?'text':'password';
  eyeIcon.innerHTML=pwVisible?'<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>':'<path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>';
  if(pwVisible)setPeek(false);else if(document.activeElement===pwInput)setPeek(true);
});
function setPeek(on){isPeeking=on;if(on){bear.classList.add('peek');eyeL.style.opacity='0';eyeR.style.opacity='0';mouth.setAttribute('d','M49 73 Q55 70 61 73');}else{bear.classList.remove('peek');eyeL.style.opacity='1';eyeR.style.opacity='1';mouth.setAttribute('d','M49 72 Q55 78 61 72');}}
function setLooking(d){if(isPeeking)return;if(d){eyeL.style.transform='translateY(2px)';eyeR.style.transform='translateY(2px)';mouth.setAttribute('d','M49 72 Q55 79 61 72');}else{eyeL.style.transform='';eyeR.style.transform='';}}
function setNeutral(){if(isPeeking)return;eyeL.style.transform='';eyeR.style.transform='';movePupils(44,56,68,56);mouth.setAttribute('d','M49 72 Q55 78 61 72');}
function movePupils(lx,ly,rx,ry){var pl=document.querySelector('.pupil-l'),pr=document.querySelector('.pupil-r');if(pl){pl.setAttribute('cx',lx);pl.setAttribute('cy',ly);}if(pr){pr.setAttribute('cx',rx);pr.setAttribute('cy',ry);}}
setInterval(function(){if(isPeeking)return;var tl=eyeL.style.transform||'',tr=eyeR.style.transform||'';eyeL.style.transform=tl+' scaleY(0.08)';eyeR.style.transform=tr+' scaleY(0.08)';setTimeout(function(){if(!isPeeking){eyeL.style.transform=tl;eyeR.style.transform=tr;}},110);},3600);

// Show spinner on submit
document.getElementById('loginForm').addEventListener('submit', function(){
  var btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div>';
});
</script>
</body>
</html>