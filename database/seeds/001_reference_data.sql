INSERT INTO roles (code, name) VALUES
('worker', 'Trabajador'),
('technician', 'Personal técnico'),
('csi_manager', 'Responsable del CSI');

INSERT INTO permissions (code, description) VALUES
('request.create', 'Registrar solicitudes'),
('request.view_own', 'Consultar solicitudes propias'),
('request.view_assigned', 'Consultar solicitudes asignadas'),
('request.view_all', 'Consultar todas las solicitudes'),
('request.manage', 'Clasificar, priorizar y asignar solicitudes'),
('request.update_status', 'Actualizar estado y registrar solución'),
('user.manage_roles', 'Administrar roles y permisos'),
('report.view', 'Consultar y exportar reportes');

INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE (r.code = 'worker' AND p.code IN ('request.create', 'request.view_own'))
   OR (r.code = 'technician' AND p.code IN ('request.view_assigned', 'request.update_status'))
   OR (r.code = 'csi_manager' AND p.code IN ('request.view_all', 'request.manage', 'user.manage_roles', 'report.view'));

INSERT INTO categories (name) VALUES
('Conectividad e Internet'), ('Configuración de IP'), ('Impresión'),
('Cableado y ponchado'), ('Instalación de equipos'), ('Contraseñas y accesos'),
('Formateo y mantenimiento'), ('Otro');

INSERT INTO priorities (code, name, sort_order) VALUES
('low', 'Baja', 1), ('normal', 'Normal', 2), ('high', 'Alta', 3), ('urgent', 'Urgente', 4);

INSERT INTO statuses (code, name, is_terminal) VALUES
('pending', 'Pendiente', FALSE), ('in_progress', 'En atención', FALSE),
('resolved', 'Solucionada', TRUE), ('closed', 'Cerrada', TRUE), ('cancelled', 'Cancelada', TRUE);
