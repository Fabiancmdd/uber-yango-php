-- =====================================================================
-- Esquema de base de datos: Sistema de transporte tipo Uber/Yango
-- Motor: MySQL 5.7+ / MariaDB 10.3+
-- Codificación: utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS uber_yango
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE uber_yango;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS rides;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS passengers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS fare_settings;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Usuarios (cuenta base con rol)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name       VARCHAR(120) NOT NULL,
  email           VARCHAR(160) NOT NULL,
  phone           VARCHAR(30)  DEFAULT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('passenger','driver','admin') NOT NULL DEFAULT 'passenger',
  status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Perfil de pasajero
-- ---------------------------------------------------------------------
CREATE TABLE passengers (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  default_address VARCHAR(255) DEFAULT NULL,
  rating_avg      DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  total_rides     INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_passenger_user (user_id),
  CONSTRAINT fk_passenger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Perfil de conductor
-- ---------------------------------------------------------------------
CREATE TABLE drivers (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  license_number  VARCHAR(60) NOT NULL,
  is_online       TINYINT(1) NOT NULL DEFAULT 0,
  current_lat     DECIMAL(10,7) DEFAULT NULL,
  current_lng     DECIMAL(10,7) DEFAULT NULL,
  rating_avg      DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  total_rides     INT UNSIGNED NOT NULL DEFAULT 0,
  total_earnings  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  UNIQUE KEY uq_driver_user (user_id),
  UNIQUE KEY uq_driver_license (license_number),
  CONSTRAINT fk_driver_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Vehículos asociados a un conductor
-- ---------------------------------------------------------------------
CREATE TABLE vehicles (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  driver_id     INT UNSIGNED NOT NULL,
  brand         VARCHAR(60) NOT NULL,
  model         VARCHAR(60) NOT NULL,
  color         VARCHAR(40) NOT NULL,
  plate         VARCHAR(20) NOT NULL,
  year          SMALLINT UNSIGNED DEFAULT NULL,
  category      ENUM('economy','comfort','xl') NOT NULL DEFAULT 'economy',
  PRIMARY KEY (id),
  UNIQUE KEY uq_vehicle_plate (plate),
  KEY ix_vehicle_driver (driver_id),
  CONSTRAINT fk_vehicle_driver FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Viajes
-- ---------------------------------------------------------------------
CREATE TABLE rides (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  passenger_id    INT UNSIGNED NOT NULL,
  driver_id       INT UNSIGNED DEFAULT NULL,
  vehicle_id      INT UNSIGNED DEFAULT NULL,

  origin_address      VARCHAR(255) NOT NULL,
  origin_lat          DECIMAL(10,7) NOT NULL,
  origin_lng          DECIMAL(10,7) NOT NULL,
  destination_address VARCHAR(255) NOT NULL,
  destination_lat     DECIMAL(10,7) NOT NULL,
  destination_lng     DECIMAL(10,7) NOT NULL,

  distance_km     DECIMAL(8,3) NOT NULL DEFAULT 0,
  duration_min    DECIMAL(8,2) NOT NULL DEFAULT 0,
  category        ENUM('economy','comfort','xl') NOT NULL DEFAULT 'economy',

  fare_estimated  DECIMAL(10,2) NOT NULL DEFAULT 0,
  fare_final      DECIMAL(10,2) DEFAULT NULL,

  status          ENUM('requested','accepted','in_progress','completed','cancelled')
                   NOT NULL DEFAULT 'requested',
  cancel_reason   VARCHAR(255) DEFAULT NULL,
  payment_method  ENUM('cash','card') NOT NULL DEFAULT 'cash',

  requested_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accepted_at     TIMESTAMP NULL DEFAULT NULL,
  started_at      TIMESTAMP NULL DEFAULT NULL,
  completed_at    TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (id),
  KEY ix_rides_passenger (passenger_id),
  KEY ix_rides_driver (driver_id),
  KEY ix_rides_status (status),
  CONSTRAINT fk_ride_passenger FOREIGN KEY (passenger_id) REFERENCES passengers(id) ON DELETE CASCADE,
  CONSTRAINT fk_ride_driver    FOREIGN KEY (driver_id)    REFERENCES drivers(id)    ON DELETE SET NULL,
  CONSTRAINT fk_ride_vehicle   FOREIGN KEY (vehicle_id)   REFERENCES vehicles(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Calificaciones (pasajero <-> conductor, una por viaje y dirección)
-- ---------------------------------------------------------------------
CREATE TABLE ratings (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ride_id     INT UNSIGNED NOT NULL,
  rater_role  ENUM('passenger','driver') NOT NULL,
  stars       TINYINT UNSIGNED NOT NULL,
  comment     VARCHAR(500) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rating_ride_role (ride_id, rater_role),
  CONSTRAINT fk_rating_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  CONSTRAINT chk_rating_stars CHECK (stars BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Configuración de tarifas (una fila por categoría)
-- Tarifa = base_fare + per_km * km + per_min * min   (mínimo min_fare)
-- ---------------------------------------------------------------------
CREATE TABLE fare_settings (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category    ENUM('economy','comfort','xl') NOT NULL,
  base_fare   DECIMAL(10,2) NOT NULL DEFAULT 2.00,
  per_km      DECIMAL(10,2) NOT NULL DEFAULT 0.80,
  per_min     DECIMAL(10,2) NOT NULL DEFAULT 0.20,
  min_fare    DECIMAL(10,2) NOT NULL DEFAULT 3.00,
  currency    VARCHAR(8) NOT NULL DEFAULT 'USD',
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fare_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
