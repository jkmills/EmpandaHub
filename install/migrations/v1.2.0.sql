-- v1.2.0: Engagement Score + Document Library

-- Engagement Score on contacts
ALTER TABLE contacts
  ADD COLUMN engagement_score    TINYINT UNSIGNED DEFAULT NULL,
  ADD COLUMN engagement_score_at DATETIME DEFAULT NULL;

-- Document Library
CREATE TABLE IF NOT EXISTS doc_categories (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id         INT UNSIGNED NOT NULL,
  parent_id      INT UNSIGNED DEFAULT NULL,
  name           VARCHAR(100) NOT NULL,
  min_visibility ENUM('all_staff','staff_only','admin_only','super_admin_only') DEFAULT 'all_staff',
  sort_order     INT DEFAULT 0,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doccat_org    FOREIGN KEY (org_id)    REFERENCES organizations(id),
  CONSTRAINT fk_doccat_parent FOREIGN KEY (parent_id) REFERENCES doc_categories(id) ON DELETE SET NULL,
  KEY idx_doccat_org (org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id          INT UNSIGNED NOT NULL,
  category_id     INT UNSIGNED DEFAULT NULL,
  title           VARCHAR(200) NOT NULL,
  description     TEXT DEFAULT NULL,
  filename        VARCHAR(255) NOT NULL,
  file_path       VARCHAR(500) NOT NULL,
  file_size       INT UNSIGNED NOT NULL,
  mime_type       VARCHAR(100) NOT NULL,
  visibility      ENUM('all_staff','staff_only','admin_only','super_admin_only') DEFAULT 'all_staff',
  tags            VARCHAR(500) DEFAULT NULL,
  uploaded_by     INT UNSIGNED NOT NULL,
  current_version TINYINT UNSIGNED DEFAULT 1,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_org  FOREIGN KEY (org_id)      REFERENCES organizations(id),
  CONSTRAINT fk_doc_cat  FOREIGN KEY (category_id) REFERENCES doc_categories(id) ON DELETE SET NULL,
  KEY idx_doc_org        (org_id),
  KEY idx_doc_visibility (org_id, visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_versions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_id    INT UNSIGNED NOT NULL,
  version_number TINYINT UNSIGNED NOT NULL,
  filename       VARCHAR(255) NOT NULL,
  file_path      VARCHAR(500) NOT NULL,
  file_size      INT UNSIGNED NOT NULL,
  uploaded_by    INT UNSIGNED NOT NULL,
  uploaded_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_docver_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  KEY idx_docver_doc (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_share_links (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id      INT UNSIGNED NOT NULL,
  document_id INT UNSIGNED NOT NULL,
  token       VARCHAR(64) NOT NULL,
  expires_at  DATETIME DEFAULT NULL,
  is_active   TINYINT(1) DEFAULT 1,
  created_by  INT UNSIGNED NOT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_share_token (token),
  CONSTRAINT fk_share_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  KEY idx_share_org (org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_link_access_log (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  share_link_id INT UNSIGNED NOT NULL,
  accessed_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  ip_address    VARCHAR(45) DEFAULT NULL,
  CONSTRAINT fk_accesslog_link FOREIGN KEY (share_link_id) REFERENCES document_share_links(id) ON DELETE CASCADE,
  KEY idx_accesslog_link (share_link_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
