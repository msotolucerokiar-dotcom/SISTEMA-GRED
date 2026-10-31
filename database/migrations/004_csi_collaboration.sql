CREATE TABLE user_permissions (
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permission_id SMALLINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    granted_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
    granted_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (user_id, permission_id)
);

CREATE TABLE user_permission_history (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permission_id SMALLINT NOT NULL REFERENCES permissions(id),
    actor_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(16) NOT NULL CHECK (action IN ('granted', 'revoked')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX user_permission_history_user_time_idx ON user_permission_history(user_id, created_at DESC);

CREATE TABLE request_tasks (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    request_id BIGINT NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
    intern_id BIGINT NOT NULL REFERENCES users(id),
    responsible_id BIGINT NOT NULL REFERENCES users(id),
    delegated_by BIGINT NOT NULL REFERENCES users(id),
    instructions TEXT NOT NULL CHECK (length(trim(instructions)) BETWEEN 5 AND 2000),
    work_note TEXT CHECK (work_note IS NULL OR length(trim(work_note)) BETWEEN 5 AND 5000),
    state VARCHAR(16) NOT NULL DEFAULT 'assigned' CHECK (state IN ('assigned', 'submitted', 'approved', 'cancelled')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    submitted_at TIMESTAMPTZ,
    approved_at TIMESTAMPTZ,
    approved_by BIGINT REFERENCES users(id),
    cancelled_at TIMESTAMPTZ
);
CREATE UNIQUE INDEX request_tasks_one_open_per_intern_idx ON request_tasks(request_id, intern_id) WHERE state IN ('assigned', 'submitted');
CREATE INDEX request_tasks_intern_state_idx ON request_tasks(intern_id, state, created_at DESC);
CREATE INDEX request_tasks_request_idx ON request_tasks(request_id, created_at DESC);
