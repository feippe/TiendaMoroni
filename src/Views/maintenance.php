<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#0D1C38">
  <title>Sitio en mantenimiento — Tienda Moroni</title>

  <link rel="icon" href="/assets/img/isotipo.svg?v=3" type="image/svg+xml">
  <link rel="icon" href="/assets/img/isotipo.ico?v=3" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico?v=3" type="image/x-icon">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <!-- Tailwind CSS: compilado y minificado para producción (npm run build) -->
  <link rel="stylesheet" href="/assets/css/tailwind.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">

  <style>
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse-gold {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.5; }
    }
    .anim-1 { animation: fadeInUp 0.6s ease 0ms   both; }
    .anim-2 { animation: fadeInUp 0.6s ease 150ms both; }
    .anim-3 { animation: fadeInUp 0.6s ease 300ms both; }
    .anim-4 { animation: fadeInUp 0.6s ease 450ms both; }
    .dot-grid {
      background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
      background-size: 28px 28px;
    }
    .pulse-dot { animation: pulse-gold 2s ease-in-out infinite; }
  </style>
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4 relative overflow-hidden"
      style="background:linear-gradient(150deg,var(--color-navy-deeper) 0%,var(--color-navy-dark) 60%,var(--color-navy-deeper) 100%)">

  <!-- Dot grid texture -->
  <div class="dot-grid absolute inset-0 pointer-events-none" aria-hidden="true" style="opacity:0.035"></div>

  <!-- Gold radial glow -->
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true"
       style="background:radial-gradient(ellipse 60% 50% at 50% 45%, rgba(232,176,32,0.07) 0%, transparent 70%)"></div>

  <!-- Card -->
  <div class="relative z-10 max-w-sm w-full text-center">

    <!-- Logo -->
    <div class="anim-1 flex justify-center mb-10">
      <img src="/assets/img/Logo.svg?v=2"
           alt="Tienda Moroni"
           class="h-12 w-auto"
           style="filter:brightness(0) invert(1);">
    </div>

    <!-- Gold divider top -->
    <div class="anim-2 w-10 h-px mx-auto mb-10" style="background:var(--color-gold)"></div>

    <!-- Icon: wrench / tools -->
    <div class="anim-2 flex justify-center mb-6">
      <span class="pulse-dot inline-flex items-center justify-center w-14 h-14 rounded-full"
            style="background:rgba(232,176,32,0.1);border:1px solid rgba(232,176,32,0.3)">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
             fill="none" stroke="#E8B020" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
      </span>
    </div>

    <!-- Headline -->
    <h1 class="anim-3 font-serif italic text-2xl sm:text-3xl leading-snug mb-4"
        style="color:var(--color-white)">
      Estamos mejorando<br>la tienda para vos
    </h1>

    <!-- Body -->
    <p class="anim-3 font-light text-sm leading-relaxed mb-10"
       style="color:rgba(238,241,248,0.8)">
      Estamos realizando tareas de mantenimiento para brindarte<br class="hidden sm:block">
      una mejor experiencia. Volvé a visitarnos en unos momentos.
    </p>

    <!-- Gold divider bottom -->
    <div class="anim-4 w-10 h-px mx-auto mb-8" style="background:var(--color-gold)"></div>

    <!-- Footer note -->
    <p class="anim-4 font-light text-xs tracking-widest uppercase" style="color:rgba(232,176,32,0.55)">
      Volvemos pronto
    </p>

  </div>

</body>
</html>
