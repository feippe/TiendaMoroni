/** @type {import('tailwindcss').Config} */
module.exports = {
  // Escanea TODOS los archivos donde se usen clases Tailwind
  content: [
    './src/Views/**/*.php',
    './public_html/assets/js/*.js',
  ],

  // Clases generadas dinámicamente desde JS (classList.add/remove)
  // que el scanner estático no detecta — deben estar aquí para no ser purgadas.
  safelist: [
    '-translate-x-full',   // admin.js: sidebar toggle
    'bg-green-600',        // app.js: cart button success state
    // Las siguientes son usadas estáticamente también, pero las dejamos
    // explícitas por claridad:
    'hidden',
    'cart-icon-bounce',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans:  ['Lato', 'sans-serif'],
        serif: ['Playfair Display', 'Georgia', 'serif'],
      },
      colors: {
        /* ── Paleta principal ── */
        gold: {
          DEFAULT: '#E8B020',
          dark:    '#C8920A',
          soft:    '#F5C842',
          light:   '#FEF9EC',
          tint:    '#FDF0C4',
          deeper:  '#9E6F05',
        },
        navy: {
          DEFAULT: '#1E3A6E',
          dark:    '#152B54',
          deeper:  '#0D1C38',
          light:   '#EEF1F8',
          mid:     '#4F6EA8',
        },
        /* ── Neutros cálidos ── */
        warm: {
          50:  '#F8F6F2',
          100: '#f0ede6',
          200: '#e2ddd4',
          300: '#cdc7bb',
          400: '#7c756c',
          500: '#7a7268',
          600: '#5a5450',
          700: '#44403c',
          800: '#2C2A27',
          900: '#1a1916',
        },
        /* ── Alias de compatibilidad: brand → paleta oficial ── */
        brand: {
          50:  '#F8F9FA',
          100: '#EEF1F8',
          200: '#EEF1F8',
          300: '#4F6EA8',
          400: '#E8B020',
          500: '#C8920A',
          600: '#1E3A6E',
          700: '#152B54',
          800: '#0D1C38',
          900: '#0D1C38',
        },
        /* ── Alias extra (sólo admin) ── */
        accent: {
          DEFAULT: '#E8B020',
          light:   '#F5C842',
          dark:    '#C8920A',
        },
      },
      transitionDuration: {
        DEFAULT: '200ms',
      },
    },
  },
  plugins: [],
};
