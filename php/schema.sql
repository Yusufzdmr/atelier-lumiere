-- Atelier Lumière – MariaDB-Schema (ALL-INKL)
--
-- Bewusst dieselbe Struktur wie in der Next.js-Fassung: die Inhalte liegen als
-- JSON-Dokument, nicht in dutzenden Spalten. Damit lassen sich die bestehenden
-- Daten unverändert übernehmen (bin/import.php) und die Portierung bleibt eine
-- Übersetzung der Logik statt einer Datenmigration.
--
-- Einspielen: phpMyAdmin (KAS) → Importieren, oder
--   mysql -h <host> -u <user> -p <db> < schema.sql

SET NAMES utf8mb4;

-- Redaktionelle Inhalte: ein einziger Datensatz
CREATE TABLE IF NOT EXISTS site_content (
  id         TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zugangsdaten fremder Dienste (PayPal, Google, Meta, weitere Schlüssel)
CREATE TABLE IF NOT EXISTS integrations (
  id         TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kundenakte: Zugang, Auftragsdaten, Gutschein
CREATE TABLE IF NOT EXISTS customers (
  code       VARCHAR(64) NOT NULL PRIMARY KEY,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bildergalerie des Kunden
CREATE TABLE IF NOT EXISTS galleries (
  code       VARCHAR(64) NOT NULL PRIMARY KEY,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auswahl fürs Album: eine pro Galerie, neue Einsendung ersetzt die alte
CREATE TABLE IF NOT EXISTS selections (
  code VARCHAR(64) NOT NULL PRIMARY KEY,
  data LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invitations (
  slug       VARCHAR(96) NOT NULL PRIMARY KEY,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persönlich adressierte Fassungen einer Einladung.
-- Die Einladung bleibt EIN Datensatz; hier steht nur, wer angesprochen wird und
-- unter welcher Adresse. Sonst wären 200 Gäste 200 Kopien derselben Karte.
CREATE TABLE IF NOT EXISTS invite_guests (
  slug       VARCHAR(96) NOT NULL,
  token      VARCHAR(96) NOT NULL,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (slug, token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zwischenstand des Einladungs-Assistenten
CREATE TABLE IF NOT EXISTS invite_drafts (
  token      VARCHAR(64) NOT NULL PRIMARY KEY,
  data       LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rsvps (
  id   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(96) NOT NULL,
  data LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX rsvps_slug_idx (slug, at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bremse gegen durchprobierte Passwoerter und Gutscheincodes.
-- Muss ausserhalb der Sitzung liegen: wer das Sitzungscookie wegwirft,
-- bekaeme sonst mit jedem Versuch einen frischen Zaehler.
-- Gespeichert wird nur ein Streuwert der Absenderadresse, nie die Adresse selbst.
CREATE TABLE IF NOT EXISTS throttle (
  bucket VARCHAR(190) NOT NULL PRIMARY KEY,
  hits   INT UNSIGNED NOT NULL DEFAULT 0,
  until  DATETIME NOT NULL,
  INDEX throttle_until_idx (until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anfragen aus dem Kontaktformular
CREATE TABLE IF NOT EXISTS leads (
  id   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  data LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug    VARCHAR(96) NOT NULL,
  orderid VARCHAR(64) NOT NULL,
  data    LONGTEXT NOT NULL CHECK (JSON_VALID(data)),
  at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX payments_slug_idx (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Panelin hangi sekmelerinde çalışıldığını sayar.
-- „Sık kullanılanlar" listesini (son 30 gün) buradan üretiriz.
CREATE TABLE IF NOT EXISTS admin_usage (
  tab      VARCHAR(64) NOT NULL PRIMARY KEY,
  hits     INT UNSIGNED NOT NULL DEFAULT 0,
  last_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
