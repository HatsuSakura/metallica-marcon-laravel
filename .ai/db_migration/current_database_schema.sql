-- Adminer 4.8.1 MySQL 5.5.5-10.8.3-MariaDB-1:10.8.3+maria~jammy dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `polygon` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `area_site`;
CREATE TABLE `area_site` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  `area_id` bigint(20) unsigned NOT NULL,
  `is_preferred` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `area_site_site_id_foreign` (`site_id`),
  KEY `area_site_area_id_foreign` (`area_id`),
  CONSTRAINT `area_site_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_site_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `is_cargo` tinyint(1) NOT NULL DEFAULT 1,
  `is_long` tinyint(1) NOT NULL DEFAULT 0,
  `total_count` int(11) NOT NULL DEFAULT 0,
  `length` double DEFAULT NULL,
  `casse` int(11) DEFAULT NULL,
  `spazi_casse` int(11) DEFAULT NULL,
  `spazi_bancale` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `catalog_items`;
CREATE TABLE `catalog_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `type` enum('material','component') NOT NULL,
  `code` varchar(191) DEFAULT NULL,
  `parent_catalog_item_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_items_name_unique` (`name`),
  KEY `catalog_items_parent_catalog_item_id_foreign` (`parent_catalog_item_id`),
  CONSTRAINT `catalog_items_parent_catalog_item_id_foreign` FOREIGN KEY (`parent_catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `cer_codes`;
CREATE TABLE `cer_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `description` varchar(191) NOT NULL,
  `is_dangerous` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `customer_occasionale` tinyint(4) DEFAULT 0,
  `seller_id` bigint(20) unsigned NOT NULL,
  `ragione_sociale` varchar(191) NOT NULL,
  `partita_iva` varchar(191) NOT NULL,
  `codice_fiscale` varchar(191) NOT NULL,
  `indirizzo_legale` varchar(191) NOT NULL,
  `codice_sdi` varchar(191) NOT NULL,
  `job_type` varchar(191) DEFAULT NULL,
  `email_commerciale` varchar(191) NOT NULL,
  `email_amministrativa` varchar(191) NOT NULL,
  `pec` varchar(191) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_partita_iva_unique` (`partita_iva`),
  UNIQUE KEY `customers_codice_fiscale_unique` (`codice_fiscale`),
  KEY `customers_seller_id_foreign` (`seller_id`),
  FULLTEXT KEY `customers_fulltext_index` (`ragione_sociale`,`partita_iva`,`codice_fiscale`,`indirizzo_legale`,`email_commerciale`,`email_amministrativa`,`pec`),
  CONSTRAINT `customers_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `holders`;
CREATE TABLE `holders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `description` varchar(191) DEFAULT NULL,
  `volume` double DEFAULT NULL,
  `equivalent_holder_id` bigint(20) unsigned DEFAULT NULL,
  `equivalent_units` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holders_is_custom_index` (`is_custom`),
  KEY `holders_equivalent_holder_id_index` (`equivalent_holder_id`),
  CONSTRAINT `holders_equivalent_holder_id_foreign` FOREIGN KEY (`equivalent_holder_id`) REFERENCES `holders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `internal_contacts`;
CREATE TABLE `internal_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `surname` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `mobile` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `role` varchar(191) DEFAULT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `internal_contacts_site_id_foreign` (`site_id`),
  CONSTRAINT `internal_contacts_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journeys`;
CREATE TABLE `journeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `dt_start` timestamp NULL DEFAULT NULL,
  `dt_end` timestamp NULL DEFAULT NULL,
  `real_dt_start` timestamp NULL DEFAULT NULL,
  `real_dt_end` timestamp NULL DEFAULT NULL,
  `is_double_load` tinyint(1) NOT NULL DEFAULT 0,
  `is_temporary_storage` tinyint(1) NOT NULL DEFAULT 0,
  `warehouse_id_1` bigint(20) unsigned DEFAULT NULL,
  `warehouse_download_dt_1` timestamp NULL DEFAULT NULL,
  `warehouse_id_2` bigint(20) unsigned DEFAULT NULL,
  `warehouse_download_dt_2` timestamp NULL DEFAULT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `cargo_for_vehicle_id` bigint(20) unsigned NOT NULL,
  `trailer_id` bigint(20) unsigned DEFAULT NULL,
  `cargo_for_trailer_id` bigint(20) unsigned DEFAULT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `logistic_id` bigint(20) unsigned NOT NULL,
  `state` enum('creato','attivo','eseguito','chiuso') NOT NULL DEFAULT 'creato',
  `plan_version` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `journeys_vehicle_id_foreign` (`vehicle_id`),
  KEY `journeys_cargo_for_vehicle_id_foreign` (`cargo_for_vehicle_id`),
  KEY `journeys_trailer_id_foreign` (`trailer_id`),
  KEY `journeys_cargo_for_trailer_id_foreign` (`cargo_for_trailer_id`),
  KEY `journeys_driver_id_foreign` (`driver_id`),
  KEY `journeys_logistic_id_foreign` (`logistic_id`),
  KEY `journeys_warehouse_id_1_foreign` (`warehouse_id_1`),
  KEY `journeys_warehouse_id_2_foreign` (`warehouse_id_2`),
  CONSTRAINT `journeys_cargo_for_trailer_id_foreign` FOREIGN KEY (`cargo_for_trailer_id`) REFERENCES `cargos` (`id`),
  CONSTRAINT `journeys_cargo_for_vehicle_id_foreign` FOREIGN KEY (`cargo_for_vehicle_id`) REFERENCES `cargos` (`id`),
  CONSTRAINT `journeys_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `journeys_logistic_id_foreign` FOREIGN KEY (`logistic_id`) REFERENCES `users` (`id`),
  CONSTRAINT `journeys_trailer_id_foreign` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`),
  CONSTRAINT `journeys_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `journeys_warehouse_id_1_foreign` FOREIGN KEY (`warehouse_id_1`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journeys_warehouse_id_2_foreign` FOREIGN KEY (`warehouse_id_2`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_cargos`;
CREATE TABLE `journey_cargos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `cargo_id` bigint(20) unsigned NOT NULL,
  `journey_id` bigint(20) unsigned NOT NULL,
  `truck_location` enum('vehicle','trailer','fulfill') DEFAULT NULL,
  `is_grounding` tinyint(1) NOT NULL DEFAULT 0,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `download_sequence` tinyint(3) unsigned NOT NULL,
  `state` enum('creato','attivo','eseguito','chiuso') NOT NULL DEFAULT 'creato',
  PRIMARY KEY (`id`),
  KEY `journey_cargos_cargo_id_foreign` (`cargo_id`),
  KEY `journey_cargos_journey_id_foreign` (`journey_id`),
  KEY `journey_cargos_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `journey_cargos_cargo_id_foreign` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`),
  CONSTRAINT `journey_cargos_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `journeys` (`id`),
  CONSTRAINT `journey_cargos_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_cargo_order_item`;
CREATE TABLE `journey_cargo_order_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_cargo_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `is_double_load` tinyint(1) NOT NULL DEFAULT 0,
  `warehouse_download_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journey_cargo_order_item_journey_cargo_id_order_item_id_unique` (`journey_cargo_id`,`order_item_id`),
  KEY `journey_cargo_order_item_order_item_id_foreign` (`order_item_id`),
  KEY `journey_cargo_order_item_warehouse_download_id_foreign` (`warehouse_download_id`),
  CONSTRAINT `journey_cargo_order_item_journey_cargo_id_foreign` FOREIGN KEY (`journey_cargo_id`) REFERENCES `journey_cargos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_cargo_order_item_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_cargo_order_item_warehouse_download_id_foreign` FOREIGN KEY (`warehouse_download_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_events`;
CREATE TABLE `journey_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` bigint(20) unsigned NOT NULL,
  `journey_stop_id` bigint(20) unsigned DEFAULT NULL,
  `state` enum('planned','in_progress','done','skipped','cancelled') DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journey_events_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `journey_events_journey_id_created_at_index` (`journey_id`,`created_at`),
  KEY `journey_events_journey_stop_id_created_at_index` (`journey_stop_id`,`created_at`),
  KEY `journey_events_state_created_at_index` (`state`,`created_at`),
  CONSTRAINT `journey_events_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journey_events_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `journeys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_events_journey_stop_id_foreign` FOREIGN KEY (`journey_stop_id`) REFERENCES `journey_stops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_stops`;
CREATE TABLE `journey_stops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` bigint(20) unsigned NOT NULL,
  `kind` enum('customer','technical') DEFAULT 'customer',
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_visit_index` int(10) unsigned DEFAULT 1,
  `technical_action_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `planned_sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `sequence` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('planned','in_progress','done','skipped','cancelled') DEFAULT 'planned',
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `address_text` varchar(191) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reason_code` varchar(64) DEFAULT NULL,
  `reason_text` text DEFAULT NULL,
  `driver_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journey_customer_visit_unique` (`journey_id`,`customer_id`,`customer_visit_index`),
  KEY `journey_stops_customer_id_foreign` (`customer_id`),
  KEY `journey_stops_technical_action_id_foreign` (`technical_action_id`),
  KEY `journey_stops_journey_id_sequence_index` (`journey_id`,`sequence`),
  KEY `journey_stops_journey_id_status_index` (`journey_id`,`status`),
  KEY `journey_stops_journey_id_kind_index` (`journey_id`,`kind`),
  CONSTRAINT `journey_stops_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `journey_stops_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `journeys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_stops_technical_action_id_foreign` FOREIGN KEY (`technical_action_id`) REFERENCES `journey_stop_actions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_stop_actions`;
CREATE TABLE `journey_stop_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `label` varchar(191) NOT NULL,
  `requires_location` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journey_stop_actions_code_unique` (`code`),
  KEY `journey_stop_actions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `journey_stop_orders`;
CREATE TABLE `journey_stop_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` bigint(20) unsigned NOT NULL,
  `journey_stop_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_journey_order_once` (`journey_id`,`order_id`),
  UNIQUE KEY `uniq_stop_order_once` (`journey_stop_id`,`order_id`),
  KEY `journey_stop_orders_journey_stop_id_index` (`journey_stop_id`),
  KEY `journey_stop_orders_order_id_index` (`order_id`),
  CONSTRAINT `journey_stop_orders_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `journeys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_stop_orders_journey_stop_id_foreign` FOREIGN KEY (`journey_stop_id`) REFERENCES `journey_stops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journey_stop_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_code` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
  `requested_at` timestamp NULL DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  `logistic_id` bigint(20) unsigned NOT NULL,
  `journey_id` bigint(20) unsigned DEFAULT NULL,
  `state` enum('creato','pianificato','eseguito','scaricato','chiuso') NOT NULL DEFAULT 'creato',
  `truck_location` enum('vehicle','trailer','fulfill') DEFAULT NULL,
  `expected_withdraw_dt` timestamp NULL DEFAULT NULL,
  `real_withdraw_dt` timestamp NULL DEFAULT NULL,
  `has_ragno` tinyint(1) NOT NULL DEFAULT 0,
  `ragnista_id` bigint(20) unsigned DEFAULT NULL,
  `machinery_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_site_id_foreign` (`site_id`),
  KEY `orders_logistic_id_foreign` (`logistic_id`),
  KEY `orders_journey_id_foreign` (`journey_id`),
  KEY `orders_ragnista_id_foreign` (`ragnista_id`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `journeys` (`id`),
  CONSTRAINT `orders_logistic_id_foreign` FOREIGN KEY (`logistic_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ragnista_id_foreign` FOREIGN KEY (`ragnista_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_counters`;
CREATE TABLE `order_counters` (
  `year` year(4) NOT NULL,
  `counter` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_holders`;
CREATE TABLE `order_holders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `holder_id` bigint(20) unsigned NOT NULL,
  `holder_piene` int(11) NOT NULL DEFAULT 0,
  `holder_vuote` int(11) NOT NULL DEFAULT 0,
  `holder_totale` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_holders_order_id_foreign` (`order_id`),
  KEY `order_holders_holder_id_foreign` (`holder_id`),
  CONSTRAINT `order_holders_holder_id_foreign` FOREIGN KEY (`holder_id`) REFERENCES `holders` (`id`),
  CONSTRAINT `order_holders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `cer_code_id` bigint(20) unsigned NOT NULL,
  `order_item_group_id` bigint(20) unsigned DEFAULT NULL,
  `holder_id` bigint(20) unsigned DEFAULT NULL,
  `holder_quantity` int(11) DEFAULT NULL,
  `is_bulk` tinyint(1) NOT NULL DEFAULT 0,
  `custom_l_cm` decimal(8,2) DEFAULT NULL,
  `custom_w_cm` decimal(8,2) DEFAULT NULL,
  `custom_h_cm` decimal(8,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `weight_declared` double DEFAULT NULL,
  `weight_gross` double DEFAULT NULL,
  `weight_tare` double DEFAULT NULL,
  `weight_net` double DEFAULT NULL,
  `adr` tinyint(1) DEFAULT 0,
  `adr_onu_code` varchar(191) DEFAULT NULL,
  `adr_hp` varchar(191) DEFAULT NULL,
  `adr_lotto` varchar(191) DEFAULT NULL,
  `adr_volume` double DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_notes` text DEFAULT NULL,
  `is_holder_dirty` tinyint(1) NOT NULL DEFAULT 0,
  `total_dirty_holders` int(11) DEFAULT NULL,
  `is_holder_broken` tinyint(1) NOT NULL DEFAULT 0,
  `total_broken_holders` int(11) DEFAULT NULL,
  `is_warehouse_added` tinyint(1) NOT NULL DEFAULT 0,
  `is_not_found` tinyint(1) NOT NULL DEFAULT 0,
  `has_non_conformity` tinyint(1) NOT NULL DEFAULT 0,
  `has_exploded_children` tinyint(1) NOT NULL DEFAULT 0,
  `warehouse_non_conformity` text DEFAULT NULL,
  `warehouse_manager_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_downaload_worker_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_downaload_dt` timestamp NULL DEFAULT NULL,
  `warehouse_weighing_worker_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_weighing_dt` timestamp NULL DEFAULT NULL,
  `warehouse_selection_worker_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_selection_dt` timestamp NULL DEFAULT NULL,
  `has_selection` tinyint(1) NOT NULL DEFAULT 0,
  `selection_time` double DEFAULT NULL,
  `is_ragnabile` tinyint(1) NOT NULL DEFAULT 0,
  `machinery_time_fraction` int(11) DEFAULT NULL,
  `is_machinery_time_manual` tinyint(1) NOT NULL DEFAULT 0,
  `is_transshipment` tinyint(1) NOT NULL DEFAULT 0,
  `recognized_price` double DEFAULT NULL,
  `recognized_weight` double DEFAULT NULL,
  `adr_totale` tinyint(1) DEFAULT NULL,
  `adr_esenzione_totale` tinyint(1) DEFAULT NULL,
  `adr_esenzione_parziale` tinyint(1) DEFAULT NULL,
  `state` enum('creato','caricato','scaricato','trasbordo','lavorazione','classificato','chiuso') NOT NULL DEFAULT 'creato',
  PRIMARY KEY (`id`),
  KEY `order_items_cer_code_id_foreign` (`cer_code_id`),
  KEY `order_items_holder_id_foreign` (`holder_id`),
  KEY `order_items_worker_id_foreign` (`warehouse_downaload_worker_id`),
  KEY `order_items_warehouse_id_foreign` (`warehouse_id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_warehouse_manager_id_foreign` (`warehouse_manager_id`),
  KEY `order_items_warehouse_weighing_worker_id_foreign` (`warehouse_weighing_worker_id`),
  KEY `order_items_warehouse_selection_worker_id_foreign` (`warehouse_selection_worker_id`),
  KEY `order_items_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `order_items_is_bulk_index` (`is_bulk`),
  KEY `order_items_order_item_group_id_foreign` (`order_item_group_id`),
  CONSTRAINT `order_items_cer_code_id_foreign` FOREIGN KEY (`cer_code_id`) REFERENCES `cer_codes` (`id`),
  CONSTRAINT `order_items_holder_id_foreign` FOREIGN KEY (`holder_id`) REFERENCES `holders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_order_item_group_id_foreign` FOREIGN KEY (`order_item_group_id`) REFERENCES `order_item_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `order_items_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `order_items_warehouse_manager_id_foreign` FOREIGN KEY (`warehouse_manager_id`) REFERENCES `users` (`id`),
  CONSTRAINT `order_items_warehouse_selection_worker_id_foreign` FOREIGN KEY (`warehouse_selection_worker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `order_items_warehouse_weighing_worker_id_foreign` FOREIGN KEY (`warehouse_weighing_worker_id`) REFERENCES `users` (`id`),
  CONSTRAINT `order_items_worker_id_foreign` FOREIGN KEY (`warehouse_downaload_worker_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_item_explosions`;
CREATE TABLE `order_item_explosions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `parent_explosion_id` bigint(20) unsigned DEFAULT NULL,
  `catalog_item_id` bigint(20) unsigned NOT NULL,
  `explosion_source` enum('ad_hoc','recipe') DEFAULT NULL,
  `recipe_id` bigint(20) unsigned DEFAULT NULL,
  `recipe_version` int(10) unsigned DEFAULT NULL,
  `weight_net` decimal(10,3) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_explosions_order_item_id_foreign` (`order_item_id`),
  KEY `order_item_explosions_parent_explosion_id_foreign` (`parent_explosion_id`),
  KEY `order_item_explosions_catalog_item_id_foreign` (`catalog_item_id`),
  KEY `order_item_explosions_recipe_id_foreign` (`recipe_id`),
  CONSTRAINT `order_item_explosions_catalog_item_id_foreign` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_explosions_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_explosions_parent_explosion_id_foreign` FOREIGN KEY (`parent_explosion_id`) REFERENCES `order_item_explosions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_item_explosions_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_item_groups`;
CREATE TABLE `order_item_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `cer_code_id` bigint(20) unsigned NOT NULL,
  `label` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_item_groups_order_id_cer_code_id_label_unique` (`order_id`,`cer_code_id`,`label`),
  KEY `order_item_groups_cer_code_id_foreign` (`cer_code_id`),
  KEY `order_item_groups_order_id_cer_code_id_index` (`order_id`,`cer_code_id`),
  CONSTRAINT `order_item_groups_cer_code_id_foreign` FOREIGN KEY (`cer_code_id`) REFERENCES `cer_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_groups_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `order_item_images`;
CREATE TABLE `order_item_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `path` varchar(191) NOT NULL,
  `original_name` varchar(191) DEFAULT NULL,
  `mime_type` varchar(191) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_images_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `order_item_images_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `recipes`;
CREATE TABLE `recipes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `catalog_item_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipes_name_unique` (`name`),
  UNIQUE KEY `recipes_catalog_item_id_unique` (`catalog_item_id`),
  CONSTRAINT `recipes_catalog_item_id_foreign` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `recipe_nodes`;
CREATE TABLE `recipe_nodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `parent_node_id` bigint(20) unsigned DEFAULT NULL,
  `catalog_item_id` bigint(20) unsigned NOT NULL,
  `is_override` tinyint(1) NOT NULL DEFAULT 0,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `suggested_ratio` decimal(8,3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_nodes_recipe_id_foreign` (`recipe_id`),
  KEY `recipe_nodes_parent_node_id_foreign` (`parent_node_id`),
  KEY `recipe_nodes_catalog_item_id_foreign` (`catalog_item_id`),
  CONSTRAINT `recipe_nodes_catalog_item_id_foreign` FOREIGN KEY (`catalog_item_id`) REFERENCES `catalog_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_nodes_parent_node_id_foreign` FOREIGN KEY (`parent_node_id`) REFERENCES `recipe_nodes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recipe_nodes_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `denominazione` varchar(191) NOT NULL,
  `tipologia` varchar(191) DEFAULT NULL,
  `is_main` tinyint(1) DEFAULT 1,
  `indirizzo` varchar(191) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `fattore_rischio_calcolato` double NOT NULL DEFAULT 0,
  `giorni_prossimo_ritiro` bigint(20) NOT NULL DEFAULT 0,
  `has_muletto` tinyint(1) NOT NULL DEFAULT 0,
  `has_transpallet_el` tinyint(1) NOT NULL DEFAULT 0,
  `has_transpallet_ma` tinyint(1) NOT NULL DEFAULT 0,
  `other_machines` text NOT NULL,
  `has_adr_consultant` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sites_customer_id_foreign` (`customer_id`),
  CONSTRAINT `sites_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `timetables`;
CREATE TABLE `timetables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  `hours_array` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timetables_site_id_foreign` (`site_id`),
  CONSTRAINT `timetables_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `trailers`;
CREATE TABLE `trailers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `plate` varchar(191) DEFAULT NULL,
  `is_front_cargo` tinyint(4) DEFAULT 1,
  `load_capacity` double NOT NULL DEFAULT 11000,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `surname` varchar(191) NOT NULL DEFAULT 'cognome',
  `user_code` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `avatar` varchar(191) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `is_ragnista` tinyint(1) NOT NULL DEFAULT 0,
  `can_login` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('manager','logistic','driver','warehouse_chief','warehouse_manager','warehouse_worker','customer','developer') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_customer_id_foreign` (`customer_id`),
  CONSTRAINT `users_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `user_warehouse`;
CREATE TABLE `user_warehouse` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_warehouse_user_id_warehouse_id_unique` (`user_id`,`warehouse_id`),
  KEY `user_warehouse_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `user_warehouse_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_warehouse_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `plate` varchar(191) DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `trailer_id` bigint(20) unsigned DEFAULT NULL,
  `has_trailer` tinyint(1) NOT NULL DEFAULT 1,
  `load_capacity` double NOT NULL DEFAULT 8000,
  PRIMARY KEY (`id`),
  KEY `vehicles_driver_id_foreign` (`driver_id`),
  KEY `vehicles_trailer_id_foreign` (`trailer_id`),
  CONSTRAINT `vehicles_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `vehicles_trailer_id_foreign` FOREIGN KEY (`trailer_id`) REFERENCES `trailers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
  `version_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `versionable_id` varchar(191) NOT NULL,
  `versionable_type` varchar(191) NOT NULL,
  `user_id` varchar(191) DEFAULT NULL,
  `model_data` longtext NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`version_id`),
  KEY `versions_versionable_id_index` (`versionable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `denominazione` varchar(191) NOT NULL,
  `indirizzo` varchar(191) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `note` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `withdraws`;
CREATE TABLE `withdraws` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `withdraw_date` datetime NOT NULL,
  `residue_percentage` float DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `site_id` bigint(20) unsigned NOT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `manual_insert` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `withdraws_customer_id_foreign` (`customer_id`),
  KEY `withdraws_site_id_foreign` (`site_id`),
  KEY `withdraws_vehicle_id_foreign` (`vehicle_id`),
  KEY `withdraws_driver_id_foreign` (`driver_id`),
  KEY `withdraws_user_id_foreign` (`user_id`),
  CONSTRAINT `withdraws_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `withdraws_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `withdraws_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  CONSTRAINT `withdraws_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `withdraws_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `workers`;
CREATE TABLE `workers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `surname` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `worker_warehouse`;
CREATE TABLE `worker_warehouse` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_warehouse_worker_id_warehouse_id_unique` (`worker_id`,`warehouse_id`),
  KEY `worker_warehouse_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `worker_warehouse_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_warehouse_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2026-03-04 10:24:20
