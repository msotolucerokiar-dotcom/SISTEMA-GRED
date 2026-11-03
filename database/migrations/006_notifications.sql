CREATE TABLE notifications (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    recipient_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    history_id BIGINT NOT NULL REFERENCES request_history(id) ON DELETE CASCADE,
    title VARCHAR(100) NOT NULL,
    message VARCHAR(250) NOT NULL,
    target_url VARCHAR(250) NOT NULL CHECK (left(target_url, 1) = '/' AND left(target_url, 2) <> '//'),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    read_at TIMESTAMPTZ,
    UNIQUE (recipient_id, history_id)
);
CREATE INDEX notifications_recipient_time_idx ON notifications(recipient_id, created_at DESC, id DESC);
CREATE INDEX notifications_unread_idx ON notifications(recipient_id, id DESC) WHERE read_at IS NULL;

CREATE FUNCTION add_request_notification(
    p_recipient BIGINT, p_history BIGINT, p_actor BIGINT,
    p_title TEXT, p_message TEXT, p_url TEXT
) RETURNS VOID LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO notifications (recipient_id, history_id, title, message, target_url)
    SELECT u.id, p_history, p_title, p_message, p_url
    FROM users u WHERE u.id = p_recipient AND u.is_active = TRUE AND u.id <> p_actor
    ON CONFLICT (recipient_id, history_id) DO NOTHING;
END;
$$;

CREATE FUNCTION notify_request_history() RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    v_code TEXT;
    v_requester BIGINT;
    v_manager BIGINT;
    v_task RECORD;
    v_previous BIGINT;
    v_next BIGINT;
BEGIN
    SELECT public_code, requester_id INTO v_code, v_requester FROM requests WHERE id = NEW.request_id;
    IF v_code IS NULL THEN RETURN NEW; END IF;

    IF NEW.action IN ('assigned', 'reassigned') THEN
        v_next := NULLIF(NEW.details->>'technician_id', '')::BIGINT;
        PERFORM add_request_notification(v_next, NEW.id, NEW.actor_id,
            'Nueva asignación', 'Se te asignó la solicitud ' || v_code || '.',
            '/technician/requests/' || v_code);
        IF NEW.action = 'reassigned' THEN
            v_previous := NULLIF(NEW.details->>'previous_technician_id', '')::BIGINT;
            PERFORM add_request_notification(v_previous, NEW.id, NEW.actor_id,
                'Cambio de responsable', 'La solicitud ' || v_code || ' dejó de estar asignada a ti.', '/dashboard');
        END IF;
    END IF;

    IF NEW.action IN ('assigned', 'reassigned', 'classified', 'started', 'resolved') THEN
        PERFORM add_request_notification(v_requester, NEW.id, NEW.actor_id,
            CASE NEW.action
                WHEN 'resolved' THEN 'Solicitud solucionada'
                WHEN 'started' THEN 'Atención iniciada'
                WHEN 'classified' THEN 'Solicitud clasificada'
                ELSE 'Asignación actualizada'
            END,
            'Hay una actualización en tu solicitud ' || v_code || '.', '/requests/' || v_code);
    END IF;

    IF NEW.action IN ('task_delegated', 'task_submitted', 'task_approved', 'task_cancelled') THEN
        SELECT id, intern_id, responsible_id INTO v_task FROM request_tasks
        WHERE id = NULLIF(NEW.details->>'task_id', '')::BIGINT;
        IF FOUND THEN
            IF NEW.action = 'task_delegated' THEN
                PERFORM add_request_notification(v_task.intern_id, NEW.id, NEW.actor_id,
                    'Nueva tarea delegada', 'Tienes una tarea en la solicitud ' || v_code || '.',
                    '/intern/tasks/' || v_task.id);
            ELSIF NEW.action = 'task_submitted' THEN
                PERFORM add_request_notification(v_task.responsible_id, NEW.id, NEW.actor_id,
                    'Tarea pendiente de validación', 'Se envió una tarea de la solicitud ' || v_code || ' para validar.',
                    '/technician/requests/' || v_code);
            ELSE
                PERFORM add_request_notification(v_task.intern_id, NEW.id, NEW.actor_id,
                    CASE NEW.action WHEN 'task_approved' THEN 'Tarea validada' ELSE 'Tarea cancelada' END,
                    'Se actualizó tu tarea de la solicitud ' || v_code || '.',
                    '/intern/tasks/' || v_task.id);
            END IF;
        END IF;
    END IF;

    IF NEW.action = 'tasks_cancelled' THEN
        FOR v_task IN SELECT id, intern_id FROM request_tasks
            WHERE request_id = NEW.request_id AND state = 'cancelled' AND cancelled_at = transaction_timestamp()
        LOOP
            PERFORM add_request_notification(v_task.intern_id, NEW.id, NEW.actor_id,
                'Tarea cancelada', 'Se canceló tu tarea de la solicitud ' || v_code || ' por un cambio de responsable.',
                '/intern/tasks/' || v_task.id);
        END LOOP;
    END IF;

    IF NEW.action IN ('created', 'assigned', 'reassigned', 'classified', 'started', 'resolved', 'task_submitted') THEN
        FOR v_manager IN SELECT u.id FROM users u JOIN roles role ON role.id = u.role_id
            WHERE role.code = 'csi_manager' AND u.is_active = TRUE AND u.id <> NEW.actor_id
        LOOP
            PERFORM add_request_notification(v_manager, NEW.id, NEW.actor_id,
                CASE NEW.action WHEN 'created' THEN 'Nueva solicitud' WHEN 'task_submitted' THEN 'Tarea por validar' ELSE 'Solicitud actualizada' END,
                'Revisa la solicitud ' || v_code || '.', '/csi/requests/' || v_code);
        END LOOP;
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER request_history_notifications
AFTER INSERT ON request_history FOR EACH ROW EXECUTE FUNCTION notify_request_history();
