-- Expected structure for income_logger table
-- This table should already exist in your database

CREATE TABLE IF NOT EXISTS `income_logger` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usersId` bigint(20) UNSIGNED NOT NULL,
  `taskCoin` varchar(10) NOT NULL,
  `taskType` varchar(20) NOT NULL,
  `transactionDateTime` datetime NULL DEFAULT NULL,
  `originalPhpValue` decimal(15,2) NOT NULL,
  `newPhpValue` decimal(15,2) NOT NULL,
  `delete_status` enum('active','deleted') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `income_logger_usersid_foreign` (`usersId`),
  KEY `income_logger_delete_status_index` (`delete_status`),
  KEY `income_logger_tasktype_index` (`taskType`),
  KEY `income_logger_taskcoin_index` (`taskCoin`),
  KEY `income_logger_transactiondatetime_index` (`transactionDateTime`),
  KEY `income_logger_created_at_index` (`created_at`),
  CONSTRAINT `income_logger_usersid_foreign` FOREIGN KEY (`usersId`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing (optional)
INSERT INTO `income_logger` (`usersId`, `taskCoin`, `taskType`, `transactionDateTime`, `originalPhpValue`, `newPhpValue`, `delete_status`, `created_at`, `updated_at`) VALUES
(1, 'btc', 'to buy', '2025-01-25 10:00:00', 100000.00, 105000.00, 'active', NOW(), NOW()),
(1, 'btc', 'to sell', '2025-01-25 14:30:00', 105000.00, 110000.00, 'active', NOW(), NOW()),
(1, 'eth', 'to buy', '2025-01-26 09:15:00', 50000.00, 52000.00, 'active', NOW(), NOW()),
(1, 'eth', 'to sell', '2025-01-26 16:45:00', 52000.00, 54000.00, 'active', NOW(), NOW());
