-- ── Migración: columna wa_in_catalog en products ──────────────────────────────
-- Permite marcar qué productos están subidos y aprobados en Meta Commerce Manager.
-- Solo los marcados con wa_in_catalog = 1 se envían vía product_list de WhatsApp.
--
-- EJECUTAR UNA SOLA VEZ en producción:
--   mysql -u USER -p DB_NAME < wa_catalog_column.sql
--
-- DESPUÉS DE EJECUTAR:
--   1. Verificar en Commerce Manager qué products están aprobados.
--   2. Marcarlos con:
--        UPDATE products SET wa_in_catalog = 1 WHERE id IN (11, 13, 14, ...);
--   3. Los productos sin marcar seguirán apareciendo en el fallback (imágenes).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `wa_in_catalog` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Producto subido y aprobado en Meta Commerce Manager'
    AFTER `featured`;

-- Marcar como activos en catálogo todos los productos activos actuales.
-- Ajustar este listado según lo que aparece en Commerce Manager.
-- UPDATE products SET wa_in_catalog = 1 WHERE status = 'active' AND id IN (11, 13, 14, 21);
