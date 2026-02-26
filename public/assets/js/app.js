/**
 * TiendaMoroni — app.js
 * Global client-side logic. Runs after DOM content loads.
 */

/* ─────────────────────────────────────────────────────────────────────────
 * Alpine.js global cart store — initialised once per page
 * ───────────────────────────────────────────────────────────────────────── */
document.addEventListener('alpine:init', () => {
  Alpine.store('cart', {
    count: (typeof __CART_COUNT !== 'undefined') ? __CART_COUNT : 0,
    increment(by = 1) { this.count += by; },
    set(n)            { this.count = n; },
  });
});

/* ─────────────────────────────────────────────────────────────────────────
 * addToCart(productId, triggerEl, qty = 1)
 * Called by "Agregá al carrito" buttons.
 * ───────────────────────────────────────────────────────────────────────── */
async function addToCart(productId, triggerEl, qty = 1) {
  const btn      = triggerEl instanceof Element ? triggerEl : document.querySelector(triggerEl);
  const original = btn ? btn.innerHTML : '';

  if (btn) {
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner"></span>';
  }

  try {
    const res  = await fetch('/carrito/agregar', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    new URLSearchParams({
        product_id: productId,
        qty:        qty,
        _csrf:      document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      }),
    });

    const data = await res.json();

    if (data.success) {
      // Fly-to-cart animation
      flyToCart(btn, () => {
        // Update counter once the dot arrives at the icon
        if (typeof Alpine !== 'undefined' && Alpine.store('cart')) {
          Alpine.store('cart').set(data.cartCount ?? Alpine.store('cart').count + qty);
        }
      });

      // Button success state
      if (btn) {
        btn.innerHTML = '✓ Agregado';
        btn.classList.add('bg-green-600');
        setTimeout(() => {
          btn.innerHTML = original;
          btn.disabled  = false;
          btn.classList.remove('bg-green-600');
        }, 1800);
      }
    } else {
      alert(data.message ?? 'No se pudo agregar el producto.');
      if (btn) { btn.innerHTML = original; btn.disabled = false; }
    }
  } catch (err) {
    console.error('addToCart error:', err);
    if (btn) { btn.innerHTML = original; btn.disabled = false; }
  }
}

/* ─────────────────────────────────────────────────────────────────────────
 * flyToCart(fromEl, onArrival)
 * Animates a gold dot from fromEl to the #cart-icon, then calls onArrival
 * and briefly bounces the cart icon.
 * ───────────────────────────────────────────────────────────────────────── */
function flyToCart(fromEl, onArrival) {
  const cartIcon = document.getElementById('cart-icon');
  if (!cartIcon || !fromEl) { onArrival && onArrival(); return; }

  const fromRect = fromEl.getBoundingClientRect();
  const toRect   = cartIcon.getBoundingClientRect();

  const dot = document.createElement('div');
  dot.className = 'fly-dot';
  dot.style.top  = (fromRect.top  + fromRect.height / 2 - 11) + 'px';
  dot.style.left = (fromRect.left + fromRect.width  / 2 - 11) + 'px';
  document.body.appendChild(dot);

  // Force reflow so the initial position is painted before the transition starts
  dot.getBoundingClientRect();

  // Fly to cart icon center, shrink, fade
  dot.style.top     = (toRect.top  + toRect.height / 2 - 5) + 'px';
  dot.style.left    = (toRect.left + toRect.width  / 2 - 5) + 'px';
  dot.style.width   = '10px';
  dot.style.height  = '10px';
  dot.style.opacity = '0';

  dot.addEventListener('transitionend', () => {
    dot.remove();
    onArrival && onArrival();
    // Bounce the cart icon
    cartIcon.classList.remove('cart-icon-bounce');
    void cartIcon.offsetWidth; // reflow to restart animation if triggered twice fast
    cartIcon.classList.add('cart-icon-bounce');
    setTimeout(() => cartIcon.classList.remove('cart-icon-bounce'), 600);
  }, { once: true });
}

/* ─────────────────────────────────────────────────────────────────────────
 * Live search debounce on product listing page
 * A hidden form is submitted on input change with a 400 ms debounce.
 * ───────────────────────────────────────────────────────────────────────── */
(function initLiveSearch() {
  const input  = document.getElementById('product-search-input');
  const form   = input?.closest('form');
  if (!input || !form) return;

  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => form.submit(), 400);
  });
})();

/* ─────────────────────────────────────────────────────────────────────────
 * Flash message auto-dismiss
 * ───────────────────────────────────────────────────────────────────────── */
(function initFlashDismiss() {
  const flashes = document.querySelectorAll('[data-flash]');
  flashes.forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });
})();
