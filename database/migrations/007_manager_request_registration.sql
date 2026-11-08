INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.code = 'csi_manager' AND p.code = 'request.create'
ON CONFLICT DO NOTHING;
