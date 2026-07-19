-- LabControl - Schema Inicial
-- PostgreSQL 17

-- Habilitar extensão UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Tabelas gerenciadas pelas migrations do Laravel:
-- users, roles, permissions, role_user, permission_role, activity_logs
-- equipment, inventory, inventory_movements, loans, calibrations
-- verifications, maintenance, certificates, laboratories
-- locations, manufacturers, suppliers, attachments, settings

-- Este arquivo serve como referência do schema completo.
-- As migrations do Laravel são a fonte oficial da estrutura do banco.
