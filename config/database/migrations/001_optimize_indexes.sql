-- 001_optimize_indexes.sql
-- Simple migration to verify migration system works

-- Update any existing configurations if needed
INSERT IGNORE INTO CauHinh (TenCH, GiaTri, MoTa) VALUES
('migration_test', '1', 'Test migration completed successfully');