ALTER TABLE users ADD COLUMN must_change_password BOOLEAN NOT NULL DEFAULT FALSE;
CREATE UNIQUE INDEX users_email_case_insensitive_idx ON users (lower(email));
