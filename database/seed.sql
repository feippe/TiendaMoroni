-- ============================================================
--  TiendaMoroni – Seed Data
--  Run AFTER schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Admin user (password: Admin1234!) ─────────────────────────────────────────
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `auth_provider`, `role`) VALUES
(1, 'Admin TiendaMoroni', 'admin@tiendamoroni.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin1234!
 'own', 'admin');

-- ── Vendor user & vendor record ───────────────────────────────────────────────
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `auth_provider`, `role`) VALUES
(2, 'Hermana López', 'artesanias.lopez@tiendamoroni.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'own', 'buyer');

INSERT INTO `vendors` (`id`, `user_id`, `business_name`, `email`, `phone`) VALUES
(1, 2, 'Artesanías Hermana López', 'artesanias.lopez@tiendamoroni.com', '+598 99 123 456');

-- ── Categories ────────────────────────────────────────────────────────────────
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`, `meta_title`, `meta_description`, `sort_order`) VALUES
(1, 'Escrituras', 'escrituras',
 'Tapas, fundas y accesorios artesanales para el Libro de Mormón y la Biblia.',
 'https://picsum.photos/seed/scriptures-lds/600/400',
 'Escrituras artesanales – TiendaMoroni',
 'Descubrí tapas, fundas y marcadores artesanales para tus escrituras sagradas.',
 1),
(2, 'Joyería y Accesorios', 'joyeria-accesorios',
 'Llaveros CTR, pulseras, anillos y bijou con símbolos de la Iglesia hechos a mano.',
 'https://picsum.photos/seed/jewelry-lds/600/400',
 'Joyería artesanal – TiendaMoroni',
 'Llaveros CTR, pulseras y accesorios con símbolos de la fe, creados por hermanos artesanos.',
 2),
(3, 'Decoración del Hogar', 'decoracion-hogar',
 'Cuadros con escrituras, figuras del Ángel Moroni y adornos con temática de la Iglesia.',
 'https://picsum.photos/seed/homedeco-lds/600/400',
 'Decoración del hogar – TiendaMoroni',
 'Adorn atu hogar con cuadros, figuras y piezas artesanales con temática de la Iglesia.',
 3),
(4, 'Artículos para Misioneros', 'misioneros',
 'Fundas de credencial, libretas personalizadas y sets especiales para misioneros.',
 'https://picsum.photos/seed/missionary-lds/600/400',
 'Artículos para misioneros – TiendaMoroni',
 'Equipá al misionero de tu familia con artesanías hechas con amor y fe.',
 4),
(5, 'Regalos y Ocasiones', 'regalos',
 'Regalos únicos para bautismos, confirmaciones, bodas en el templo y misiones.',
 'https://picsum.photos/seed/gifts-lds/600/400',
 'Regalos para ocasiones especiales – TiendaMoroni',
 'Encontrá el regalo perfecto para cada momento especial de nuestra comunidad.',
 5),
(6, 'Aceiteros y Bendición', 'aceiteros-bendicion',
 'Aceiteros artesanales en madera, cuero y cerámica para la ordenanza de sanidad.',
 'https://picsum.photos/seed/anointing-lds/600/400',
 'Aceiteros artesanales – TiendaMoroni',
 'Aceiteros para la ordenanza de sanidad, hechos con dedicación y respeto.',
 6);

-- ── Products (8) ──────────────────────────────────────────────────────────────
INSERT INTO `products`
  (`id`,`vendor_id`,`category_id`,`name`,`slug`,`description`,`short_description`,
   `price`,`stock`,`status`,`featured`,`main_image_url`,`meta_title`,`meta_description`)
VALUES
(1, 1, 1,
 'Tapa artesanal para el Libro de Mormón — cuero marrón',
 'tapa-libro-mormon-cuero-marron',
 '<p>Tapa protectora hecha a mano en cuero genuino marrón oscuro. Incluye bolsillo interior para lápiz y marcapáginas bordado con hilo dorado. Compatible con la edición estándar del Libro de Mormón. Cada pieza es única gracias al acabado artesanal.</p>',
 'Tapa protectora en cuero genuino hecha a mano para el Libro de Mormón.',
 890.00, 12, 'active', 1,
 'https://picsum.photos/seed/book-cover-lds/600/600',
 'Tapa artesanal Libro de Mormón – TiendaMoroni',
 'Tapa en cuero genuino hecha a mano para el Libro de Mormón. Artesanía uruguaya.'
),
(2, 1, 2,
 'Llavero CTR bordado a mano',
 'llavero-ctr-bordado',
 '<p>Llavero artesanal con el escudo CTR (Choose the Right) bordado en hilo dorado sobre fondo azul marino. Refuerzo metálico dorado. Disponible en azul, verde y blanco. Hecho con amor en Uruguay.</p>',
 'Llavero con escudo CTR bordado en hilo dorado, hecho a mano en Uruguay.',
 320.00, 30, 'active', 1,
 'https://picsum.photos/seed/ctr-keychain/600/600',
 'Llavero CTR bordado – TiendaMoroni',
 'Llavero artesanal CTR bordado a mano. El regalo perfecto para jóvenes de la Iglesia.'
),
(3, 1, 3,
 'Cuadro con escritura de Moroni 10:4',
 'cuadro-moroni-10-4',
 '<p>Cuadro de madera pintado a mano con la escritura de Moroni 10:4 en tipografía caligráfica. Terminación en acrílico dorado sobre fondo azul marino. Medidas: 25×35 cm. Listo para colgar. Incluye soporte trasero.</p>',
 'Cuadro artesanal con Moroni 10:4 pintado a mano, terminación dorada.',
 1250.00, 8, 'active', 1,
 'https://picsum.photos/seed/moroni-scripture/600/600',
 'Cuadro Moroni 10:4 artesanal – TiendaMoroni',
 'Cuadro con escritura de Moroni 10:4 pintado a mano. Decoración con temática de la Iglesia.'
),
(4, 1, 6,
 'Set aceitero de bendición en madera de algarrobo',
 'set-aceitero-madera-algarrobo',
 '<p>Aceitero artesanal torneado en madera de algarrobo uruguayo. Incluye frasco de vidrio interior sellado y tapa a rosca con acabado en cera natural. Diámetro 3 cm, altura 7 cm. Cada pieza es única por la veta natural de la madera.</p>',
 'Aceitero artesanal torneado en algarrobo uruguayo con frasco de vidrio interior.',
 680.00, 15, 'active', 1,
 'https://picsum.photos/seed/anointing-wood/600/600',
 'Set aceitero artesanal – TiendaMoroni',
 'Aceitero de bendición en madera de algarrobo uruguayo, torneado a mano.'
),
(5, 1, 4,
 'Funda de credencial para misionero — personalizada',
 'funda-credencial-misionero',
 '<p>Funda de credencial en cuero sintético con nombre del misionero bordado. Incluye tarjetero trasero para 2 tarjetas. Compatible con los formatos estándar de credencial de la Iglesia. Disponible en azul marino, negro y bordo. Personalizá con nombre y misión.</p>',
 'Funda para credencial de misionero en cuero sintético con nombre bordado.',
 490.00, 25, 'active', 1,
 'https://picsum.photos/seed/missionary-badge/600/600',
 'Funda de credencial misionero – TiendaMoroni',
 'Funda de credencial personalizada para misioneros. El regalo ideal antes de partir a la misión.'
),
(6, 1, 2,
 'Pulsera artesanal con símbolo del templo',
 'pulsera-simbolo-templo',
 '<p>Pulsera trenzada a mano en hilo de seda en colores blanco y dorado, con dije metálico plateado con la silueta del templo. Regulable con nudo corredizo. Talla única. Empaque en cajita de regalo.</p>',
 'Pulsera trenzada en seda blanca y dorada con dije del templo, en cajita de regalo.',
 420.00, 20, 'active', 0,
 'https://picsum.photos/seed/temple-bracelet/600/600',
 'Pulsera símbolo del templo – TiendaMoroni',
 'Pulsera artesanal con símbolo del templo, ideal para regalos de bautismo o confirmación.'
),
(7, 1, 5,
 'Set de regalo para bautismo',
 'set-regalo-bautismo',
 '<p>Set artesanal de bautismo que incluye: libreta pequeña de cuero con ángel grabado, llavero CTR, marcador bordado para escrituras y tarjeta personalizada. Todo presentado en una caja de tela azul marino con lazo dorado.</p>',
 'Set artesanal de bautismo con libreta, llavero CTR, marcador y tarjeta personalizada.',
 1490.00, 10, 'active', 1,
 'https://picsum.photos/seed/baptism-gift/600/600',
 'Set de bautismo artesanal – TiendaMoroni',
 'Regalo artesanal completo para el día del bautismo. Hecho con amor por hermanos de la comunidad.'
),
(8, 1, 1,
 'Funda acolchada para escrituras — arpillera bordada',
 'funda-escrituras-arpillera',
 '<p>Funda protectora para escrituras confeccionada en arpillera natural con bordado artesanal del Ángel Moroni en hilo dorado y azul. Cierre con botón de madera. Medidas para la edición combinada (escrituras cuádruple). Lavado a mano recomendado.</p>',
 'Funda en arpillera natural con Ángel Moroni bordado en hilo dorado y azul.',
 780.00, 18, 'active', 0,
 'https://picsum.photos/seed/scripture-bag/600/600',
 'Funda escrituras arpillera – TiendaMoroni',
 'Funda artesanal para escrituras con Ángel Moroni bordado. Hecha a mano en Uruguay.'
);

-- ── Product extra images ──────────────────────────────────────────────────────
INSERT INTO `product_images` (`product_id`, `image_url`, `sort_order`) VALUES
(1, 'https://picsum.photos/seed/book-cover-detail/600/600', 1),
(1, 'https://picsum.photos/seed/book-cover-open/600/600', 2),
(3, 'https://picsum.photos/seed/scripture-frame-detail/600/600', 1),
(4, 'https://picsum.photos/seed/aceitero-detail/600/600', 1),
(7, 'https://picsum.photos/seed/baptism-set-open/600/600', 1);

SET foreign_key_checks = 1;
