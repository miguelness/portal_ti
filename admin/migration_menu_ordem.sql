-- Migration Application: Modernized Menu Management
-- Date: 2025-12-11
-- Description: Changes 'ordem' column from INT to VARCHAR to support hierarchical strings (1.1, 1.2)

ALTER TABLE menu_links MODIFY COLUMN ordem VARCHAR(50) DEFAULT '0';
