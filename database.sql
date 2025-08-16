-- =========================================================
-- FASTCORE – Fresh Schema (MySQL 5.7+/8.0+, MariaDB 10.3+)
-- - Uses utf8mb4 + sane numeric types for money/rates
-- - Keeps original column names (compatibility)
-- - No foreign keys (to match original app expectations)
-- =========================================================

SET NAMES utf8mb4;

-- Drop existing (safe order not required without FKs)
DROP TABLE IF EXISTS
  `db_store`,
  `db_tarif`,
  `db_percent`,
  `db_conf`,
  `db_news`,
  `db_reviews`,
  `db_bonus`,
  `db_insert`,
  `db_payout`,
  `db_stats`,
  `db_restore`,
  `db_purse`,
  `db_users`;

-- =========================
-- Users
-- =========================
CREATE TABLE `db_users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(32) NOT NULL,
  `email` VARCHAR(254) NOT NULL,
  `pass` VARCHAR(255) NOT NULL,                -- for password_hash()
  `reg` INT UNSIGNED NOT NULL,                 -- UNIX time
  `auth` INT UNSIGNED NOT NULL DEFAULT 0,      -- last auth time
  `ban` TINYINT NOT NULL DEFAULT 0,
  `ip` INT UNSIGNED NOT NULL DEFAULT 0,        -- IPv4, use VARBINARY(16) if you need IPv6 later
  `money_b` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `money_p` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sum_in` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sum_out` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `refsite` VARCHAR(60) NOT NULL DEFAULT '',
  `referer` VARCHAR(30) NOT NULL DEFAULT '',
  `rid` INT NOT NULL DEFAULT 0,
  `refs` INT NOT NULL DEFAULT 0,
  `income` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `ref_to` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `point` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `rating` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `role` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_login` (`login`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `ix_users_rid` (`rid`),
  KEY `ix_users_referer` (`referer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Wallets / Purses
-- =========================
CREATE TABLE `db_purse` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uid` INT NOT NULL DEFAULT 0,
  `payeer` VARCHAR(50) NOT NULL DEFAULT '0',
  `qiwi` VARCHAR(50) NOT NULL DEFAULT '0',
  `yandex` VARCHAR(50) NOT NULL DEFAULT '0',
  `pin` INT NOT NULL DEFAULT 0,
  `count` INT NOT NULL DEFAULT 0,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purse_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Global Config
-- =========================
CREATE TABLE `db_conf` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `coint` INT NOT NULL,
  `bounty` INT NOT NULL,
  `p_sell` INT NOT NULL,
  `p_swap` INT NOT NULL,
  `min_s` DECIMAL(10,2) NOT NULL,
  `acc_pay` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `db_conf` (`id`,`coint`,`bounty`,`p_sell`,`p_swap`,`min_s`,`acc_pay`) VALUES
(1, 1, 10, 90, 5, 0.10, 10);

-- =========================
-- Percent Brackets
-- =========================
CREATE TABLE `db_percent` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type` INT NOT NULL,
  `sum_a` INT NOT NULL,
  `sum_b` INT NOT NULL,
  `sum_x` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_percent_type_range` (`type`,`sum_a`,`sum_b`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `db_percent` (`id`,`type`,`sum_a`,`sum_b`,`sum_x`) VALUES
(1, 1, 1,     499,   0.10),
(2, 1, 500,   999,   0.20),
(3, 1, 1000,  4999,  0.30),
(4, 1, 5000,  9999,  0.40),
(5, 1, 10000, 99999, 0.50);

-- =========================
-- Tariffs
-- =========================
CREATE TABLE `db_tarif` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(30) NOT NULL,
  `img` INT NOT NULL,
  `speed` DECIMAL(10,4) NOT NULL,
  `price` INT NOT NULL,
  `period` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tarif_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `db_tarif` (`id`,`title`,`img`,`speed`,`price`,`period`) VALUES
(1, 'тест-1', 1, 0.0034, 10,   90),
(2, 'тест-2', 2, 0.0180, 50,   90),
(3, 'тест-3', 3, 0.0930, 250,  90),
(4, 'тест-4', 4, 0.3800, 1000, 90),
(5, 'тест-5', 5, 1.2000, 3000, 90),
(6, 'тест-6', 6, 3.2200, 7500, 90);

-- =========================
-- User Store (purchased items/tariffs)
-- =========================
CREATE TABLE `db_store` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uid` INT NOT NULL,
  `title` VARCHAR(30) NOT NULL,
  `tarif` INT NOT NULL,
  `level` INT NOT NULL,
  `speed` DECIMAL(10,4) NOT NULL,
  `add` INT UNSIGNED NOT NULL,    -- start time
  `end` INT UNSIGNED NOT NULL,    -- expire time
  `last` INT UNSIGNED NOT NULL,   -- last accrual
  PRIMARY KEY (`id`),
  KEY `ix_store_uid` (`uid`),
  KEY `ix_store_tarif` (`tarif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- News
-- =========================
CREATE TABLE `db_news` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `text` TEXT NOT NULL,
  `cat` INT NOT NULL DEFAULT 0,
  `count` INT NOT NULL DEFAULT 0,
  `add` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_news_cat` (`cat`),
  KEY `ix_news_add` (`add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Reviews
-- =========================
CREATE TABLE `db_reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(25) NOT NULL,
  `uid` INT NOT NULL,
  `text` TEXT NOT NULL,
  `img` INT NOT NULL DEFAULT 0,
  `like` INT UNSIGNED NOT NULL DEFAULT 0,
  `hide` INT UNSIGNED NOT NULL DEFAULT 0,
  `date` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_reviews_uid` (`uid`),
  KEY `ix_reviews_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Bonuses
-- =========================
CREATE TABLE `db_bonus` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uid` INT NOT NULL DEFAULT 0,
  `login` VARCHAR(50) NOT NULL,
  `sum` DECIMAL(10,2) NOT NULL,
  `add` INT UNSIGNED NOT NULL DEFAULT 0,
  `del` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_bonus_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Deposits (inserts)
-- =========================
CREATE TABLE `db_insert` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uid` INT NOT NULL,
  `login` VARCHAR(50) NOT NULL,
  `sum` DECIMAL(12,2) NOT NULL,
  `sum_x` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sys` VARCHAR(20) NOT NULL,
  `status` TINYINT NOT NULL,                   -- 0=pending,1=paid, etc.
  `operation_id` BIGINT UNSIGNED NOT NULL,     -- gateway txn id
  `add` INT UNSIGNED NOT NULL DEFAULT 0,
  `end` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_insert_operation` (`operation_id`),
  KEY `ix_insert_uid_status` (`uid`,`status`),
  KEY `ix_insert_add` (`add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Payouts (withdrawals)
-- =========================
CREATE TABLE `db_payout` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uid` INT NOT NULL,
  `login` VARCHAR(30) NOT NULL,
  `purse` VARCHAR(64) NOT NULL,
  `sum` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` TINYINT NOT NULL DEFAULT 0,
  `sys` TINYINT NOT NULL,
  `add` INT UNSIGNED NOT NULL DEFAULT 0,
  `del` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_payout_uid_status` (`uid`,`status`),
  KEY `ix_payout_add` (`add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- Global Stats
-- =========================
CREATE TABLE `db_stats` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `users` INT NOT NULL DEFAULT 0,
  `inserts` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payments` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `db_stats` (`id`,`users`,`inserts`,`payments`) VALUES
(1, 0, 0.00, 0.00);

-- =========================
-- Password Restore
-- =========================
CREATE TABLE `db_restore` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(50) NOT NULL,
  `ip` INT UNSIGNED NOT NULL,
  `date_add` INT UNSIGNED NOT NULL,
  `date_del` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_restore_email` (`email`),
  KEY `ix_restore_date_del` (`date_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- End of schema
-- =========================================================
