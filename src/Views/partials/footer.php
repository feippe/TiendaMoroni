<footer class="bg-brand-900 text-warm-400 mt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- Brand -->
      <div class="md:col-span-1">
        <a href="/" class="text-white font-extrabold text-lg font-serif"><?= SITE_NAME ?></a>
        <p class="mt-1 text-xs text-brand-400 tracking-wide">La tienda de nuestra comunidad</p>
        <p class="mt-3 text-sm leading-relaxed">
          Un espacio creado con amor, para que los artesanos y vendedores SUD de Uruguay puedan compartir sus creaciones con toda la comunidad.
        </p>
      </div>

      <!-- Links -->
      <div>
        <h4 class="text-white font-semibold mb-3 text-sm">Artesanías</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="/productos" class="hover:text-white transition">Ver todo</a></li>
          <li><a href="/buscar"    class="hover:text-white transition">Buscar</a></li>
          <li><a href="/carrito"   class="hover:text-white transition">Mi carrito</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-semibold mb-3 text-sm">Mi cuenta</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="/mi-cuenta"     class="hover:text-white transition">Mi cuenta</a></li>
          <li><a href="/auth/login"    class="hover:text-white transition">Iniciar sesión</a></li>
          <li><a href="/auth/register" class="hover:text-white transition">Crear cuenta</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-white font-semibold mb-3 text-sm">Contacto</h4>
        <ul class="space-y-2 text-sm">
          <li>
            <a href="mailto:<?= SITE_EMAIL ?>" class="hover:text-white transition">
              <?= SITE_EMAIL ?>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="mt-10 pt-6 border-t border-brand-800 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-warm-500">
      <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados. Desarrollado por <a href="https://feippe.com" class="hover:text-white transition underline" target="_blank">Feippe</a>.</p>
      <p>Hecho con ❤️ en Uruguay</p>
    </div>
  </div>
</footer>
