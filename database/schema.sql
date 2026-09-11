-- =====================================================================
--  SOCKET STUDIO AI SKILLS LIBRARY — Esquema de base de datos
--  MySQL 8 / MariaDB 10.4+  ·  utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(80)  NOT NULL,
    lastname        VARCHAR(80)  NULL,
    username        VARCHAR(60)  NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin','user') NOT NULL DEFAULT 'user',
    status          ENUM('active','suspended','pending_deletion') NOT NULL DEFAULT 'active',
    avatar          VARCHAR(255) NULL,
    bio             VARCHAR(280) NULL,
    last_login_at   DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(90)  NOT NULL,
    slug        VARCHAR(110) NOT NULL,
    description VARCHAR(255) NULL,
    icon        VARCHAR(40)  NULL,
    position    SMALLINT     NOT NULL DEFAULT 0,
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- skills
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NULL,
    name              VARCHAR(140) NOT NULL,
    slug              VARCHAR(170) NOT NULL,
    short_description VARCHAR(255) NOT NULL,
    description       MEDIUMTEXT   NULL,      -- cuerpo SKILL.md (markdown)
    category_id       INT UNSIGNED NULL,
    tags              VARCHAR(255) NULL,      -- lista separada por comas
    compatibility     VARCHAR(255) NOT NULL DEFAULT 'OpenClaw',
    version           VARCHAR(20)  NOT NULL DEFAULT '1.0.0',
    definition_json   MEDIUMTEXT   NULL,      -- definición JSON de la habilidad (override manual)
    status            ENUM('draft','pending','under_review','approved','rejected','published','archived')
                      NOT NULL DEFAULT 'draft',
    visibility        ENUM('public','private','unlisted') NOT NULL DEFAULT 'public',
    formats           VARCHAR(60)  NOT NULL DEFAULT 'md,txt,json,zip',
    featured          TINYINT(1)   NOT NULL DEFAULT 0,
    downloads         INT UNSIGNED NOT NULL DEFAULT 0,
    views             INT UNSIGNED NOT NULL DEFAULT 0,
    author_name       VARCHAR(120) NULL,      -- autor mostrado (envíos sin cuenta)
    author_email      VARCHAR(190) NULL,
    review_notes      TEXT         NULL,
    submission_id     INT UNSIGNED NULL,
    published_at      DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_skills_slug (slug),
    KEY idx_skills_user (user_id),
    KEY idx_skills_cat (category_id),
    KEY idx_skills_status_vis (status, visibility),
    KEY idx_skills_downloads (downloads),
    FULLTEXT KEY ft_skills (name, short_description, description, tags),
    CONSTRAINT fk_skills_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_skills_cat  FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- agents — "tienda de agentes": reglas en Markdown + habilidades en JSON
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agents (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NULL,
    name              VARCHAR(140) NOT NULL,
    slug              VARCHAR(170) NOT NULL,
    role_title        VARCHAR(140) NULL,      -- "Analista tributario", "Soporte N1"...
    short_description VARCHAR(255) NOT NULL,
    rules_md          MEDIUMTEXT   NOT NULL,  -- archivo de reglas que se descarga como .md
    system_prompt     TEXT         NULL,      -- instrucción de sistema resumida
    category_id       INT UNSIGNED NULL,
    tags              VARCHAR(255) NULL,
    compatibility     VARCHAR(255) NOT NULL DEFAULT 'OpenClaw',
    version           VARCHAR(20)  NOT NULL DEFAULT '1.0.0',
    accent            VARCHAR(20)  NULL,      -- clave de color de la tarjeta
    status            ENUM('draft','pending','under_review','approved','rejected','published','archived')
                      NOT NULL DEFAULT 'draft',
    visibility        ENUM('public','private','unlisted') NOT NULL DEFAULT 'public',
    featured          TINYINT(1)   NOT NULL DEFAULT 0,
    downloads         INT UNSIGNED NOT NULL DEFAULT 0,
    views             INT UNSIGNED NOT NULL DEFAULT 0,
    author_name       VARCHAR(120) NULL,
    author_email      VARCHAR(190) NULL,
    review_notes      TEXT         NULL,
    published_at      DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_agents_slug (slug),
    KEY idx_agents_status_vis (status, visibility),
    KEY idx_agents_user (user_id),
    FULLTEXT KEY ft_agents (name, role_title, short_description, tags),
    CONSTRAINT fk_agents_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_agents_cat  FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- agent_skills — habilidades que trae cada agente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS agent_skills (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    agent_id   INT UNSIGNED NOT NULL,
    skill_id   INT UNSIGNED NOT NULL,
    required   TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = base del agente, no se puede quitar
    position   SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_agent_skill (agent_id, skill_id),
    KEY idx_as_skill (skill_id),
    CONSTRAINT fk_as_agent FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE CASCADE,
    CONSTRAINT fk_as_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- skill_formats — archivos materializados por formato
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skill_formats (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    skill_id   INT UNSIGNED NOT NULL,
    format     VARCHAR(10)  NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    file_size  INT UNSIGNED NOT NULL DEFAULT 0,
    mime_type  VARCHAR(90)  NOT NULL DEFAULT 'text/plain',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_skill_format (skill_id, format),
    CONSTRAINT fk_formats_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- skill_versions — historial
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skill_versions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    skill_id    INT UNSIGNED NOT NULL,
    version     VARCHAR(20)  NOT NULL,
    description MEDIUMTEXT   NULL,
    changelog   VARCHAR(255) NULL,
    edited_by   INT UNSIGNED NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_versions_skill (skill_id),
    CONSTRAINT fk_versions_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- skill_submissions — envíos de visitantes sin cuenta
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skill_submissions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference     VARCHAR(24)  NOT NULL,      -- identificador público del envío
    kind          ENUM('skill','agent') NOT NULL DEFAULT 'skill',
    name          VARCHAR(120) NULL,          -- nombre del remitente
    email         VARCHAR(190) NOT NULL,
    skill_name    VARCHAR(140) NOT NULL,
    description   TEXT         NOT NULL,
    category_id   INT UNSIGNED NULL,
    compatibility VARCHAR(255) NOT NULL DEFAULT 'OpenClaw',
    comments      TEXT         NULL,
    file_path     VARCHAR(255) NULL,
    file_name     VARCHAR(190) NULL,
    file_size     INT UNSIGNED NOT NULL DEFAULT 0,
    content       MEDIUMTEXT   NULL,          -- contenido de texto extraído
    status        ENUM('pending','under_review','approved','rejected','published','archived')
                  NOT NULL DEFAULT 'pending',
    review_notes  TEXT         NULL,
    skill_id      INT UNSIGNED NULL,          -- skill creada al aprobar
    agent_id      INT UNSIGNED NULL,          -- agente creado al aprobar
    ip_hash       CHAR(64)     NULL,
    consent       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at   DATETIME     NULL,
    reviewed_by   INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_submissions_ref (reference),
    KEY idx_submissions_status (status),
    CONSTRAINT fk_sub_cat FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- downloads
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS downloads (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    skill_id   INT UNSIGNED NULL,
    agent_id   INT UNSIGNED NULL,
    user_id    INT UNSIGNED NULL,
    kind       ENUM('skill','agent','pack') NOT NULL DEFAULT 'skill',
    format     VARCHAR(10)  NOT NULL,
    ip_hash    CHAR(64)     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_downloads_skill (skill_id),
    KEY idx_downloads_agent (agent_id),
    KEY idx_downloads_date (created_at),
    CONSTRAINT fk_dl_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE,
    CONSTRAINT fk_dl_agent FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- favorites
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    skill_id   INT UNSIGNED NULL,
    agent_id   INT UNSIGNED NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fav_skill (user_id, skill_id),
    UNIQUE KEY uq_fav_agent (user_id, agent_id),
    CONSTRAINT fk_fav_user  FOREIGN KEY (user_id)  REFERENCES users (id)  ON DELETE CASCADE,
    CONSTRAINT fk_fav_skill FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_agent FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NULL,
    actor_label   VARCHAR(120) NULL,          -- "visitante", email, etc.
    action        VARCHAR(60)  NOT NULL,
    resource_type VARCHAR(40)  NOT NULL,
    resource_id   INT UNSIGNED NULL,
    metadata      TEXT         NULL,          -- JSON, nunca secretos
    ip_hash       CHAR(64)     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_date (created_at),
    KEY idx_audit_action (action),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- password_resets
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(190) NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reset_token (token_hash),
    KEY idx_reset_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- rate_limits — control de abuso (login, envíos, recuperación)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket     VARCHAR(60) NOT NULL,
    ip_hash    CHAR(64)    NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rl (bucket, ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- email_log — trazabilidad de correos del sistema (sin contenido sensible)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient  VARCHAR(190) NOT NULL,
    template   VARCHAR(60)  NOT NULL,
    subject    VARCHAR(190) NOT NULL,
    ok         TINYINT(1)   NOT NULL DEFAULT 0,
    error      VARCHAR(255) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
