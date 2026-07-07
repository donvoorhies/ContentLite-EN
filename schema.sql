-- schema.sql
-- Database install script for ContentLite (EN)
-- Run this after selecting the target database, e.g.:
--   USE your_database_name;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS cms_content (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title     VARCHAR(255) NOT NULL,
  content   TEXT NOT NULL,
  status    ENUM('published', 'draft') NOT NULL DEFAULT 'draft',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cms_content_status_updated_at (status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_albums (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  description TEXT,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_albums_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  album_id    INT UNSIGNED NOT NULL,
  filename     VARCHAR(255) NOT NULL,
  title       VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  sort_order   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gallery_images_album_sort (album_id, sort_order, id),
  CONSTRAINT fk_gallery_images_album
    FOREIGN KEY (album_id) REFERENCES gallery_albums(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
