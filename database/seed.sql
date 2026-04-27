-- =====================================================================
-- Datos demo para el sistema de transporte
-- Hashes generados con password_hash('demo1234', PASSWORD_BCRYPT)
-- Todos los usuarios demo usan la contraseña: demo1234
-- =====================================================================

USE uber_yango;

-- Limpieza
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE ratings;
TRUNCATE TABLE rides;
TRUNCATE TABLE vehicles;
TRUNCATE TABLE drivers;
TRUNCATE TABLE passengers;
TRUNCATE TABLE users;
TRUNCATE TABLE fare_settings;
SET FOREIGN_KEY_CHECKS = 1;

-- Tarifas (USD)
INSERT INTO fare_settings (category, base_fare, per_km, per_min, min_fare, currency) VALUES
  ('economy', 2.00, 0.80, 0.20, 3.00, 'USD'),
  ('comfort', 3.00, 1.10, 0.25, 4.50, 'USD'),
  ('xl',      4.00, 1.40, 0.30, 6.00, 'USD');

-- Usuarios (password = "demo1234")
-- Hash bcrypt válido para "demo1234"
-- NOTA: este hash corresponde a la cadena "demo1234".
-- Si falla en tu MySQL puedes regenerarlo con:
--   php -r "echo password_hash('demo1234', PASSWORD_BCRYPT);"
SET @pwd := '$2y$10$e8BSlsi/tpDsdvY2jjdBzORZIJCNCTuGaG4bSRVzgi1iERdVcuTJe';

INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
  ('Admin Principal',  'admin@demo.com',     '+10000000000', @pwd, 'admin'),
  ('Ana Pasajera',     'ana@demo.com',       '+10000000001', @pwd, 'passenger'),
  ('Bruno Pasajero',   'bruno@demo.com',     '+10000000002', @pwd, 'passenger'),
  ('Carlos Conductor', 'carlos@demo.com',    '+10000000003', @pwd, 'driver'),
  ('Diana Conductora', 'diana@demo.com',     '+10000000004', @pwd, 'driver');

-- Perfiles
INSERT INTO passengers (user_id, default_address) VALUES
  ((SELECT id FROM users WHERE email='ana@demo.com'),   'Av. Principal 123'),
  ((SELECT id FROM users WHERE email='bruno@demo.com'), 'Calle 9 #45');

INSERT INTO drivers (user_id, license_number, is_online, current_lat, current_lng) VALUES
  ((SELECT id FROM users WHERE email='carlos@demo.com'), 'LIC-CAR-001', 1, 19.4326, -99.1332),
  ((SELECT id FROM users WHERE email='diana@demo.com'),  'LIC-DIA-002', 1, 19.4360, -99.1400);

-- Vehículos
INSERT INTO vehicles (driver_id, brand, model, color, plate, year, category) VALUES
  ((SELECT id FROM drivers WHERE license_number='LIC-CAR-001'),
    'Toyota', 'Corolla', 'Blanco', 'ABC-1234', 2020, 'economy'),
  ((SELECT id FROM drivers WHERE license_number='LIC-DIA-002'),
    'Honda',  'Civic',   'Gris',   'XYZ-9876', 2022, 'comfort');
