-- phpMyAdmin SQL Dump
-- Base de datos: `parking_db`
-- Esquema actualizado para coincidir con la estructura del usuario

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `roles`
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`id`, `code`, `name`) VALUES
(1, 'ADMIN', 'Administrador'),
(2, 'USER', 'Usuario');

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `facilities`
-- --------------------------------------------------------
CREATE TABLE `facilities` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `address` varchar(180) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `facilities` (`id`, `name`, `address`, `is_active`) VALUES
(1, 'Parqueadero Principal', NULL, 1);

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `vehicle_types`
-- --------------------------------------------------------
CREATE TABLE `vehicle_types` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `vehicle_types` (`id`, `code`, `name`) VALUES
(1, 'CAR', 'Carro'),
(2, 'MOTO', 'Moto'),
(3, 'BICI', 'Bicicleta');

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `parking_capacity`
-- --------------------------------------------------------
CREATE TABLE `parking_capacity` (
  `facility_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_type_id` bigint(20) UNSIGNED NOT NULL,
  `capacity` int(11) NOT NULL,
  PRIMARY KEY (`facility_id`,`vehicle_type_id`),
  KEY `fk_cap_vehicle_type` (`vehicle_type_id`),
  CONSTRAINT `fk_cap_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `fk_cap_vehicle_type` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `parking_capacity` (`facility_id`, `vehicle_type_id`, `capacity`) VALUES
(1, 1, 20),
(1, 2, 30),
(1, 3, 15);

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `rates`
-- --------------------------------------------------------
CREATE TABLE `rates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `facility_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_type_id` bigint(20) UNSIGNED NOT NULL,
  `price_per_hour` decimal(12,2) NOT NULL,
  `min_minutes` int(11) NOT NULL DEFAULT 60,
  `rounding_minutes` int(11) NOT NULL DEFAULT 60,
  `grace_minutes` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate` (`facility_id`,`vehicle_type_id`),
  KEY `fk_rate_vt` (`vehicle_type_id`),
  CONSTRAINT `fk_rate_fac` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `fk_rate_vt` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rates` (`facility_id`, `vehicle_type_id`, `price_per_hour`, `min_minutes`, `rounding_minutes`, `grace_minutes`, `is_active`) VALUES
(1, 1, 5000.00, 60, 60, 10, 1),
(1, 2, 3000.00, 60, 60, 10, 1),
(1, 3, 1500.00, 60, 60, 15, 1);

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `reservations`
-- --------------------------------------------------------
CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `facility_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_type_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_description` varchar(120) DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `status` enum('PENDING','CONFIRMED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_res_vehicle_type` (`vehicle_type_id`),
  KEY `idx_res_user` (`user_id`),
  KEY `idx_res_availability` (`facility_id`,`vehicle_type_id`,`start_at`,`end_at`,`status`),
  CONSTRAINT `fk_res_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_res_vehicle_type` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `payments`
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'COP',
  `method` varchar(30) NOT NULL,
  `status` enum('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `gateway_reference` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pay_user` (`user_id`),
  KEY `idx_pay_reservation` (`reservation_id`),
  KEY `idx_pay_status` (`status`),
  CONSTRAINT `fk_pay_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `payment_notifications`
-- --------------------------------------------------------
CREATE TABLE `payment_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `reservation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `channel` enum('INTERNAL','EMAIL','SMS','WHATSAPP') NOT NULL DEFAULT 'INTERNAL',
  `message` varchar(255) NOT NULL,
  `notification_status` enum('CREATED','SENT','ERROR') NOT NULL DEFAULT 'CREATED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notif_payment` (`payment_id`),
  KEY `fk_notif_reservation` (`reservation_id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `pqr`
-- --------------------------------------------------------
CREATE TABLE `pqr` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('PETITION','COMPLAINT','CLAIM','SUGGESTION') NOT NULL,
  `description` text NOT NULL,
  `status` enum('PENDING','IN_PROGRESS','RESOLVED','CLOSED') NOT NULL DEFAULT 'PENDING',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pqr_user` (`user_id`),
  KEY `idx_pqr_status` (`status`),
  KEY `idx_pqr_type` (`type`),
  CONSTRAINT `fk_pqr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Triggers para payments
-- --------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_payments_bi_set_paid_at` BEFORE INSERT ON `payments` FOR EACH ROW
BEGIN
  IF NEW.status = 'PAID' AND NEW.paid_at IS NULL THEN
    SET NEW.paid_at = NOW();
  END IF;
END$$

CREATE TRIGGER `trg_payments_bu_set_paid_at` BEFORE UPDATE ON `payments` FOR EACH ROW
BEGIN
  IF NEW.status = 'PAID' AND OLD.status <> 'PAID' AND NEW.paid_at IS NULL THEN
    SET NEW.paid_at = NOW();
  END IF;
END$$

CREATE TRIGGER `trg_payments_ai_paid` AFTER INSERT ON `payments` FOR EACH ROW
BEGIN
  IF NEW.status = 'PAID' THEN
    UPDATE reservations
    SET status = 'CONFIRMED', updated_at = NOW()
    WHERE id = NEW.reservation_id AND status = 'PENDING';

    INSERT INTO payment_notifications (payment_id, reservation_id, user_id, channel, message, notification_status)
    VALUES (NEW.id, NEW.reservation_id, NEW.user_id, 'INTERNAL',
      CONCAT('Pago exitoso. Reserva #', NEW.reservation_id, ' confirmada. Monto: ', NEW.amount, ' ', NEW.currency),
      'CREATED');
  END IF;
END$$

CREATE TRIGGER `trg_payments_au_paid` AFTER UPDATE ON `payments` FOR EACH ROW
BEGIN
  IF NEW.status = 'PAID' AND OLD.status <> 'PAID' THEN
    UPDATE reservations
    SET status = 'CONFIRMED', updated_at = NOW()
    WHERE id = NEW.reservation_id AND status = 'PENDING';

    INSERT INTO payment_notifications (payment_id, reservation_id, user_id, channel, message, notification_status)
    VALUES (NEW.id, NEW.reservation_id, NEW.user_id, 'INTERNAL',
      CONCAT('Pago exitoso. Reserva #', NEW.reservation_id, ' confirmada. Monto: ', NEW.amount, ' ', NEW.currency),
      'CREATED');
  END IF;
END$$

DELIMITER ;

COMMIT;
