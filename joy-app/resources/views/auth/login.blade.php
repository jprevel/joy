<!DOCTYPE html>
<html lang="en" x-data="loginUI()" x-init="init()">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Joy - Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    :root {
      --bg-blur: 40px;
      --card-bg: rgba(255, 255, 255, 0.06);
      --card-stroke: rgba(255, 255, 255, 0.12);
      --txt: #ffffff;
    }
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
      color: var(--txt);
      background: #000;
      overflow: hidden;
    }

    /* === Animated background layer === */
    .bg-wrap { position: fixed; inset: 0; z-index: 0; }
    .bg-gradient, .bg-hue { position: absolute; inset: 0; }
    /* base animated gradient using conic + radial for depth */
    .bg-gradient {
      background:
        radial-gradient(1200px 800px at 20% 20%, #ffffff0a, transparent 60%),
        conic-gradient(from 0turn at 50% 50%, #0ea5e9, #a855f7, #f43f5e, #f59e0b, #10b981, #0ea5e9);
      animation: spin 24s linear infinite;
      filter: saturate(1.2);
    }
    .bg-hue { backdrop-filter: hue-rotate(0deg) blur(var(--bg-blur)); animation: hue 18s linear infinite; }

    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes hue { to { backdrop-filter: hue-rotate(360deg) blur(var(--bg-blur)); } }

    /* subtle vignette */
    .vignette { position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background: radial-gradient(1000px 600px at 50% 30%, transparent 40%, rgba(0,0,0,0.35) 80%);
    }

    /* === Center stage === */
    .stage { position: relative; z-index: 2; min-height: 100dvh; display: grid; place-items: center; padding: 24px; }
    .stack { width: min(92vw, 720px); position: relative; }

    /* === Morphing SVG behind title === */
    .blob-wrap { position: absolute; inset: -80px -40px; z-index: -1; pointer-events: none; }
    svg.blobs { width: 110%; max-width: 110%; display: block; margin: 0 auto; filter: drop-shadow(0 10px 60px rgba(255,255,255,0.25)); opacity: 0.9; }
    .blob { mix-blend-mode: screen; }

    /* === Title === */
    .title { text-align: center; font-weight: 800; letter-spacing: -0.02em; font-size: clamp(40px, 7vw, 72px); margin: 0; text-shadow: 0 6px 30px rgba(255,255,255,0.25); }
    .subtitle { text-align: center; margin-top: 8px; opacity: 0.8; }

    /* Title letter rise-in */
    .title span { display: inline-block; transform: translateY(20px); opacity: 0; filter: blur(6px); animation: rise 0.9s cubic-bezier(.2,.7,.2,1) forwards; }
    .title span:nth-child(odd) { animation-delay: calc(var(--i) * 40ms); }
    .title span:nth-child(even) { animation-delay: calc(var(--i) * 40ms + 60ms); }
    @keyframes rise { to { transform: translateY(0); opacity: 1; filter: blur(0); } }

    /* shimmering text glow */
    .glow { background: linear-gradient(90deg, #fff 0%, #fff 15%, rgba(255,255,255,0.55) 30%, #fff 45%, #fff0 60%);
      -webkit-background-clip: text; background-clip: text; color: transparent; background-size: 200% 100%;
      animation: shimmer 2.6s ease-in-out infinite; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    /* === Card === */
    .card { margin-top: 18px; background: var(--card-bg); border: 1px solid var(--card-stroke); border-radius: 18px; backdrop-filter: blur(16px) saturate(1.1);
      box-shadow: 0 10px 40px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.08); }
    .card h2 { margin: 0; padding: 18px 20px 0 20px; text-align: center; font-weight: 600; opacity: 0.95; }
    .card form { padding: 18px 20px 22px 20px; }
    .row { display: grid; gap: 10px; margin-bottom: 14px; }
    label { font-size: 14px; opacity: 0.9; }
    input[type="email"], input[type="password"] {
      width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.18); background: rgba(0,0,0,0.35); color: #fff;
      outline: none; transition: box-shadow .2s, border-color .2s; backdrop-filter: blur(6px);
    }
    input::placeholder { color: rgba(255,255,255,0.55); }
    input:focus { border-color: rgba(255,255,255,0.5); box-shadow: 0 0 0 4px rgba(255,255,255,0.15); }

    .btn {
      appearance: none; width: 100%; border: 0; border-radius: 12px; padding: 12px 16px; font-weight: 700; font-size: 16px; cursor: pointer;
      color: #0b0b0b; background: #fff; box-shadow: 0 10px 30px rgba(255,255,255,0.25), inset 0 -10px 20px rgba(0,0,0,0.08);
      transition: transform .06s ease, box-shadow .2s ease; }
    .btn:active { transform: translateY(1px); box-shadow: 0 4px 18px rgba(255,255,255,0.22); }

    /* Footer controls */
    .controls { display: flex; gap: 10px; justify-content: center; align-items: center; margin-top: 10px; opacity: 0.9; font-size: 14px; }
    .switch { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
    .switch input { appearance: none; width: 38px; height: 22px; border-radius: 999px; background: rgba(255,255,255,0.35); position: relative; outline: none; transition: background .2s; }
    .switch input:checked { background: #fff; }
    .switch input::after { content: ""; position: absolute; inset: 3px; width: 16px; height: 16px; border-radius: 50%; background: #0b0b0b; transform: translateX(0);
      transition: transform .2s; }
    .switch input:checked::after { transform: translateX(16px); }

    /* Twinkles */
    .twinkle { position: fixed; inset: 0; pointer-events: none; z-index: 1; }
    .twinkle i { position: absolute; width: 2px; height: 2px; background: #fff; opacity: 0; filter: drop-shadow(0 0 6px #fff); animation: twinkle 6s infinite ease-in-out; }
    @keyframes twinkle { 0%,100%{ opacity:0 } 10%{ opacity: .9 } 50%{ opacity:.2 } }

    /* Error messages */
    .error { color: #f87171; font-size: 14px; margin-top: 6px; }

    /* Logo lockup */
    .logo-lockup {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 30px;
      margin-bottom: 20px;
    }
    .logo-lockup img {
      width: 200px;
      height: 149px;
      display: block;
    }
    .logo-lockup h1 {
      font-family: Inter, system-ui, -apple-system, sans-serif;
      font-weight: 800;
      color: #ffffff;
      font-size: 120px;
      line-height: 120px;
      margin: 0;
      letter-spacing: -0.02em;
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
      .bg-gradient, .bg-hue, .title span, .glow, .twinkle i { animation: none !important; }
      svg .morph-1 animate, svg .morph-2 animate, svg .morph-3 animate { display: none; }
    }
    /* Manual toggle via [data-reduced="true"] */
    [data-reduced="true"] .bg-gradient, [data-reduced="true"] .bg-hue, [data-reduced="true"] .title span, [data-reduced="true"] .glow, [data-reduced="true"] .twinkle i { animation: none !important; }
    [data-reduced="true"] svg .morph-1 animate, [data-reduced="true"] svg .morph-2 animate, [data-reduced="true"] svg .morph-3 animate { display: none; }
  </style>
</head>
<body :data-reduced="reduced">
  <!-- Animated background layers -->
  <div class="bg-wrap" aria-hidden="true">
    <div class="bg-gradient"></div>
    <div class="bg-hue"></div>
  </div>
  <div class="vignette" aria-hidden="true"></div>

  <!-- Optional twinkles -->
  <div class="twinkle" aria-hidden="true">
    <!-- A few fixed stars with different timings -->
    <i style="left:12%; top:18%; animation-delay: 0s"></i>
    <i style="left:78%; top:26%; animation-delay: .8s"></i>
    <i style="left:64%; top:12%; animation-delay: 1.6s"></i>
    <i style="left:32%; top:70%; animation-delay: 2.1s"></i>
    <i style="left:86%; top:64%; animation-delay: 3.2s"></i>
  </div>

  <main class="stage">
    <section class="stack">
      <!-- Morphing blobs behind the title -->
      <div class="blob-wrap" aria-hidden="true">
        <svg class="blobs" viewBox="0 0 400 320" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.25"/>
              <stop offset="100%" stop-color="#ffffff" stop-opacity="0.06"/>
            </linearGradient>
          </defs>
          <g class="morph-1" opacity="0.55">
            <path fill="url(#g1)">
              <animate attributeName="d" dur="14s" repeatCount="indefinite" values="
                M393,155Q374,210,335,245Q296,280,245,308Q194,336,150,308Q106,280,81,237Q56,194,80,147Q104,100,149,77Q194,54,245,73Q296,92,345,119Q394,146,393,155Z;
                M392,165Q368,212,330,246Q292,280,238,301Q184,322,143,294Q102,266,78,225Q54,184,83,142Q112,100,155,78Q198,56,245,70Q292,84,340,113Q388,142,392,165Z;
                M387,160Q370,210,328,246Q286,282,233,300Q180,318,136,292Q92,266,71,225Q50,184,78,141Q106,98,152,78Q198,58,245,74Q292,90,338,116Q384,142,387,160Z;
                M393,155Q374,210,335,245Q296,280,245,308Q194,336,150,308Q106,280,81,237Q56,194,80,147Q104,100,149,77Q194,54,245,73Q296,92,345,119Q394,146,393,155Z"/>
            </path>
          </g>
          <g class="morph-2" opacity="0.35" transform="translate(20,-10)">
            <path fill="url(#g1)">
              <animate attributeName="d" dur="18s" repeatCount="indefinite" values="
                M333,135Q318,188,281,219Q244,250,198,274Q152,298,117,268Q82,238,65,197Q48,156,70,116Q92,76,127,52Q162,28,202,45Q242,62,285,86Q328,110,333,135Z;
                M340,140Q318,190,278,221Q238,252,197,269Q156,286,118,262Q80,238,66,197Q52,156,72,117Q92,78,127,54Q162,30,203,44Q244,58,288,84Q332,110,340,140Z;
                M333,135Q318,188,281,219Q244,250,198,274Q152,298,117,268Q82,238,65,197Q48,156,70,116Q92,76,127,52Q162,28,202,45Q242,62,285,86Q328,110,333,135Z"/>
            </path>
          </g>
          <g class="morph-3" opacity="0.25" transform="translate(-10,10)">
            <path fill="url(#g1)">
              <animate attributeName="d" dur="22s" repeatCount="indefinite" values="
                M370,160Q340,210,300,240Q260,270,210,290Q160,310,120,280Q80,250,60,205Q40,160,65,120Q90,80,130,60Q170,40,215,60Q260,80,305,105Q350,130,370,160Z;
                M372,162Q340,210,302,238Q264,266,214,286Q164,306,124,276Q84,246,64,202Q44,158,68,120Q92,82,132,62Q172,42,216,62Q260,82,306,108Q352,134,372,162Z;
                M370,160Q340,210,300,240Q260,270,210,290Q160,310,120,280Q80,250,60,205Q40,160,65,120Q90,80,130,60Q170,40,215,60Q260,80,305,105Q350,130,370,160Z"/>
            </path>
          </g>
        </svg>
      </div>

      <!-- Logo and Branding -->
      <div style="text-align: center; margin-bottom: 32px;">
        <div class="logo-lockup">
          <img src="{{ asset('MM_logo_200px.png') }}" alt="MajorMajor">
          <h1>Joy</h1>
        </div>
      </div>

      <!-- Login card -->
      <div class="card" role="region" aria-label="Sign in">
        <h2>Sign in</h2>
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="row">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="you@example.com" x-model="email" value="{{ old('email') }}" required />
            @error('email')
              <div class="error">{{ $message }}</div>
            @enderror
          </div>
          <div class="row">
            <label for="password">Password</label>
            <div style="position:relative">
              <input :type="show ? 'text' : 'password'" id="password" name="password" placeholder="••••••••" x-model="password" required />
              <button type="button" @click="show=!show" aria-label="Toggle password visibility" title="Show/Hide"
                style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:0; color:#fff; opacity:.8; cursor:pointer">👁️</button>
            </div>
            @error('password')
              <div class="error">{{ $message }}</div>
            @enderror
          </div>
          <button class="btn" type="submit">Enter</button>
        </form>
        <div class="controls">
          <label class="switch" title="Reduce animations">
            <input type="checkbox" x-model="reduced" @change="persistReduced()" />
            <span>Reduce motion</span>
          </label>
        </div>
      </div>
    </section>
  </main>

  <script>
    function loginUI() {
      return {
        appName: 'Joy',
        email: '{{ old('email') }}', 
        password: '', 
        show: false,
        reduced: false,
        init() {
          const saved = localStorage.getItem('reduced-motion');
          if (saved !== null) this.reduced = saved === 'true';
        },
        persistReduced() { 
          localStorage.setItem('reduced-motion', this.reduced); 
        },
      }
    }

    // Split heading into letter spans to trigger staggered animation via CSS nth-child
    function splitTitle() {
      const el = document.querySelector('.title .glow');
      if (!el) return;
      const text = el.textContent.trim();
      el.innerHTML = '';
      [...text].forEach((ch, i) => {
        const s = document.createElement('span');
        s.style.setProperty('--i', i);
        s.textContent = ch === ' ' ? '\u00A0' : ch;
        el.appendChild(s);
      });
    }
  </script>
</body>
</html>