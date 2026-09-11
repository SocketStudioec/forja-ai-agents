-- =====================================================================
--  Un agente puede pertenecer a varias categorías.
--
--  `agents.category_id` se conserva como categoría PRINCIPAL: es la que se
--  muestra como insignia y la que ordena. La tabla pivote guarda todas,
--  incluida la principal, para que una sola consulta resuelva el filtrado.
-- =====================================================================

CREATE TABLE IF NOT EXISTS agent_categories (
    agent_id    INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    is_primary  TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (agent_id, category_id),
    KEY idx_ac_category (category_id),
    CONSTRAINT fk_ac_agent    FOREIGN KEY (agent_id)    REFERENCES agents (id)     ON DELETE CASCADE,
    CONSTRAINT fk_ac_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los agentes que ya existían pasan a tener su categoría actual en el pivote.
INSERT IGNORE INTO agent_categories (agent_id, category_id, is_primary)
SELECT id, category_id, 1 FROM agents WHERE category_id IS NOT NULL;
