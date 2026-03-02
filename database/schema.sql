-- ============================================================
-- Advanced URL Shortener — Database Schema
-- Engine: InnoDB (transactional, FK support)
-- Charset: utf8mb4 (full Unicode)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `url_shortener`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `url_shortener`;

-- ============================================================
-- Table: links
-- Stores every shortened URL with metadata & expiry controls.
-- ============================================================
DROP TABLE IF EXISTS `click_logs`;
DROP TABLE IF EXISTS `links`;

CREATE TABLE `links` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_url`    TEXT            NOT NULL,
  `short_code`      VARCHAR(16)     NOT NULL,
  `click_count`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `max_clicks`      BIGINT UNSIGNED DEFAULT NULL  COMMENT 'Click-limit expiry; NULL = unlimited',
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `expires_at`      DATETIME        DEFAULT NULL  COMMENT 'Date-based expiry; NULL = never',
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Short-code look-ups must be O(1)
  UNIQUE  KEY `uq_short_code` (`short_code`),

  -- Dashboard queries: active links, expired links, ordering
  KEY `idx_is_active`   (`is_active`),
  KEY `idx_expires_at`  (`expires_at`),
  KEY `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: click_logs
-- Append-only ledger of every redirect event.
-- Designed to grow large — indexed for analytic queries.
-- ============================================================
CREATE TABLE `click_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `link_id`     BIGINT UNSIGNED NOT NULL,
  `ip_address`  VARCHAR(45)     NOT NULL  COMMENT 'IPv4 or IPv6',
  `user_agent`  TEXT            DEFAULT NULL,
  `referer`     TEXT            DEFAULT NULL,
  `clicked_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- FK to links.id — cascade deletes so orphan rows never linger
  CONSTRAINT `fk_click_logs_link`
    FOREIGN KEY (`link_id`) REFERENCES `links` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  -- Analytics queries: "clicks for link X", "clicks in date range"
  KEY `idx_link_id`    (`link_id`),
  KEY `idx_clicked_at` (`clicked_at`),

  -- Composite: per-link time-series queries
  KEY `idx_link_clicked` (`link_id`, `clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
