<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>PhilWil Apartments — Welcome</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    /* ---------- Root themes & variables ---------- */
    :root{
      --transition: 480ms cubic-bezier(.2,.9,.3,1);
      --glass-radius:14px;
      --shadow-strong: 0 30px 80px rgba(2,8,20,0.6);
      --shadow-soft: 0 12px 30px rgba(2,8,20,0.35);
      --accent: #6A7CFF;
      --accent-2:#7FF7D1;
      --muted: rgba(0,0,0,0.45);

      /* Bright mode defaults */
      --bg: linear-gradient(180deg,#f6f9ff 0%, #e8f1ff 45%, #eef6ff 100%);
      --panel: rgba(255,255,255,0.9);
      --panel-border: rgba(15,20,40,0.06);
      --text-main: #071428;
      --text-sub: rgba(7,20,40,0.65);
      --glass-reflect: rgba(255,255,255,0.7);
      --btn-shadow: 0 10px 30px rgba(10,20,40,0.06);
      --logo-glow: rgba(106,124,255,0.12);
      --sky-filter: none;
    }

    /* Soft (not so bright) theme */
    :root[data-theme="soft"]{
      --bg: linear-gradient(180deg,#e9eef6 0%, #dbe8f3 45%, #dfeffd 100%);
      --panel: rgba(255,255,255,0.86);
      --panel-border: rgba(6,12,28,0.06);
      --text-main: #081428;
      --text-sub: rgba(8,20,40,0.56);
      --glass-reflect: rgba(255,255,255,0.5);
      --btn-shadow: 0 18px 50px rgba(8,16,32,0.12);
      --logo-glow: rgba(150,190,255,0.08);
    }

    /* Dark / Black (night) theme */
    :root[data-theme="dark"]{
      --bg: radial-gradient(800px 400px at 10% 10%, rgba(106,92,255,0.06), transparent 8%), linear-gradient(180deg,#021021 0%, #031227 55%, #00060b 100%);
      --panel: rgba(8,12,22,0.48);
      --panel-border: rgba(255,255,255,0.04);
      --text-main: #eaf5ff;
      --text-sub: rgba(230,240,255,0.6);
      --glass-reflect: rgba(255,255,255,0.03);
      --btn-shadow: 0 22px 70px rgba(0,0,0,0.7);
      --logo-glow: rgba(100,220,190,0.08);
      --sky-filter: blur(12px) saturate(140%);
    }

    /* ---------- Reset ---------- */
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{height:100%}
    body{
      font-family: Inter, Poppins, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--bg);
      color:var(--text-main);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      transition: background var(--transition), color var(--transition);
      overflow-x:hidden;
    }

    /* ---------- Container ---------- */
    .container{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px;
      position:relative;
    }

    /* cinematic backdrop */
    .backdrop{
      position:absolute; inset:0; z-index:0; pointer-events:none;
      display:block; overflow:hidden;
      transition:filter var(--transition);
      filter: var(--sky-filter);
    }

    /* subtle gradient beam */
    .backdrop .beam{
      position:absolute; left:8%; top:-10%;
      width:1200px; height:1200px; border-radius:50%;
      background: radial-gradient(circle at 20% 25%, rgba(255,255,255,0.06), transparent 24%),
                  radial-gradient(circle at 80% 70%, rgba(106,124,255,0.06), transparent 20%);
      transform:translateZ(-200px) scale(1);
      opacity:0.95;
      mix-blend-mode:screen;
      transition:opacity var(--transition);
    }

    /* skyline silhouette — cleaner than previous style for a different look */
    .backdrop svg{ width:140%; height:60vh; position:absolute; left:-20%; bottom:-6vh; opacity:0.95; transform-origin:center bottom;
      filter: drop-shadow(0 30px 50px rgba(2,6,20,0.35));
      transition: transform var(--transition);
    }

    /* sparkles (gentle) */
    .sparkles{
      position:absolute; inset:0; z-index:3; pointer-events:none; mix-blend-mode:screen;
    }
    .sparkles .p{
      position:absolute; width:6px; height:6px; border-radius:50%;
      background: radial-gradient(circle, rgba(255,255,255,0.95), rgba(255,255,255,0.2));
      opacity:0.08; transform:translateY(0);
      animation: sparkle 8s ease-in-out infinite;
    }
    @keyframes sparkle{
      0%{ transform:translateY(0) scale(0.9); opacity:0.06 }
      50%{ transform:translateY(-8px) scale(1.1); opacity:0.22 }
      100%{ transform:translateY(0) scale(0.95); opacity:0.06 }
    }

    /* ---------- Card (central) ---------- */
    .card{
      position:relative;
      z-index:8;
      width:min(1100px,96%);
      max-width:1200px;
      display:flex;
      gap:28px;
      align-items:center;
      justify-content:space-between;
      padding:28px;
      border-radius:20px;
      background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.78));
      border: 1px solid var(--panel-border);
      box-shadow: var(--shadow-strong);
      backdrop-filter: blur(10px) saturate(130%);
      transition: background var(--transition), box-shadow var(--transition), transform var(--transition), border-color var(--transition);
      overflow:visible;
    }

    /* adapt panel in dark */
    :root[data-theme="dark"] .card{
      background: linear-gradient(180deg, rgba(12,18,30,0.48), rgba(8,12,20,0.44));
      box-shadow: var(--shadow-soft);
      border:1px solid rgba(255,255,255,0.035);
      backdrop-filter: blur(14px) saturate(150%);
    }

    /* left brand area */
    .brand{
      width:60%;
      min-width:300px;
      display:flex;
      gap:18px;
      flex-direction:column;
    }
    .brand .brand-top{
      display:flex; gap:14px; align-items:center;
    }
    .logo{
      width:86px; height:86px; border-radius:16px;
      display:grid; place-items:center;
      background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(255,255,255,0.86));
      border:1px solid var(--panel-border);
      box-shadow: 0 20px 40px rgba(12,18,35,0.08), inset 0 -6px 16px rgba(255,255,255,0.06);
      transition: transform var(--transition), box-shadow var(--transition);
    }
    :root[data-theme="dark"] .logo{
      background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
      box-shadow: 0 12px 30px rgba(2,6,18,0.6), inset 0 -6px 10px rgba(255,255,255,0.02);
    }
    .logo svg{ width:54px; height:54px; display:block; transition: transform var(--transition) }
    .brand h1{
      font-family: Poppins, Inter, system-ui;
      font-weight:700;
      font-size: clamp(24px, 3.6vw, 40px);
      color:var(--text-main);
      line-height:1.02;
      margin:0;
      letter-spacing:-0.01em;
      text-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }
    .brand p{
      margin-top:8px;
      color:var(--text-sub);
      max-width:66ch;
      font-size:15px;
      line-height:1.6;
    }

    /* right area: login CTA column */
    .ctas{
      width:36%;
      min-width:240px;
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:16px;
      flex-direction:column;
    }

    .btn{
      width:100%;
      display:inline-flex;
      gap:10px;
      align-items:center;
      justify-content:center;
      padding:14px 16px;
      border-radius:12px;
      font-weight:700;
      font-size:15px;
      color:var(--text-main);
      text-decoration:none;
      border:1px solid var(--panel-border);
      box-shadow: var(--btn-shadow);
      transition: transform var(--transition), box-shadow var(--transition), background var(--transition), border-color var(--transition);
      position:relative;
      overflow:hidden;
      backdrop-filter: blur(6px) saturate(120%);
    }

    .btn:before{
      content:"";
      position:absolute; inset:0; border-radius:inherit;
      background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
      mix-blend-mode: overlay;
      pointer-events:none;
    }

    .btn:hover{ transform:translateY(-8px) scale(1.01) }

    .btn.admin{
      background: linear-gradient(180deg, rgba(18,18,18,0.95), rgba(36,36,36,0.9));
      color:white;
      border:1px solid rgba(255,255,255,0.06);
    }
    .btn.finance{
      background: linear-gradient(180deg, rgba(6,48,28,0.92), rgba(6,36,24,0.88));
      color:#eafff6;
      border:1px solid rgba(10,200,150,0.06);
    }
    .btn.secretary{
      background: linear-gradient(180deg, rgba(6,28,48,0.9), rgba(6,20,40,0.88));
      color:#e8f6ff;
      border:1px solid rgba(100,170,255,0.06);
    }

    .small-note{ font-size:13px; color:var(--text-sub); margin-top:6px; text-align:right; width:100% }

    /* theme toggle control */
    .theme-toggle{
      position:absolute;
      right:28px; top:28px; z-index:12;
      display:flex; gap:8px; align-items:center;
      background:var(--panel);
      border-radius:999px; padding:8px;
      border:1px solid var(--panel-border);
      box-shadow: 0 10px 30px rgba(10,14,28,0.06);
      backdrop-filter: blur(8px) saturate(140%);
      transition: transform var(--transition), opacity var(--transition);
    }
    .theme-toggle button{
      background:transparent; border:0; padding:8px 12px; border-radius:999px; cursor:pointer;
      font-weight:600; color:var(--text-sub); font-size:13px;
      transition: background var(--transition), color var(--transition), transform var(--transition);
    }
    .theme-toggle button.active{
      background: linear-gradient(90deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
      color:var(--text-main);
      transform:translateY(-4px);
      box-shadow: 0 8px 28px rgba(10,14,30,0.08);
    }

    /* responsive */
    @media (max-width:980px){
      .card{ flex-direction:column; align-items:center; padding:18px; gap:18px }
      .brand{ width:100%; text-align:center; align-items:center }
      .ctas{ width:100%; justify-content:center; }
      .theme-toggle{ right:12px; top:12px; }
      .backdrop svg{ width:220%; left:-60% }
    }
    @media (max-width:480px){
      .logo{ width:64px;height:64px;border-radius:12px }
      .brand h1{ font-size:20px }
      .brand p{ font-size:14px }
      .btn{ padding:12px 14px; font-size:14px; border-radius:10px }
      .theme-toggle{ padding:6px; }
    }

    /* reduced motion */
    @media (prefers-reduced-motion: reduce){
      *{ transition:none !important; animation:none !important; }
    }

  </style>
</head>
<body>
  <div class="container" id="app">

    <!-- Theme toggle (top-right) -->
    <div class="theme-toggle" aria-hidden="false" role="toolbar" aria-label="Theme controls">
      <button id="btn-bright" data-theme="bright" title="Bright mode (day)">Bright</button>
      <button id="btn-soft" data-theme="soft" title="Soft / not so bright">Soft</button>
      <button id="btn-dark" data-theme="dark" title="Black / Night mode">Night</button>
    </div>

    <!-- Cinematic backdrop -->
    <div class="backdrop" aria-hidden="true">
      <div class="beam" aria-hidden="true"></div>

      <!-- skyline vector (simplified elegant silhouette) -->
      <svg aria-hidden="true" viewBox="0 0 1600 420" preserveAspectRatio="xMidYMax slice">
        <defs>
          <linearGradient id="skyG" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.06"/>
            <stop offset="60%" stop-color="#000000" stop-opacity="0.02"/>
          </linearGradient>
        </defs>

        <!-- large silhouette -->
        <g transform="translate(0,40)">
          <rect x="0" y="260" width="1600" height="200" fill="url(#skyG)"></rect>
          <g fill="rgba(10,20,35,0.95)" opacity="0.95">
            <rect x="40" y="140" width="80" height="320" rx="8"></rect>
            <rect x="140" y="100" width="120" height="360" rx="10"></rect>
            <rect x="300" y="40" width="200" height="440" rx="12"></rect>
            <rect x="540" y="80" width="110" height="400" rx="8"></rect>
            <rect x="680" y="20" width="180" height="460" rx="12"></rect>
            <rect x="880" y="120" width="120" height="360" rx="8"></rect>
            <rect x="1040" y="60" width="140" height="420" rx="10"></rect>
            <rect x="1220" y="90" width="90" height="390" rx="7"></rect>
            <rect x="1360" y="140" width="70" height="320" rx="8"></rect>
          </g>
        </g>
      </svg>

      <!-- gentle sparkles placed around -->
      <div class="sparkles" aria-hidden="true">
        <div class="p" style="left:10%; top:18%; animation-delay:0s"></div>
        <div class="p" style="left:24%; top:14%; animation-delay:2s"></div>
        <div class="p" style="left:42%; top:10%; animation-delay:1s"></div>
        <div class="p" style="left:68%; top:16%; animation-delay:3s"></div>
        <div class="p" style="left:86%; top:12%; animation-delay:4s"></div>
      </div>
    </div>

    <!-- Main card -->
    <section class="card" role="main" aria-labelledby="brand-title">

      <div class="brand">
        <div class="brand-top">
          <div class="logo" aria-hidden="true">
            <!-- new emblem -->
            <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="lgA" x1="0" x2="1">
                  <stop offset="0%" stop-color="#8bd9ff"/>
                  <stop offset="100%" stop-color="#6a7cff"/>
                </linearGradient>
              </defs>
              <rect x="8" y="14" width="16" height="36" rx="3" fill="url(#lgA)"></rect>
              <rect x="26" y="8" width="12" height="42" rx="3" fill="url(#lgA)"></rect>
              <rect x="40" y="20" width="12" height="30" rx="3" fill="url(#lgA)"></rect>
            </svg>
          </div>

          <div>
            <div style="font-size:12px; font-weight:700; color:var(--text-sub); letter-spacing:0.12em; text-transform:uppercase;">PHILWIL</div>
            <div style="font-size:12px; color:var(--text-sub); margin-top:4px">Apartments • Management Suite</div>
          </div>
        </div>

        <h1 id="brand-title">PhilWil Apartments — A premium property portal</h1>

        <p>Presenting a brand-forward welcome for your property management system: cinematic depth, clean editorial typography, and three themes so the presentation matches the time of day or your meeting mood. Use the toggle to switch themes or let it auto-switch at 7:00 PM to night mode.</p>

        <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap">
          <span style="padding:8px 12px; border-radius:999px; background:linear-gradient(180deg, rgba(0,0,0,0.04), rgba(0,0,0,0.02)); font-weight:600; color:var(--text-sub)">Cinematic</span>
          <span style="padding:8px 12px; border-radius:999px; background:linear-gradient(180deg, rgba(0,0,0,0.04), rgba(0,0,0,0.02)); font-weight:600; color:var(--text-sub)">Glass UI</span>
          <span style="padding:8px 12px; border-radius:999px; background:linear-gradient(180deg, rgba(0,0,0,0.04), rgba(0,0,0,0.02)); font-weight:600; color:var(--text-sub)">Parallax</span>
        </div>
      </div>

      <aside class="ctas" aria-label="Login actions">
        <a class="btn admin" href="{{ route('admin.login') }}" role="button" aria-label="Admin Login">Admin Login</a>
        <!-- <a class="btn finance" href="{{ route('finance.login') }}" role="button" aria-label="Finance Login">Finance Login</a> -->
        <a class="btn secretary" href="{{ route('secretary.login') }}" role="button" aria-label="Secretary Login">Secretary Login</a>

        <div class="small-note">© {{ date('Y') }} PhilWil Apartments — elegant & secure</div>
      </aside>

    </section>
  </div>

  <!-- Minimal JS: theme switching + auto-night at 19:00 + subtle parallax -->
  <script>
    (function(){
      const root = document.documentElement;
      const btnBright = document.getElementById('btn-bright');
      const btnSoft = document.getElementById('btn-soft');
      const btnDark = document.getElementById('btn-dark');
      const themeButtons = [btnBright, btnSoft, btnDark];

      // apply theme to root attribute data-theme
      function applyTheme(name){
        if(!name) name = 'bright';
        root.setAttribute('data-theme', name);
        // update active states
        themeButtons.forEach(b => b.classList.toggle('active', b.dataset.theme === name));
        // small visual tweak for backdrop on dark
        document.querySelector('.backdrop').style.opacity = name === 'dark' ? '0.9' : '1';
      }

      // cycle function (if you want to cycle)
      function nextTheme(){
        const order = ['bright','soft','dark'];
        const cur = root.getAttribute('data-theme') || 'bright';
        const next = order[(order.indexOf(cur)+1)%order.length];
        applyTheme(next);
      }

      // attach click
      btnBright.addEventListener('click', ()=> applyTheme('bright'));
      btnSoft.addEventListener('click', ()=> applyTheme('soft'));
      btnDark.addEventListener('click', ()=> applyTheme('dark'));

      // Auto day/night logic: switch to night at 19:00 local and back to bright at 06:00.
      // If between 7:00 PM and 5:59 AM -> dark. Between 6AM and 8:59AM => soft. Between 9AM and 6:59PM => bright.
      function autoThemeByTime(){
        const d = new Date();
        const h = d.getHours(); // local time
        let theme = 'bright';
        if(h >= 19 || h < 6){
          theme = 'dark';
        } else if(h >= 6 && h < 9){
          theme = 'soft';
        } else {
          theme = 'bright';
        }
        applyTheme(theme);
      }

      // Run on load
      autoThemeByTime();

      // Re-check every minute in case page open crosses threshold
      setInterval(autoThemeByTime, 60 * 1000);

      // subtle parallax for card on pointer move
      const container = document.getElementById('app');
      container.addEventListener('mousemove', (e)=> {
        const w = window.innerWidth, h = window.innerHeight;
        const nx = (e.clientX - w/2) / (w/2);
        const ny = (e.clientY - h/2) / (h/2);
        const card = document.querySelector('.card');
        card.style.transform = `translate3d(${nx*8}px, ${ny*6}px, 0) rotateX(${ny*1.2}deg) rotateY(${nx*1.2}deg)`;
        // move backdrop slightly
        const svg = document.querySelector('.backdrop svg');
        if(svg) svg.style.transform = `translate3d(${nx*12}px, ${Math.abs(ny)*-6}px, 0) scale(1.02)`;
      });
      ['mouseleave','mouseout','blur'].forEach(ev => {
        container.addEventListener(ev, ()=> {
          const card = document.querySelector('.card');
          if(card) card.style.transform = '';
          const svg = document.querySelector('.backdrop svg');
          if(svg) svg.style.transform = '';
        });
      });

      // keyboard shortcuts: T to cycle themes
      window.addEventListener('keydown', (e)=>{
        if(e.key.toLowerCase() === 't'){ nextTheme(); }
      });

      // Respect reduced-motion
      const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
      if(mq.matches){
        document.querySelectorAll('*').forEach(el=>el.style.transitionDuration = '0ms');
      }

    })();
  </script>
</body>
</html>
