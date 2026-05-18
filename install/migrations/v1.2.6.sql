-- v1.2.6: Link users to contacts/members.
-- Adds contact_id FK on users. Backfills existing users by creating a contact
-- record from their name/email if one does not already exist for that email.

ALTER TABLE users ADD COLUMN contact_id INT UNSIGNED DEFAULT NULL AFTER org_id;
ALTER TABLE users ADD KEY idx_users_contact (contact_id);
ALTER TABLE users ADD CONSTRAINT fk_users_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL;

-- Create contact records for any users that lack one (matched by email within org).
INSERT INTO contacts (org_id, first_name, last_name, email, created_at)
SELECT u.org_id,
       SUBSTRING_INDEX(u.name, ' ', 1),
       CASE WHEN LOCATE(' ', u.name) > 0
            THEN SUBSTRING(u.name, LOCATE(' ', u.name) + 1)
            ELSE '' END,
       u.email,
       u.created_at
FROM users u
WHERE u.contact_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM contacts c
      WHERE c.org_id = u.org_id AND c.email = u.email
  );

-- Link users to their contact records.
UPDATE users u
JOIN contacts c ON c.org_id = u.org_id AND c.email = u.email
SET u.contact_id = c.id
WHERE u.contact_id IS NULL;
