<section id="nuestra-fe"
         aria-label="Nuestra fe y comunidad"
         class="overflow-hidden"
         x-data="{ visible: false }"
         x-intersect.once="visible = true">
  <div class="grid grid-cols-1 lg:grid-cols-2" style="min-height:560px">

    <!-- Left: Image -->
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
      <!-- navy overlay -->
      <div class="absolute inset-0 pointer-events-none bg-navy opacity-30" aria-hidden="true"></div>
    </figure>

    <!-- Right: Content -->
    <div class="flex flex-col justify-center items-start h-full px-10 py-16 lg:px-16 bg-navy-deeper"
         :class="visible ? 'fe-slide-in-right' : 'fe-hidden-right'">
      <div class="max-w-md w-full">

        <!-- Decorative gold line -->
        <div class="block w-12 h-0.5 bg-gold mb-6" aria-hidden="true"></div>

        <!-- Eyebrow -->
        <p class="font-sans font-light text-xs tracking-widest uppercase mb-4 text-gold">
          Nuestra Fe
        </p>

        <!-- Headline -->
        <h2 class="font-serif italic text-3xl lg:text-4xl leading-snug mb-6 text-navy-light"
            style="font-family:'Playfair Display',Georgia,serif">
          Detrás de cada artesanía,<br> hay una historia de fe
        </h2>

        <!-- Body -->
        <p class="font-sans font-light text-base leading-relaxed mb-8 text-navy-light/75">
          <b>Tienda Moroni</b> nació en el seno de una comunidad que cree en el poder de crear con propósito.
          <br>Te invitamos a conocer más sobre <b>La Iglesia de Jesucristo de los Santos de los Últimos Días</b>,
          visitá <em><b>veniracristo.org</b></em>, un espacio hecho para quienes buscan.
        </p>

        <!-- CTA — hover via .fe-cta class in app.css -->
        <a href="https://www.veniracristo.org"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Visitá veniracristo.org para conocer más sobre La Iglesia de Jesucristo de los Santos de los Últimos Días (abre en nueva pestaña)"
           class="fe-cta w-full lg:w-auto inline-flex items-center justify-center gap-2 border border-gold text-gold rounded-full px-7 py-3 text-sm font-light tracking-wide transition duration-200">
          Quiero saber más
          <i data-lucide="arrow-right" class="w-4 h-4 flex-shrink-0"></i>
        </a>

      </div>
    </div>

  </div>
</section>
