DROP DATABASE IF EXISTS equipment_db;
CREATE DATABASE equipment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE equipment_db;

CREATE TABLE assets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  serial_number VARCHAR(100) NOT NULL,
  inventory_number VARCHAR(50) NOT NULL,
  assigned_to VARCHAR(255) DEFAULT NULL,
  status ENUM('in_use','storage','repair','retired') NOT NULL DEFAULT 'in_use',
  is_retired TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO assets (name, serial_number, inventory_number, assigned_to, status) VALUES
('Ноутбук Lenovo ThinkPad', 'SN-LN-001', 'INV-1001', 'Иванов И.И.', 'in_use'),
('Проектор Epson', 'SN-EP-777', 'INV-1002', 'Склад', 'storage'),
('Принтер HP LaserJet', 'SN-HP-555', 'INV-1003', 'Петров П.П.', 'repair');
