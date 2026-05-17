-- EmpandaHub schema — MySQL 8 / InnoDB / utf8mb4
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS organizations (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(255) NOT NULL,
  logo            VARCHAR(255) DEFAULT NULL,
  primary_color   VARCHAR(7)   DEFAULT '#2563eb',
  timezone        VARCHAR(64)  DEFAULT 'America/New_York',
  fiscal_year_start TINYINT UNSIGNED DEFAULT 1,
  config_json     JSON         DEFAULT NULL,
  created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  email        VARCHAR(255) NOT NULL,
  password     VARCHAR(255) NOT NULL,
  role         ENUM('super_admin','admin','staff','volunteer','readonly') NOT NULL DEFAULT 'readonly',
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  invite_token VARCHAR(64)  DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_email (org_id, email),
  CONSTRAINT fk_users_org FOREIGN KEY (org_id) REFERENCES organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contacts (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id         INT UNSIGNED NOT NULL,
  first_name     VARCHAR(100) NOT NULL,
  last_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(255) DEFAULT NULL,
  phone          VARCHAR(50)  DEFAULT NULL,
  address        VARCHAR(255) DEFAULT NULL,
  city           VARCHAR(100) DEFAULT NULL,
  state          VARCHAR(100) DEFAULT NULL,
  zip            VARCHAR(20)  DEFAULT NULL,
  country        VARCHAR(100) DEFAULT 'US',
  merged_into_id INT UNSIGNED DEFAULT NULL,
  created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_contacts_org FOREIGN KEY (org_id) REFERENCES organizations(id),
  KEY idx_contacts_org   (org_id),
  KEY idx_contacts_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_tags (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id     INT UNSIGNED NOT NULL,
  contact_id INT UNSIGNED NOT NULL,
  tag        VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ctags_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  KEY idx_ctags_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS board_positions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id      INT UNSIGNED NOT NULL,
  contact_id  INT UNSIGNED NOT NULL,
  title       VARCHAR(100) NOT NULL,
  committee   VARCHAR(100) DEFAULT NULL,
  start_date  DATE         NOT NULL,
  end_date    DATE         DEFAULT NULL,
  notes       VARCHAR(255) DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bp_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  CONSTRAINT fk_bp_org     FOREIGN KEY (org_id)     REFERENCES organizations(id),
  KEY idx_bp_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_history (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id        INT UNSIGNED NOT NULL,
  membership_id INT UNSIGNED NOT NULL,
  contact_id    INT UNSIGNED NOT NULL,
  event_type    ENUM('created','renewed','status_change','tier_change','expired','cancelled') NOT NULL,
  old_value     VARCHAR(255) DEFAULT NULL,
  new_value     VARCHAR(255) DEFAULT NULL,
  notes         VARCHAR(255) DEFAULT NULL,
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mh_membership FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
  CONSTRAINT fk_mh_contact    FOREIGN KEY (contact_id)    REFERENCES contacts(id),
  KEY idx_mh_membership (membership_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_notes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id     INT UNSIGNED NOT NULL,
  contact_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  body       TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cnotes_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  KEY idx_cnotes_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS membership_tiers (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id           INT UNSIGNED NOT NULL,
  name             VARCHAR(100) NOT NULL,
  description      TEXT         DEFAULT NULL,
  parent_tier_id   INT UNSIGNED DEFAULT NULL,
  billing_cycle    ENUM('monthly','quarterly','annual','lifetime') NOT NULL DEFAULT 'annual',
  amount           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  grace_period_days TINYINT UNSIGNED DEFAULT 30,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order       TINYINT UNSIGNED DEFAULT 0,
  created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tiers_org    FOREIGN KEY (org_id)         REFERENCES organizations(id),
  CONSTRAINT fk_tiers_parent FOREIGN KEY (parent_tier_id) REFERENCES membership_tiers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id     INT UNSIGNED NOT NULL,
  contact_id INT UNSIGNED NOT NULL,
  tier_id    INT UNSIGNED NOT NULL,
  start_date DATE         NOT NULL,
  end_date   DATE         DEFAULT NULL,
  status     ENUM('active','grace','expired','cancelled','lifetime') NOT NULL DEFAULT 'active',
  notes      TEXT         DEFAULT NULL,
  created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mem_contact FOREIGN KEY (contact_id) REFERENCES contacts(id),
  CONSTRAINT fk_mem_tier    FOREIGN KEY (tier_id)    REFERENCES membership_tiers(id),
  KEY idx_mem_contact (contact_id),
  KEY idx_mem_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dues_payments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id        INT UNSIGNED NOT NULL,
  membership_id INT UNSIGNED NOT NULL,
  contact_id    INT UNSIGNED NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  due_date      DATE          DEFAULT NULL,
  paid_on       DATE          NOT NULL,
  method        VARCHAR(50)   DEFAULT NULL,
  reference     VARCHAR(100)  DEFAULT NULL,
  note          VARCHAR(255)  DEFAULT NULL,
  receipt_sent  TINYINT(1)    DEFAULT 0,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dues_membership FOREIGN KEY (membership_id) REFERENCES memberships(id),
  CONSTRAINT fk_dues_contact    FOREIGN KEY (contact_id)    REFERENCES contacts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  description  TEXT         DEFAULT NULL,
  goal_amount  DECIMAL(10,2) DEFAULT NULL,
  start_date   DATE          DEFAULT NULL,
  end_date     DATE          DEFAULT NULL,
  is_active    TINYINT(1)    DEFAULT 1,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_campaigns_org FOREIGN KEY (org_id) REFERENCES organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id         INT UNSIGNED  NOT NULL,
  contact_id     INT UNSIGNED  DEFAULT NULL,
  campaign_id    INT UNSIGNED  DEFAULT NULL,
  amount         DECIMAL(10,2) NOT NULL,
  donated_on     DATE          NOT NULL,
  is_recurring   TINYINT(1)    DEFAULT 0,
  recur_interval ENUM('monthly','quarterly','annual') DEFAULT NULL,
  is_anonymous   TINYINT(1)    DEFAULT 0,
  method         VARCHAR(50)   DEFAULT NULL,
  note           VARCHAR(255)  DEFAULT NULL,
  receipt_sent   TINYINT(1)    DEFAULT 0,
  fiscal_year    SMALLINT UNSIGNED DEFAULT NULL,
  created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_don_contact  FOREIGN KEY (contact_id)  REFERENCES contacts(id)  ON DELETE SET NULL,
  CONSTRAINT fk_don_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
  KEY idx_don_contact  (contact_id),
  KEY idx_don_campaign (campaign_id),
  KEY idx_don_date     (donated_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS volunteers (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED NOT NULL,
  contact_id   INT UNSIGNED NOT NULL,
  skills       TEXT         DEFAULT NULL,
  availability TEXT         DEFAULT NULL,
  is_active    TINYINT(1)   DEFAULT 1,
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vol_contact FOREIGN KEY (contact_id) REFERENCES contacts(id),
  KEY idx_vol_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS volunteer_shifts (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id          INT UNSIGNED  NOT NULL,
  title           VARCHAR(255)  NOT NULL,
  description     TEXT          DEFAULT NULL,
  shift_date      DATE          NOT NULL,
  start_time      TIME          DEFAULT NULL,
  end_time        TIME          DEFAULT NULL,
  max_volunteers  SMALLINT UNSIGNED DEFAULT NULL,
  created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shifts_org FOREIGN KEY (org_id) REFERENCES organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS volunteer_hours (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  org_id        INT UNSIGNED  NOT NULL,
  volunteer_id  INT UNSIGNED  NOT NULL,
  shift_id      INT UNSIGNED  DEFAULT NULL,
  hours         DECIMAL(5,2)  NOT NULL,
  activity_date DATE          NOT NULL,
  description   VARCHAR(255)  DEFAULT NULL,
  status        ENUM('pending','approved','rejected') DEFAULT 'pending',
  approved_by   INT UNSIGNED  DEFAULT NULL,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_hours_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteers(id),
  CONSTRAINT fk_hours_shift     FOREIGN KEY (shift_id)     REFERENCES volunteer_shifts(id) ON DELETE SET NULL,
  KEY idx_hours_vol (volunteer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED  NOT NULL,
  title        VARCHAR(255)  NOT NULL,
  description  TEXT          DEFAULT NULL,
  event_date   DATE          NOT NULL,
  start_time   TIME          DEFAULT NULL,
  end_time     TIME          DEFAULT NULL,
  location     VARCHAR(255)  DEFAULT NULL,
  capacity     SMALLINT UNSIGNED DEFAULT NULL,
  price        DECIMAL(10,2) DEFAULT 0.00,
  is_published TINYINT(1)    DEFAULT 0,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_events_org FOREIGN KEY (org_id) REFERENCES organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
  id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  org_id        INT UNSIGNED  NOT NULL,
  event_id      INT UNSIGNED  NOT NULL,
  contact_id    INT UNSIGNED  DEFAULT NULL,
  name          VARCHAR(255)  NOT NULL,
  email         VARCHAR(255)  DEFAULT NULL,
  status        ENUM('registered','waitlist','cancelled','attended') DEFAULT 'registered',
  amount_paid   DECIMAL(10,2) DEFAULT 0.00,
  refund_note   VARCHAR(255)  DEFAULT NULL,
  checked_in_at DATETIME      DEFAULT NULL,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(id),
  CONSTRAINT fk_reg_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
  KEY idx_reg_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funders (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id       INT UNSIGNED NOT NULL,
  name         VARCHAR(255) NOT NULL,
  contact_name VARCHAR(255) DEFAULT NULL,
  email        VARCHAR(255) DEFAULT NULL,
  phone        VARCHAR(50)  DEFAULT NULL,
  website      VARCHAR(255) DEFAULT NULL,
  notes        TEXT         DEFAULT NULL,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_funders_org FOREIGN KEY (org_id) REFERENCES organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grants` (
  id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  org_id           INT UNSIGNED  NOT NULL,
  funder_id        INT UNSIGNED  NOT NULL,
  title            VARCHAR(255)  NOT NULL,
  description      TEXT          DEFAULT NULL,
  status           ENUM('prospect','drafting','submitted','awarded','declined') DEFAULT 'prospect',
  amount_requested DECIMAL(12,2) DEFAULT NULL,
  amount_awarded   DECIMAL(12,2) DEFAULT NULL,
  deadline_date    DATE          DEFAULT NULL,
  submitted_date   DATE          DEFAULT NULL,
  awarded_date     DATE          DEFAULT NULL,
  period_start     DATE          DEFAULT NULL,
  period_end       DATE          DEFAULT NULL,
  notes            TEXT          DEFAULT NULL,
  created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grants_funder FOREIGN KEY (funder_id) REFERENCES funders(id),
  KEY idx_grants_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grant_reports (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id         INT UNSIGNED NOT NULL,
  grant_id       INT UNSIGNED NOT NULL,
  title          VARCHAR(255) NOT NULL,
  due_date       DATE         NOT NULL,
  submitted_date DATE         DEFAULT NULL,
  notes          TEXT         DEFAULT NULL,
  created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_greports_grant FOREIGN KEY (grant_id) REFERENCES `grants`(id) ON DELETE CASCADE,
  KEY idx_greports_grant (grant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  org_id           INT UNSIGNED  NOT NULL,
  source_type      ENUM('donation','dues_payment','event_registration','grant') NOT NULL,
  source_id        INT UNSIGNED  NOT NULL,
  contact_id       INT UNSIGNED  DEFAULT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  direction        ENUM('credit','debit') NOT NULL DEFAULT 'credit',
  category         VARCHAR(100)  DEFAULT NULL,
  description      VARCHAR(255)  DEFAULT NULL,
  transaction_date DATE          NOT NULL,
  fiscal_year      SMALLINT UNSIGNED DEFAULT NULL,
  created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_org FOREIGN KEY (org_id) REFERENCES organizations(id),
  KEY idx_tx_source   (source_type, source_id),
  KEY idx_tx_date     (transaction_date),
  KEY idx_tx_category (category),
  KEY idx_tx_fiscal   (org_id, fiscal_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  org_id      INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED DEFAULT NULL,
  action      VARCHAR(100) NOT NULL,
  target_type VARCHAR(100) DEFAULT NULL,
  target_id   INT UNSIGNED DEFAULT NULL,
  details     TEXT         DEFAULT NULL,
  ip          VARCHAR(45)  DEFAULT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_audit_org     (org_id),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
