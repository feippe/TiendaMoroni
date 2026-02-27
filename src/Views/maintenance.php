<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Sitio en mantenimiento — Tienda Moroni</title>

  <link rel="icon" href="/assets/img/isotipo.ico" type="image/x-icon">
  <link rel="shortcut icon" href="/assets/img/isotipo.ico" type="image/x-icon">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans:  ['Lato', 'sans-serif'],
            serif: ['Playfair Display', 'Georgia', 'serif'],
          },
          colors: {
            brand: {
              50: '#f5f7fa', 100: '#e8edf4', 200: '#c8d4e4', 300: '#9ab1cc',
              400: '#C6A75E', 500: '#b5923f', 600: '#1B3A5C', 700: '#162E4A',
              800: '#0F1E2E', 900: '#090f17',
            },
            warm: {
              50: '#F8F6F2', 100: '#f0ede6', 200: '#e2ddd4', 300: '#cdc7bb',
              400: '#a8a092', 500: '#7a7268', 600: '#5a5450', 700: '#44403c',
              800: '#2C2A27', 900: '#1a1916',
            },
            accent: { DEFAULT: '#C6A75E', light: '#d4b97a', dark: '#a88840' },
          },
        },
      },
    }
  </script>
</head>
<body class="bg-brand-800 font-sans antialiased min-h-screen flex items-center justify-center px-4">

  <div class="max-w-md w-full text-center px-8 py-12">

    <!-- Logo mark -->
    <div class="flex flex-col items-center mb-6">
      <img src="/assets/img/isotipo.svg"
           alt="Tienda Moroni"
           width="48" height="48"
           class="w-12 h-12 mb-3"
           style="filter: brightness(0) saturate(100%) invert(70%) sepia(40%) saturate(600%) hue-rotate(5deg) brightness(95%);">
      <p class="font-sans font-light tracking-widest text-xs uppercase" style="color:#C6A75E">TIENDA</p>
      <p class="font-serif font-bold text-2xl" style="color:#F8F6F2">Moroni</p>
    </div>

    <!-- Divider -->
    <div class="w-12 h-px mx-auto my-8" style="background:#C6A75E"></div>

    <!-- Headline -->
    <h1 class="font-serif italic text-2xl leading-snug mb-4" style="color:#F8F6F2">
      Estamos mejorando la tienda
    </h1>

    <!-- Body -->
    <p class="font-sans font-light text-sm leading-relaxed text-warm-300 mb-8">
      Estamos realizando tareas de mantenimiento para brindarte una mejor experiencia.
      Volvé a visitarnos en unos momentos.
    </p>

  </div>

</body>
</html>
