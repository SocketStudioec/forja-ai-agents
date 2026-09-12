-- =====================================================================
--  Plantillas de pago.
--
--  Un agente o una habilidad de pago se muestra en el catálogo con su
--  nombre y una descripción de lo que hace, pero NO se puede descargar ni
--  previsualizar su contenido. Sólo un administrador puede marcarlas.
--
--  Aplicar con:  php scripts/migrate.php database/migrations/003_paid_tier.sql
-- =====================================================================

ALTER TABLE agents
    ADD COLUMN tier        ENUM('free','paid') NOT NULL DEFAULT 'free' AFTER visibility,
    ADD COLUMN price_label VARCHAR(60)  NULL AFTER tier,
    ADD COLUMN contact_url VARCHAR(255) NULL AFTER price_label,
    ADD COLUMN teaser      TEXT         NULL AFTER contact_url;

ALTER TABLE skills
    ADD COLUMN tier        ENUM('free','paid') NOT NULL DEFAULT 'free' AFTER visibility,
    ADD COLUMN price_label VARCHAR(60)  NULL AFTER tier,
    ADD COLUMN contact_url VARCHAR(255) NULL AFTER price_label,
    ADD COLUMN teaser      TEXT         NULL AFTER contact_url;

ALTER TABLE agents ADD INDEX idx_agents_tier (tier);
ALTER TABLE skills ADD INDEX idx_skills_tier (tier);
