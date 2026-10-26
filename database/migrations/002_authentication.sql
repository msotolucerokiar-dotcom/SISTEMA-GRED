CREATE TABLE login_attempts (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email VARCHAR(254) NOT NULL,
    ip_address INET NOT NULL,
    attempted_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    was_successful BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE INDEX login_attempts_window_idx ON login_attempts (email, ip_address, attempted_at DESC);
CREATE INDEX users_role_active_idx ON users (role_id, is_active);
