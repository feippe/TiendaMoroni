<section id="nuestra-fe"
         aria-label="Nuestra fe y comunidad"
         class="overflow-hidden"
         x-data="{ visible: false }"
         x-intersect.once="visible = true">
  <div class="grid grid-cols-1 lg:grid-cols-2" style="min-height:560px">

    <!-- Left: Image — Fix 6: relative + overflow-hidden on figure, overlay div -->
    <figure role="img"
            aria-label="Paisaje sereno que evoca paz y espiritualidad"
            class="relative overflow-hidden h-56 lg:h-full m-0"
            :class="visible ? 'fe-slide-in-left' : 'fe-hidden-left'">
      <img src="/assets/img/paisaje.webp"
           alt="Paisaje sereno al amanecer que refleja paz y espiritualidad, representando los valores de la comunidad de Tienda Moroni"
           loading="lazy"
           decoding="async"
           width="800"
           height="560"
           class="w-full h-full object-cover">
      <!-- Fix 6: navy overlay -->
      <div class="absolute inset-0 pointer-events-none bg-brand-800 opacity-30" aria-hidden="true"></div>
    </figure>

    <!-- Fix 2 & 4: background via class (not style so Alpine :style won't override it),
         flex-col justify-center items-start, h-full -->
    <div class="flex flex-col justify-center items-start h-full px-10 py-16 lg:px-16 bg-brand-800"
         :class="visible ? 'fe-slide-in-right' : 'fe-hidden-right'">
      <div class="max-w-md w-full">

        <!-- Fix 5: decorative gold line — block h-0.5 -->
        <div class="block w-12 h-0.5 mb-6" style="background:#C6A75E" aria-hidden="true"></div>

        <!-- Fix 3: eyebrow color via inline style -->
        <p class="font-sans font-light text-xs tracking-widest uppercase mb-4"
           style="color:#C6A75E">
          Nuestra Fe
        </p>

        <!-- Fix 1: headline — always present, color text-warm-50 -->
        <h2 class="font-serif italic text-3xl lg:text-4xl leading-snug mb-6"
            style="font-family:'Playfair Display',Georgia,serif;color:#F8F6F2">
          Detrás de cada artesanía,<br> hay una historia de fe
        </h2>

        <!-- Fix 3: body text warm-200 -->
        <p class="font-sans font-light text-base leading-relaxed mb-8"
           style="color:#e2ddd4">
          <b>Tienda Moroni</b> nació en el seno de una comunidad que cree en el poder de crear con propósito.
          <br>Te invitamos a conocer más sobre <b>La Iglesia de Jesucristo de los Santos de los Últimos Días</b>,
          visitá <em><b>veniracristo.org</b></em>, un espacio hecho para quienes buscan.
        </p>

        <!-- Fix 3: CTA border+text accent, hover via .fe-cta class in app.css -->
        <a href="https://www.veniracristo.org"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Visitá veniracristo.org para conocer más sobre La Iglesia de Jesucristo de los Santos de los Últimos Días (abre en nueva pestaña)"
           class="fe-cta w-full lg:w-auto inline-flex items-center justify-center gap-2 border rounded-full px-7 py-3 text-sm font-light tracking-wide transition duration-200"
           style="border-color:#C6A75E;color:#C6A75E">
          Quiero saber más
          <i data-lucide="arrow-right" class="w-4 h-4 flex-shrink-0"></i>
        </a>

      </div>
    </div>

  </div>
</section>
