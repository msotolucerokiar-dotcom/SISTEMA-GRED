INSERT INTO roles (code, name) VALUES ('intern', 'Practicante CSI') ON CONFLICT (code) DO NOTHING;

INSERT INTO permissions (code, description) VALUES
('request.assign', 'Asignar y reasignar solicitudes'),
('request.classify', 'Clasificar y priorizar solicitudes'),
('request.delegate', 'Delegar tareas a practicantes'),
('task.view_own', 'Consultar tareas propias'),
('task.record_work', 'Documentar tareas propias')
ON CONFLICT (code) DO NOTHING;

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE (r.code = 'csi_manager' AND p.code IN ('request.assign', 'request.classify', 'request.delegate', 'request.view_assigned', 'request.update_status'))
   OR (r.code = 'intern' AND p.code IN ('task.view_own', 'task.record_work'))
ON CONFLICT DO NOTHING;
