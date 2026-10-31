CREATE SEQUENCE request_public_number_seq;
INSERT INTO areas (name) VALUES ('Sede central') ON CONFLICT (name) DO NOTHING;
CREATE INDEX request_attachments_request_idx ON request_attachments(request_id);
