-- ============================================================
-- CSDL TAN PHAT ERP — ban xuat de ban giao
-- Sinh ngay: 03/08/2026 07:26  |  Server nguon: MySQL 8.0.44
-- So bang: 70
--
-- CACH NAP:
--   1. Tao CSDL rong (file nay KHONG co lenh CREATE DATABASE):
--      CREATE DATABASE tanphat_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Nap:  mysql -u root -p --default-character-set=utf8mb4 tanphat_php < tanphat_php.sql
--      hoac phpMyAdmin -> chon CSDL -> tab Import.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Bang `acc_accounts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `acc_accounts`;
CREATE TABLE `acc_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `is_detail` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_accounts_code` (`code`),
  KEY `idx_acc_accounts_parent` (`parent_id`),
  CONSTRAINT `fk_acc_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `acc_accounts` (`id`, `code`, `name`, `parent_id`, `type`, `is_detail`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', '111', 'Tiền mặt', NULL, 'asset', '1', '0', '1', '2026-07-18 05:49:08', NULL),
('2', '112', 'Tiền gửi ngân hàng', NULL, 'asset', '1', '1', '1', '2026-07-18 05:49:08', NULL),
('3', '131', 'Phải thu của khách hàng', NULL, 'asset', '1', '2', '1', '2026-07-18 05:49:08', NULL),
('4', '133', 'Thuế GTGT được khấu trừ', NULL, 'asset', '1', '3', '1', '2026-07-18 05:49:08', NULL),
('5', '156', 'Hàng hóa', NULL, 'asset', '1', '4', '1', '2026-07-18 05:49:08', NULL),
('6', '211', 'Tài sản cố định hữu hình', NULL, 'asset', '1', '5', '1', '2026-07-18 05:49:08', NULL),
('7', '331', 'Phải trả cho người bán', NULL, 'liability', '1', '6', '1', '2026-07-18 05:49:08', NULL),
('8', '333', 'Thuế và các khoản phải nộp NN', NULL, 'liability', '1', '7', '1', '2026-07-18 05:49:08', NULL),
('9', '334', 'Phải trả người lao động', NULL, 'liability', '1', '8', '1', '2026-07-18 05:49:08', NULL),
('10', '411', 'Vốn đầu tư của chủ sở hữu', NULL, 'equity', '1', '9', '1', '2026-07-18 05:49:08', NULL),
('11', '421', 'Lợi nhuận sau thuế chưa PP', NULL, 'equity', '1', '10', '1', '2026-07-18 05:49:08', NULL),
('12', '511', 'Doanh thu bán hàng và CCDV', NULL, 'revenue', '1', '11', '1', '2026-07-18 05:49:08', NULL),
('13', '515', 'Doanh thu hoạt động tài chính', NULL, 'revenue', '1', '12', '1', '2026-07-18 05:49:08', NULL),
('14', '632', 'Giá vốn hàng bán', NULL, 'expense', '1', '13', '1', '2026-07-18 05:49:08', NULL),
('15', '635', 'Chi phí tài chính', NULL, 'expense', '1', '14', '1', '2026-07-18 05:49:08', NULL),
('16', '641', 'Chi phí bán hàng', NULL, 'expense', '1', '15', '1', '2026-07-18 05:49:08', NULL),
('17', '642', 'Chi phí quản lý doanh nghiệp', NULL, 'expense', '1', '16', '1', '2026-07-18 05:49:08', NULL),
('18', '711', 'Thu nhập khác', NULL, 'revenue', '1', '17', '1', '2026-07-18 05:49:08', NULL),
('19', '811', 'Chi phí khác', NULL, 'expense', '1', '18', '1', '2026-07-18 05:49:08', NULL),
('20', '141', 'Tạm ứng', NULL, 'asset', '1', '19', '1', '2026-07-18 06:10:16', NULL),
('21', '138', 'Phải thu khác', NULL, 'asset', '1', '20', '1', '2026-07-18 06:10:16', NULL),
('22', '153', 'Công cụ, dụng cụ', NULL, 'asset', '1', '21', '1', '2026-07-18 06:10:16', NULL),
('23', '154', 'Chi phí SXKD dở dang (dịch vụ)', NULL, 'asset', '1', '22', '1', '2026-07-18 06:10:16', NULL),
('24', '338', 'Phải trả, phải nộp khác', NULL, 'liability', '1', '23', '1', '2026-07-18 06:10:16', NULL),
('25', '3331', 'Thuế GTGT phải nộp', NULL, 'liability', '1', '24', '1', '2026-07-18 06:10:16', NULL);

-- --------------------------------------------------------
-- Bang `acc_cost_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `acc_cost_items`;
CREATE TABLE `acc_cost_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_cost_items_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `acc_projects`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `acc_projects`;
CREATE TABLE `acc_projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_projects_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `acc_voucher_entries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `acc_voucher_entries`;
CREATE TABLE `acc_voucher_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucher_id` int NOT NULL,
  `account_id` int DEFAULT NULL,
  `debit_account_id` int DEFAULT NULL,
  `credit_account_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_item_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ave_voucher` (`voucher_id`),
  KEY `idx_ave_account` (`account_id`),
  KEY `fk_ave_cost_item` (`cost_item_id`),
  KEY `fk_ave_project` (`project_id`),
  KEY `fk_ave_debit` (`debit_account_id`),
  KEY `fk_ave_credit` (`credit_account_id`),
  CONSTRAINT `fk_ave_account` FOREIGN KEY (`account_id`) REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ave_cost_item` FOREIGN KEY (`cost_item_id`) REFERENCES `acc_cost_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ave_credit` FOREIGN KEY (`credit_account_id`) REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ave_debit` FOREIGN KEY (`debit_account_id`) REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ave_project` FOREIGN KEY (`project_id`) REFERENCES `acc_projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ave_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `acc_voucher_entries` (`id`, `voucher_id`, `account_id`, `debit_account_id`, `credit_account_id`, `amount`, `description`, `cost_item_id`, `project_id`, `create_at`) VALUES
('11', '11', NULL, '5', '7', '6000000.00', 'Nhập kho PNK-000001', NULL, NULL, '2026-07-18 11:19:08'),
('15', '13', NULL, '5', '7', '6000000.00', 'Nhập kho PNK-000001', NULL, NULL, '2026-07-18 16:38:33'),
('16', '14', NULL, '14', '5', '300000.00', 'Hàng thiếu PKK-000001', NULL, NULL, '2026-07-18 16:46:20'),
('18', '16', NULL, '5', '7', '10000000.00', 'Nhập kho PNK-000001', NULL, NULL, '2026-07-19 04:45:40'),
('19', '17', NULL, '3', '12', '1050000.00', 'Doanh thu HD-000001', NULL, NULL, '2026-07-19 04:45:42'),
('20', '17', NULL, '14', '5', '600000.00', 'Giá vốn HD-000001', NULL, NULL, '2026-07-19 04:45:42'),
('21', '18', NULL, '5', '7', '1000000.00', 'Nhập kho PNK-000001', NULL, NULL, '2026-07-19 06:36:36'),
('22', '19', NULL, '5', '7', '600000.00', 'Nhập kho PNK-000002', NULL, NULL, '2026-07-19 06:36:56'),
('23', '20', NULL, '3', '12', '950000.00', 'Doanh thu HD-000001', NULL, NULL, '2026-07-19 10:32:57'),
('24', '20', NULL, '3', '25', '95000.00', 'Thuế GTGT HD-000001', NULL, NULL, '2026-07-19 10:32:57'),
('25', '20', NULL, '14', '5', '600000.00', 'Giá vốn HD-000001', NULL, NULL, '2026-07-19 10:32:57'),
('26', '21', NULL, '5', '7', '76550000.00', 'Nhập kho PNK-000001', NULL, NULL, '2026-07-19 11:11:57'),
('27', '22', NULL, '5', '7', '63200000.00', 'Nhập kho PNK-000002', NULL, NULL, '2026-07-19 11:11:57'),
('28', '23', NULL, '5', '7', '91040000.00', 'Nhập kho PNK-000003', NULL, NULL, '2026-07-19 11:11:57'),
('29', '24', NULL, '3', '12', '4473040.00', 'Doanh thu HD-000001', NULL, NULL, '2026-07-19 11:11:57'),
('30', '24', NULL, '3', '25', '447304.00', 'Thuế GTGT HD-000001', NULL, NULL, '2026-07-19 11:11:57'),
('31', '24', NULL, '14', '5', '3420000.00', 'Giá vốn HD-000001', NULL, NULL, '2026-07-19 11:11:57'),
('32', '25', NULL, '3', '12', '10488000.00', 'Doanh thu HD-000002', NULL, NULL, '2026-07-19 11:11:57'),
('33', '25', NULL, '3', '25', '1048800.00', 'Thuế GTGT HD-000002', NULL, NULL, '2026-07-19 11:11:57'),
('34', '25', NULL, '14', '5', '8250000.00', 'Giá vốn HD-000002', NULL, NULL, '2026-07-19 11:11:57'),
('35', '26', NULL, '3', '12', '1154000.00', 'Doanh thu HD-000003', NULL, NULL, '2026-07-19 11:11:57'),
('36', '26', NULL, '3', '25', '115400.00', 'Thuế GTGT HD-000003', NULL, NULL, '2026-07-19 11:11:57'),
('37', '26', NULL, '14', '5', '820000.00', 'Giá vốn HD-000003', NULL, NULL, '2026-07-19 11:11:57');

-- --------------------------------------------------------
-- Bang `acc_vouchers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `acc_vouchers`;
CREATE TABLE `acc_vouchers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_date` date NOT NULL,
  `cash_account_id` int DEFAULT NULL,
  `partner_id` int DEFAULT NULL,
  `partner_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acc_vouchers_no` (`voucher_no`),
  KEY `idx_acc_vouchers_date` (`voucher_date`),
  KEY `idx_acc_vouchers_cash` (`cash_account_id`),
  KEY `idx_acc_vouchers_partner` (`partner_id`),
  CONSTRAINT `fk_acc_vouchers_cash` FOREIGN KEY (`cash_account_id`) REFERENCES `acc_accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_acc_vouchers_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `acc_vouchers` (`id`, `voucher_no`, `voucher_type`, `voucher_date`, `cash_account_id`, `partner_id`, `partner_name`, `reason`, `amount`, `status`, `created_by`, `create_at`, `update_at`) VALUES
('11', 'PKT-000001', 'ke_toan', '2026-07-10', NULL, NULL, NULL, 'Tự động từ phiếu nhập PNK-000001', '6000000.00', '1', NULL, '2026-07-18 11:19:08', NULL),
('13', 'PKT-000002', 'ke_toan', '2026-07-10', NULL, NULL, NULL, 'Tự động từ phiếu nhập PNK-000001', '6000000.00', '1', NULL, '2026-07-18 16:38:33', NULL),
('14', 'PKT-000003', 'ke_toan', '2026-07-13', NULL, NULL, NULL, 'Tự động từ kiểm kê PKK-000001', '300000.00', '1', NULL, '2026-07-18 16:46:20', NULL),
('16', 'PKT-000004', 'ke_toan', '2026-07-10', NULL, NULL, NULL, 'Tự động từ phiếu nhập PNK-000001', '10000000.00', '1', NULL, '2026-07-19 04:45:40', NULL),
('17', 'PKT-000005', 'ke_toan', '2026-07-19', NULL, NULL, 'Khach Web', 'Tự động từ hoá đơn HD-000001', '1050000.00', '1', NULL, '2026-07-19 04:45:42', NULL),
('18', 'PKT-000006', 'ke_toan', '2026-05-01', NULL, NULL, NULL, 'Tự động từ phiếu nhập PNK-000001', '1000000.00', '1', NULL, '2026-07-19 06:36:36', NULL),
('19', 'PKT-000007', 'ke_toan', '2026-06-15', NULL, NULL, NULL, 'Tự động từ phiếu nhập PNK-000002', '600000.00', '1', NULL, '2026-07-19 06:36:56', NULL),
('20', 'PKT-000008', 'ke_toan', '2026-07-19', NULL, NULL, NULL, 'Tự động từ hoá đơn HD-000001', '1045000.00', '1', NULL, '2026-07-19 10:32:57', NULL),
('21', 'PKT-000009', 'ke_toan', '2026-03-05', NULL, '8', 'Công ty Bosch Việt Nam', 'Tự động từ phiếu nhập PNK-000001', '76550000.00', '1', NULL, '2026-07-19 11:11:57', NULL),
('22', 'PKT-000010', 'ke_toan', '2026-04-12', NULL, '9', 'NCC Phụ tùng Miền Bắc', 'Tự động từ phiếu nhập PNK-000002', '63200000.00', '1', NULL, '2026-07-19 11:11:57', NULL),
('23', 'PKT-000011', 'ke_toan', '2026-05-20', NULL, '8', 'Công ty Bosch Việt Nam', 'Tự động từ phiếu nhập PNK-000003', '91040000.00', '1', NULL, '2026-07-19 11:11:57', NULL),
('24', 'PKT-000012', 'ke_toan', '2026-06-10', NULL, '4', 'Garage Thành Công', 'Tự động từ hoá đơn HD-000001', '4920344.00', '1', NULL, '2026-07-19 11:11:57', NULL),
('25', 'PKT-000013', 'ke_toan', '2026-06-25', NULL, '5', 'Đại lý phụ tùng Phú Sơn', 'Tự động từ hoá đơn HD-000002', '11536800.00', '1', NULL, '2026-07-19 11:11:57', NULL),
('26', 'PKT-000014', 'ke_toan', '2026-07-08', NULL, '6', 'Anh Nguyễn Văn Hùng', 'Tự động từ hoá đơn HD-000003', '1269400.00', '1', NULL, '2026-07-19 11:11:57', NULL);

-- --------------------------------------------------------
-- Bang `banners`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_banners_status_sort` (`status`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banners` (`id`, `title`, `image`, `link`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'ssss', 'public/assets/uploads/banners/ssss-09481031.jpg', NULL, '0', '1', '2026-07-28 10:05:26', '2026-07-28 10:11:31');

-- --------------------------------------------------------
-- Bang `car_body_types`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_body_types`;
CREATE TABLE `car_body_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_body_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_body_types` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Sedan', 'sedan', '0', '1', '2026-07-18 04:24:32', NULL),
('2', 'Hatchback', 'hatchback', '1', '1', '2026-07-18 04:24:32', NULL),
('3', 'SUV', 'suv', '2', '1', '2026-07-18 04:24:32', NULL),
('4', 'Crossover', 'crossover', '3', '1', '2026-07-18 04:24:32', NULL),
('5', 'MPV', 'mpv', '4', '1', '2026-07-18 04:24:32', NULL),
('6', 'Bán tải', 'ban-tai', '5', '1', '2026-07-18 04:24:32', NULL);

-- --------------------------------------------------------
-- Bang `car_brands`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_brands`;
CREATE TABLE `car_brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_brands_slug` (`slug`),
  KEY `idx_car_brands_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_brands` (`id`, `name`, `slug`, `logo`, `country`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Toyota', 'toyota', NULL, 'Nhật Bản', '0', '1', '2026-07-18 04:24:32', NULL),
('2', 'Honda', 'honda', NULL, 'Nhật Bản', '1', '1', '2026-07-18 04:24:32', NULL),
('3', 'Kia', 'kia', NULL, 'Hàn Quốc', '2', '1', '2026-07-18 04:24:32', NULL),
('4', 'Hyundai', 'hyundai', NULL, 'Hàn Quốc', '3', '1', '2026-07-18 04:24:32', NULL),
('5', 'Mazda', 'mazda', NULL, 'Nhật Bản', '4', '1', '2026-07-18 04:24:32', NULL),
('6', 'Ford', 'ford', NULL, 'Mỹ', '5', '1', '2026-07-18 04:24:32', NULL),
('337', 'Vinfast', 'vinfast', NULL, 'Việt Nam', '6', '1', '2026-07-27 11:37:08', '2026-07-27 11:38:08');

-- --------------------------------------------------------
-- Bang `car_colors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_colors`;
CREATE TABLE `car_colors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hex` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_colors_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_colors` (`id`, `name`, `slug`, `hex`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Trắng', 'trang', '#FFFFFF', '0', '1', '2026-07-18 04:24:32', NULL),
('2', 'Đen', 'den', '#000000', '1', '1', '2026-07-18 04:24:32', NULL),
('3', 'Bạc', 'bac', '#C0C0C0', '2', '1', '2026-07-18 04:24:32', NULL),
('4', 'Xám', 'xam', '#808080', '3', '1', '2026-07-18 04:24:32', NULL),
('5', 'Đỏ', 'do', '#FF0000', '4', '1', '2026-07-18 04:24:32', NULL),
('6', 'Xanh', 'xanh', '#0000FF', '5', '1', '2026-07-18 04:24:32', NULL);

-- --------------------------------------------------------
-- Bang `car_fuels`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_fuels`;
CREATE TABLE `car_fuels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_fuels_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_fuels` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Xăng', 'xang', '0', '1', '2026-07-18 04:24:32', NULL),
('2', 'Dầu (Diesel)', 'dau-diesel', '1', '1', '2026-07-18 04:24:32', NULL),
('3', 'Điện', 'dien', '2', '1', '2026-07-18 04:24:32', NULL),
('4', 'Hybrid', 'hybrid', '3', '1', '2026-07-18 04:24:32', NULL),
('5', 'xxxx', 'xxxx', '0', '1', '2026-07-18 04:27:27', NULL),
('6', 'ccc', 'cc', '0', '1', '2026-07-18 04:27:34', NULL);

-- --------------------------------------------------------
-- Bang `car_models`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_models`;
CREATE TABLE `car_models` (
  `id` int NOT NULL AUTO_INCREMENT,
  `brand_id` int NOT NULL,
  `body_type_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_models_brand_slug` (`brand_id`,`slug`),
  KEY `idx_car_models_brand` (`brand_id`),
  KEY `idx_car_models_body` (`body_type_id`),
  CONSTRAINT `fk_car_models_body` FOREIGN KEY (`body_type_id`) REFERENCES `car_body_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_car_models_brand` FOREIGN KEY (`brand_id`) REFERENCES `car_brands` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_models` (`id`, `brand_id`, `body_type_id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('254', '1', '1', 'Vios', 'toyota-vios', '0', '1', '2026-07-19 11:11:56', NULL),
('255', '1', '1', 'Camry', 'toyota-camry', '0', '1', '2026-07-19 11:11:56', NULL),
('256', '1', '3', 'Fortuner', 'toyota-fortuner', '0', '1', '2026-07-19 11:11:56', NULL),
('257', '1', '5', 'Innova', 'toyota-innova', '0', '1', '2026-07-19 11:11:56', NULL),
('258', '2', '1', 'City', 'honda-city', '0', '1', '2026-07-19 11:11:56', NULL),
('259', '2', '3', 'CR-V', 'honda-cr-v', '0', '1', '2026-07-19 11:11:56', NULL),
('260', '2', '1', 'Civic', 'honda-civic', '0', '1', '2026-07-19 11:11:56', NULL),
('261', '3', '2', 'Morning', 'kia-morning', '0', '1', '2026-07-19 11:11:56', NULL),
('262', '3', '4', 'Seltos', 'kia-seltos', '0', '1', '2026-07-19 11:11:56', NULL),
('263', '4', '1', 'Accent', 'hyundai-accent', '0', '1', '2026-07-19 11:11:56', NULL),
('264', '4', '3', 'Santa Fe', 'hyundai-santa-fe', '0', '1', '2026-07-19 11:11:56', NULL),
('265', '5', '1', 'Mazda3', 'mazda-mazda3', '0', '1', '2026-07-19 11:11:56', NULL),
('266', '5', '4', 'CX-5', 'mazda-cx-5', '0', '1', '2026-07-19 11:11:56', NULL),
('267', '6', '6', 'Ranger', 'ford-ranger', '0', '1', '2026-07-19 11:11:56', NULL),
('268', '6', '3', 'Everest', 'ford-everest', '0', '1', '2026-07-19 11:11:56', NULL),
('281', '337', NULL, 'VF5', 'vf5', '0', '1', '2026-07-27 11:38:47', NULL);

-- --------------------------------------------------------
-- Bang `car_years`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `car_years`;
CREATE TABLE `car_years` (
  `id` int NOT NULL AUTO_INCREMENT,
  `model_id` int NOT NULL,
  `year_from` smallint NOT NULL,
  `year_to` smallint DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_car_years_model` (`model_id`),
  KEY `idx_car_years_range` (`year_from`,`year_to`),
  CONSTRAINT `fk_car_years_model` FOREIGN KEY (`model_id`) REFERENCES `car_models` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `car_years` (`id`, `model_id`, `year_from`, `year_to`, `name`, `status`, `create_at`, `update_at`) VALUES
('318', '254', '2014', '2018', 'Vios 2014-2018', '1', '2026-07-19 11:11:56', NULL),
('319', '254', '2018', '2023', 'Vios 2018-2023', '1', '2026-07-19 11:11:56', NULL),
('320', '254', '2023', NULL, 'Vios 2023+', '1', '2026-07-19 11:11:56', NULL),
('321', '255', '2015', '2019', 'Camry 2015-2019', '1', '2026-07-19 11:11:56', NULL),
('322', '255', '2019', NULL, 'Camry 2019+', '1', '2026-07-19 11:11:56', NULL),
('323', '256', '2016', '2020', 'Fortuner 2016-2020', '1', '2026-07-19 11:11:56', NULL),
('324', '256', '2020', NULL, 'Fortuner 2020+', '1', '2026-07-19 11:11:56', NULL),
('325', '257', '2016', '2023', 'Innova 2016-2023', '1', '2026-07-19 11:11:56', NULL),
('326', '258', '2014', '2020', 'City 2014-2020', '1', '2026-07-19 11:11:56', NULL),
('327', '258', '2020', NULL, 'City 2020+', '1', '2026-07-19 11:11:56', NULL),
('328', '259', '2017', '2022', 'CR-V 2017-2022', '1', '2026-07-19 11:11:56', NULL),
('329', '259', '2022', NULL, 'CR-V 2022+', '1', '2026-07-19 11:11:56', NULL),
('330', '260', '2016', '2021', 'Civic 2016-2021', '1', '2026-07-19 11:11:56', NULL),
('331', '260', '2021', NULL, 'Civic 2021+', '1', '2026-07-19 11:11:56', NULL),
('332', '261', '2015', '2020', 'Morning 2015-2020', '1', '2026-07-19 11:11:56', NULL),
('333', '261', '2020', NULL, 'Morning 2020+', '1', '2026-07-19 11:11:56', NULL),
('334', '262', '2020', NULL, 'Seltos 2020+', '1', '2026-07-19 11:11:56', NULL),
('335', '263', '2018', '2023', 'Accent 2018-2023', '1', '2026-07-19 11:11:56', NULL),
('336', '263', '2023', NULL, 'Accent 2023+', '1', '2026-07-19 11:11:56', NULL),
('337', '264', '2019', NULL, 'Santa Fe 2019+', '1', '2026-07-19 11:11:56', NULL),
('338', '265', '2015', '2019', 'Mazda3 2015-2019', '1', '2026-07-19 11:11:56', NULL),
('339', '265', '2019', NULL, 'Mazda3 2019+', '1', '2026-07-19 11:11:56', NULL),
('340', '266', '2017', '2022', 'CX-5 2017-2022', '1', '2026-07-19 11:11:56', NULL),
('341', '266', '2022', NULL, 'CX-5 2022+', '1', '2026-07-19 11:11:56', NULL),
('342', '267', '2015', '2022', 'Ranger 2015-2022', '1', '2026-07-19 11:11:56', NULL),
('343', '267', '2022', NULL, 'Ranger 2022+', '1', '2026-07-19 11:11:56', NULL),
('344', '268', '2018', '2022', 'Everest 2018-2022', '1', '2026-07-19 11:11:56', NULL),
('345', '268', '2022', NULL, 'Everest 2022+', '1', '2026-07-19 11:11:56', NULL),
('361', '281', '2023', NULL, 'VF5 2023+', '1', '2026-07-27 11:39:25', NULL);

-- --------------------------------------------------------
-- Bang `chat_conversations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `chat_conversations`;
CREATE TABLE `chat_conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int DEFAULT NULL,
  `session_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `unread` tinyint(1) NOT NULL DEFAULT '0',
  `last_message_at` datetime DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_session` (`session_key`),
  KEY `idx_chat_member` (`member_id`),
  CONSTRAINT `fk_chat_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chat_conversations` (`id`, `member_id`, `session_key`, `guest_name`, `guest_phone`, `status`, `unread`, `last_message_at`, `create_at`, `update_at`) VALUES
('2', NULL, 'e11c867ea06d9d70c5ca25b118c0c0685a06e0a5b25c3dd62249f68179657417', 'he', 'ágd', 'open', '0', '2026-07-27 12:18:11', '2026-07-27 12:02:20', '2026-07-27 12:40:45'),
('3', '4', '0abf82e6c91d7e334c98c6e2a09a06cc1bfd2a5275803660e6778e7dc528bb15', 'dsfg3', 'd', 'closed', '0', '2026-07-27 12:42:58', '2026-07-27 12:41:06', '2026-07-27 12:43:43'),
('4', NULL, '46076b1d7b5837894c9b18cb05bea1235d3e186cacc4d727e2c739ceaca9fe09', 'Luận', '0339513657', 'open', '0', '2026-07-28 10:12:11', '2026-07-28 10:12:11', '2026-07-28 10:12:26'),
('5', NULL, '107047c813899e9cfb7b15c3ebc5cace0d8173524d287e734da603020c1c38ab', NULL, NULL, 'open', '1', '2026-07-29 13:29:11', '2026-07-29 13:29:11', '2026-07-29 13:29:11');

-- --------------------------------------------------------
-- Bang `chat_messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `sender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `body` text COLLATE utf8mb4_unicode_ci,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv` (`conversation_id`,`id`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender`, `body`, `create_at`) VALUES
('3', '2', 'customer', 'hello', '2026-07-27 12:02:20'),
('4', '2', 'customer', 'đf', '2026-07-27 12:02:24'),
('5', '2', 'customer', 'd', '2026-07-27 12:04:01'),
('6', '2', 'customer', 'sgfá', '2026-07-27 12:18:11'),
('7', '3', 'customer', '222', '2026-07-27 12:41:06'),
('8', '3', 'staff', 'dsfs', '2026-07-27 12:42:58'),
('9', '4', 'customer', 'Bái ga', '2026-07-28 10:12:11'),
('10', '5', 'customer', 'hh', '2026-07-29 13:29:11');

-- --------------------------------------------------------
-- Bang `contact_messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `ip`, `create_at`, `update_at`) VALUES
('2', 'Nguyễn Văn Khách', 'khach1@gmail.com', '0912000111', 'Hỏi giá má phanh Vios', 'Cho mình hỏi giá má phanh trước Vios đời 2020 còn hàng không?', 'new', '127.0.0.1', '2026-07-19 11:11:56', NULL),
('3', 'Trần Thị Mai', NULL, '0912000222', 'Tư vấn lọc gió', 'Xe Honda City 2019 dùng loại lọc gió nào ạ?', 'new', '127.0.0.1', '2026-07-19 11:11:56', NULL),
('4', 'Gara Hoàng Long', 'hoanglong@gara.vn', '0912000333', 'Đặt hàng số lượng lớn', 'Bên mình muốn đặt 50 bộ má phanh, báo giá sỉ giúp.', 'handled', '127.0.0.1', '2026-07-19 11:11:56', NULL),
('5', 'Lê Quốc Bảo', 'bao.le@gmail.com', '0912000444', 'Khiếu nại bảo hành', 'Ắc quy mua tháng trước bị yếu, nhờ kiểm tra bảo hành.', 'handled', '127.0.0.1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `customer_groups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `customer_groups`;
CREATE TABLE `customer_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_groups` (`id`, `name`, `discount_percent`, `note`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Khách lẻ', '0.00', NULL, '0', '1', '2026-07-18 14:39:18', NULL),
('2', 'Đại lý', '5.00', NULL, '1', '1', '2026-07-18 14:39:18', NULL),
('3', 'Garage đối tác', '8.00', NULL, '2', '1', '2026-07-18 14:39:18', NULL);

-- --------------------------------------------------------
-- Bang `departments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `code`, `name`, `description`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'KD', 'Phòng Kinh doanh', NULL, '0', '1', '2026-07-19 04:00:53', NULL),
('2', 'KHO', 'Phòng Kho vận', NULL, '1', '1', '2026-07-19 04:00:53', NULL),
('3', 'KT', 'Phòng Kế toán', NULL, '2', '1', '2026-07-19 04:00:53', NULL),
('4', 'KT2', 'Phòng Kỹ thuật', NULL, '3', '1', '2026-07-19 04:00:53', NULL);

-- --------------------------------------------------------
-- Bang `employees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int DEFAULT NULL,
  `position_id` int DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary_base` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_code` (`code`),
  KEY `idx_emp_dept` (`department_id`),
  KEY `idx_emp_pos` (`position_id`),
  CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_emp_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `employees` (`id`, `code`, `name`, `department_id`, `position_id`, `gender`, `dob`, `phone`, `email`, `address`, `hire_date`, `salary_base`, `status`, `note`, `create_at`, `update_at`) VALUES
('2', 'NV-001', 'Nguyễn Văn An', '1', '1', 'Nam', '1988-04-12', '0901111001', 'an.nv@tanphat.vn', 'Hà Nội', '2020-01-15', '15000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('3', 'NV-002', 'Trần Thị Bình', '1', '2', 'Nữ', '1992-08-20', '0901111002', 'binh.tt@tanphat.vn', 'Hà Nội', '2021-03-01', '12000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('4', 'NV-003', 'Lê Hoàng Cường', '2', '3', 'Nam', '1990-11-05', '0901111003', 'cuong.lh@tanphat.vn', 'Hà Nội', '2019-06-10', '13000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('5', 'NV-004', 'Phạm Thị Dung', '3', '4', 'Nữ', '1991-02-28', '0901111004', 'dung.pt@tanphat.vn', 'Hà Nội', '2020-09-01', '14000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('6', 'NV-005', 'Hoàng Văn Em', '4', '1', 'Nam', '1987-07-17', '0901111005', 'em.hv@tanphat.vn', 'Hà Nội', '2018-02-20', '16000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('7', 'NV-006', 'Vũ Thị Giang', '4', '2', 'Nữ', '1994-12-03', '0901111006', 'giang.vt@tanphat.vn', 'Hà Nội', '2022-05-15', '11000000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('8', 'NV-007', 'Đặng Quốc Huy', '2', '3', 'Nam', '1993-05-25', '0901111007', 'huy.dq@tanphat.vn', 'Hà Nội', '2021-11-08', '11500000.00', '1', NULL, '2026-07-19 11:11:56', NULL),
('9', 'NV-008', 'Bùi Thị Lan', '3', '4', 'Nữ', '1995-09-14', '0901111008', 'lan.bt@tanphat.vn', 'Hà Nội', '2023-01-03', '10500000.00', '1', NULL, '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `galleries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `galleries`;
CREATE TABLE `galleries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(230) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gallery_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `galleries` (`id`, `name`, `slug`, `description`, `cover`, `is_published`, `sort_order`, `create_at`, `update_at`) VALUES
('2', 'Hình ảnh kho hàng', 'hinh-anh-kho-hang', 'Kho phụ tùng Tân Phát', NULL, '1', '0', '2026-07-19 11:11:56', NULL),
('3', 'Hoạt động công ty', 'hoat-dong-cong-ty', 'Sự kiện &#38;#38; đội ngũ Tân Phát', 'public/assets/uploads/galleries/hoat-dong-cong-ty-c4f1d94f.png', '1', '0', '2026-07-19 11:11:56', '2026-07-27 13:03:34');

-- --------------------------------------------------------
-- Bang `gallery_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `gallery_items`;
CREATE TABLE `gallery_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gallery_id` int NOT NULL,
  `media_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gi_gallery` (`gallery_id`),
  CONSTRAINT `fk_gi_gallery` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `gallery_items` (`id`, `gallery_id`, `media_type`, `image`, `video_url`, `caption`, `sort_order`, `create_at`) VALUES
('3', '2', 'video', NULL, 'dQw4w9WgXcQ', 'Video giới thiệu', '0', '2026-07-19 11:11:56'),
('4', '3', 'video', NULL, 'dQw4w9WgXcQ', 'Video giới thiệu', '0', '2026-07-19 11:11:56'),
('5', '3', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, '0', '2026-07-27 13:02:15'),
('6', '3', 'image', 'public/assets/uploads/galleries/hoat-dong-cong-ty-8edca69f.jpg', NULL, NULL, '0', '2026-07-27 13:03:31');

-- --------------------------------------------------------
-- Bang `goods_issue_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `goods_issue_items`;
CREATE TABLE `goods_issue_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `issue_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ii_issue` (`issue_id`),
  KEY `idx_ii_part` (`part_id`),
  CONSTRAINT `fk_ii_issue` FOREIGN KEY (`issue_id`) REFERENCES `goods_issues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ii_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `goods_issues`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `goods_issues`;
CREATE TABLE `goods_issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `issue_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'xuat_ban',
  `warehouse_id` int NOT NULL,
  `partner_id` int DEFAULT NULL,
  `partner_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_account_id` int DEFAULT NULL,
  `issue_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `acc_voucher_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_issue_no` (`issue_no`),
  KEY `idx_issue_wh` (`warehouse_id`),
  KEY `idx_issue_status` (`status`),
  KEY `fk_issue_partner` (`partner_id`),
  KEY `fk_issue_counter` (`counter_account_id`),
  KEY `fk_issue_voucher` (`acc_voucher_id`),
  CONSTRAINT `fk_issue_counter` FOREIGN KEY (`counter_account_id`) REFERENCES `acc_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_voucher` FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_issue_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `goods_receipt_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `goods_receipt_items`;
CREATE TABLE `goods_receipt_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ri_receipt` (`receipt_id`),
  KEY `idx_ri_part` (`part_id`),
  KEY `fk_ri_location` (`location_id`),
  CONSTRAINT `fk_ri_location` FOREIGN KEY (`location_id`) REFERENCES `warehouse_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ri_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ri_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goods_receipt_items` (`id`, `receipt_id`, `part_id`, `quantity`, `unit_cost`, `amount`, `location`, `location_id`, `note`) VALUES
('9', '8', '364', '40.000', '420000.00', '16800000.00', 'Khu A / Tầng 1 / Kệ 1', NULL, NULL),
('10', '8', '366', '25.000', '650000.00', '16250000.00', 'Khu A / Tầng 1 / Kệ 1', NULL, NULL),
('11', '8', '371', '30.000', '1050000.00', '31500000.00', 'Khu A / Tầng 2 / Kệ 1', NULL, NULL),
('12', '8', '379', '20.000', '600000.00', '12000000.00', 'Khu A / Tầng 1 / Kệ 2', NULL, NULL),
('13', '9', '367', '120.000', '70000.00', '8400000.00', NULL, NULL, NULL),
('14', '9', '368', '80.000', '110000.00', '8800000.00', NULL, NULL, NULL),
('15', '9', '369', '100.000', '130000.00', '13000000.00', NULL, NULL, NULL),
('16', '9', '370', '15.000', '560000.00', '8400000.00', NULL, NULL, NULL),
('17', '9', '376', '90.000', '80000.00', '7200000.00', NULL, NULL, NULL),
('18', '9', '377', '70.000', '120000.00', '8400000.00', NULL, NULL, NULL),
('19', '9', '378', '60.000', '150000.00', '9000000.00', NULL, NULL, NULL),
('20', '10', '365', '22.000', '480000.00', '10560000.00', NULL, NULL, NULL),
('21', '10', '372', '12.000', '1900000.00', '22800000.00', NULL, NULL, NULL),
('22', '10', '373', '8.000', '2900000.00', '23200000.00', NULL, NULL, NULL),
('23', '10', '374', '16.000', '1450000.00', '23200000.00', NULL, NULL, NULL),
('24', '10', '375', '24.000', '470000.00', '11280000.00', NULL, NULL, NULL);

-- --------------------------------------------------------
-- Bang `goods_receipts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `goods_receipts`;
CREATE TABLE `goods_receipts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nhap_mua',
  `warehouse_id` int NOT NULL,
  `partner_id` int DEFAULT NULL,
  `partner_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_account_id` int DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `acc_voucher_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipt_no` (`receipt_no`),
  KEY `idx_receipt_wh` (`warehouse_id`),
  KEY `idx_receipt_status` (`status`),
  KEY `fk_receipt_partner` (`partner_id`),
  KEY `fk_receipt_counter` (`counter_account_id`),
  KEY `fk_receipt_voucher` (`acc_voucher_id`),
  CONSTRAINT `fk_receipt_counter` FOREIGN KEY (`counter_account_id`) REFERENCES `acc_accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_voucher` FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_receipt_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goods_receipts` (`id`, `receipt_no`, `receipt_type`, `warehouse_id`, `partner_id`, `partner_name`, `counter_account_id`, `receipt_date`, `reason`, `total_amount`, `status`, `acc_voucher_id`, `created_by`, `create_at`, `update_at`) VALUES
('8', 'PNK-000001', 'nhap_mua', '1', '8', 'Công ty Bosch Việt Nam', '7', '2026-03-05', 'Nhập mua hàng', '76550000.00', '1', '21', '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('9', 'PNK-000002', 'nhap_mua', '1', '9', 'NCC Phụ tùng Miền Bắc', '7', '2026-04-12', 'Nhập mua hàng', '63200000.00', '1', '22', '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('10', 'PNK-000003', 'nhap_mua', '1', '8', 'Công ty Bosch Việt Nam', '7', '2026-05-20', 'Nhập mua hàng', '91040000.00', '1', '23', '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57');

-- --------------------------------------------------------
-- Bang `groups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `groups` (`id`, `name`, `create_at`, `update_at`) VALUES
('9', 'Admin', NULL, '2026-07-27 12:39:06'),
('10', 'Manager', NULL, NULL),
('11', 'Staff', NULL, NULL);

-- --------------------------------------------------------
-- Bang `leave_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `leave_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'annual',
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` decimal(4,1) NOT NULL DEFAULT '1.0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approver_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_leave_emp` (`employee_id`),
  KEY `idx_leave_status` (`status`),
  CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `from_date`, `to_date`, `days`, `reason`, `status`, `approver_note`, `created_by`, `create_at`, `update_at`) VALUES
('2', '3', 'Nghỉ phép năm', '2026-07-22', '2026-07-24', '3.0', 'Về quê', 'pending', NULL, '1', '2026-07-19 11:11:56', NULL),
('3', '6', 'Nghỉ ốm', '2026-06-10', '2026-06-11', '2.0', 'Khám bệnh', 'approved', NULL, '1', '2026-07-19 11:11:56', NULL),
('4', '8', 'Nghỉ phép năm', '2026-05-02', '2026-05-03', '2.0', 'Việc gia đình', 'approved', NULL, '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `login_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_tokens`;
CREATE TABLE `login_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime NOT NULL,
  `client_ip` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_tokens_token` (`token`),
  KEY `fk_login_tokens_user` (`user_id`),
  CONSTRAINT `fk_login_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `login_tokens` (`id`, `user_id`, `token`, `create_at`, `client_ip`, `current_activity`) VALUES
('274', '15', '38f01dbd890d357d625b3bd11a62f36e5b33f6a41b5adb18a2bfb1c7891a97e6', '2026-08-03 07:24:25', '127.0.0.1', '2026-08-03 07:24:25');

-- --------------------------------------------------------
-- Bang `members`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_members_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `members` (`id`, `email`, `password`, `name`, `phone`, `address`, `status`, `create_at`, `update_at`) VALUES
('3', 'df@gmail.com', '$2y$10$z3UckGsPban6S3rcCidzVeVOi5vpB78pfImUIH2clV1u5de4qSpve', 'sdfs', '5', NULL, '1', '2026-07-27 12:27:00', NULL),
('4', 'hello@gmail.com', '$2y$10$CbOLeBomCwksntQ/BFqqXepJKZrx8i0wzmwvdau71salGdMsGmmIq', '123', '', NULL, '1', '2026-07-27 12:29:13', NULL),
('5', 'hello1@gmail.com', '$2y$10$GYM52jNdTftejCilU2hhX.gO388J8SZG9sSyW4FnaIuiBC6r0b5Jy', '987', '', NULL, '1', '2026-07-27 12:29:48', NULL);

-- --------------------------------------------------------
-- Bang `menus`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '_self',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_menu_parent` (`parent_id`),
  CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menus` (`id`, `parent_id`, `label`, `url`, `target`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', NULL, 'Trang chủ', '', '_self', '0', '1', '2026-07-19 05:15:15', NULL),
('2', NULL, 'Sản phẩm', 'san-pham', '_self', '2', '1', '2026-07-19 05:15:15', NULL),
('3', NULL, 'Khuyến mãi', 'khuyen-mai', '_self', '3', '1', '2026-07-19 05:15:15', '2026-08-03 07:23:10'),
('4', NULL, 'Dự án', 'du-an', '_self', '4', '1', '2026-07-19 05:15:15', NULL),
('5', NULL, 'Thư viện', 'thu-vien', '_self', '5', '1', '2026-07-19 05:15:15', NULL),
('6', NULL, 'Tin tức', 'tin-tuc', '_self', '6', '1', '2026-07-19 05:15:15', NULL),
('9', NULL, 'Giới thiệu', 'gioi-thieu', '_self', '1', '1', '2026-08-03 07:23:10', NULL);

-- --------------------------------------------------------
-- Bang `migrations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  `ran_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`, `ran_at`) VALUES
('1', '2026_07_17_000001_create_base_tables', '1', '2026-07-18 04:24:31'),
('2', '2026_07_17_000002_fix_password_column_length', '1', '2026-07-18 04:24:32'),
('3', '2026_07_17_000003_convert_tables_to_utf8mb4', '1', '2026-07-18 04:24:32'),
('4', '2026_07_17_000004_create_car_catalog_tables', '1', '2026-07-18 04:24:32'),
('5', '2026_07_17_000005_create_parts_tables', '1', '2026-07-18 04:24:33'),
('6', '2026_07_17_000006_register_catalog_modules', '1', '2026-07-18 04:24:33'),
('7', '2026_07_18_000007_register_relational_modules', '1', '2026-07-18 04:24:33'),
('8', '2026_07_18_000008_grant_products_crud', '2', '2026-07-18 04:37:05'),
('9', '2026_07_18_000009_create_part_images', '3', '2026-07-18 04:49:39'),
('10', '2026_07_18_000010_create_part_related', '4', '2026-07-18 05:08:59'),
('11', '2026_07_18_000011_create_attributes', '5', '2026-07-18 05:27:04'),
('12', '2026_07_18_000012_create_accounting_kt1_kt2', '6', '2026-07-18 05:49:08'),
('13', '2026_07_18_000013_accounting_kt3_journal', '7', '2026-07-18 06:10:16'),
('14', '2026_07_18_000014_accounting_kt4_partners', '8', '2026-07-18 06:21:12'),
('15', '2026_07_18_000015_accounting_kt5_books', '9', '2026-07-18 06:25:37'),
('16', '2026_07_18_000016_create_warehouse_tables', '10', '2026-07-18 10:20:14'),
('17', '2026_07_18_000017_create_sales_tables', '11', '2026-07-18 11:18:24'),
('18', '2026_07_18_000018_create_members', '12', '2026-07-18 14:09:37'),
('19', '2026_07_18_000019_create_cskh_tables', '13', '2026-07-18 14:39:18'),
('20', '2026_07_18_000020_create_warehouse_ops', '14', '2026-07-18 16:38:01'),
('21', '2026_07_18_000021_create_cms_tables', '15', '2026-07-18 18:29:25'),
('22', '2026_07_19_000022_create_hr_tables', '16', '2026-07-19 04:00:54'),
('23', '2026_07_19_000023_create_seo_settings', '17', '2026-07-19 04:22:20'),
('24', '2026_07_19_000024_create_orders', '18', '2026-07-19 04:31:45'),
('25', '2026_07_19_000025_link_order_invoice', '19', '2026-07-19 04:45:02'),
('26', '2026_07_19_000026_create_galleries', '20', '2026-07-19 04:57:08'),
('27', '2026_07_19_000027_create_menus', '21', '2026-07-19 05:15:15'),
('28', '2026_07_19_000028_create_visits', '22', '2026-07-19 05:21:58'),
('29', '2026_07_19_000029_create_chat', '23', '2026-07-19 05:32:53'),
('30', '2026_07_19_000030_create_warehouse_locations', '24', '2026-07-19 06:00:03'),
('31', '2026_07_19_000031_create_warranty_handovers', '24', '2026-07-19 06:00:03'),
('32', '2026_07_19_000032_receipt_location_and_movement', '25', '2026-07-19 06:35:38'),
('33', '2026_07_19_000033_create_newsletter_contact', '26', '2026-07-19 06:42:22'),
('34', '2026_07_19_000034_warranty_maintenance', '27', '2026-07-19 08:00:35'),
('35', '2026_07_19_000035_sales_discount_einvoice', '28', '2026-07-19 10:31:56'),
('36', '2026_07_19_000036_create_stock_reservations', '29', '2026-07-19 10:37:09'),
('37', '2026_07_25_000041_create_banners', '30', '2026-07-27 16:29:50'),
('38', '2026_07_25_000040_add_category_image', '31', '2026-08-03 07:23:10'),
('39', '2026_07_28_000042_menu_khuyen_mai_gioi_thieu', '31', '2026-08-03 07:23:10'),
('40', '2026_07_28_000043_grant_add_users_groups', '31', '2026-08-03 07:23:10'),
('41', '2026_07_28_000044_register_customers_module', '31', '2026-08-03 07:23:10'),
('42', '2026_07_28_000045_orders_split_address', '31', '2026-08-03 07:23:10'),
('43', '2026_07_29_000046_rename_phu_tung_to_hang_hoa', '31', '2026-08-03 07:23:10'),
('44', '2026_08_03_000047_chuan_hoa_ten_bang_va_rang_buoc', '31', '2026-08-03 07:23:11');

-- --------------------------------------------------------
-- Bang `modules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modules` (`id`, `name`, `link`, `create_at`, `update_at`) VALUES
('5', 'Quản lý hàng hoá', 'products', NULL, NULL),
('6', 'Quản lý tin tức', 'news', NULL, NULL),
('8', 'Quản lý người dùng', 'users', NULL, NULL),
('9', 'Quản lý nhóm', 'groups', NULL, NULL),
('10', 'Dòng xe (kiểu dáng)', 'car-body-types', '2026-07-18 04:24:33', NULL),
('11', 'Nhiên liệu', 'car-fuels', '2026-07-18 04:24:33', NULL),
('12', 'Màu xe', 'car-colors', '2026-07-18 04:24:33', NULL),
('13', 'Thương hiệu hàng hoá', 'product-brands', '2026-07-18 04:24:33', '2026-08-03 07:23:10'),
('14', 'Xuất xứ', 'product-origins', '2026-07-18 04:24:33', NULL),
('15', 'Hãng sản xuất', 'product-manufacturers', '2026-07-18 04:24:33', NULL),
('16', 'Đơn vị tính', 'product-units', '2026-07-18 04:24:33', NULL),
('17', 'Hãng xe', 'car-brands', '2026-07-18 04:24:33', NULL),
('18', 'Model xe', 'car-models', '2026-07-18 04:24:33', NULL),
('19', 'Đời xe', 'car-years', '2026-07-18 04:24:33', NULL),
('20', 'Danh mục hàng hoá', 'part-categories', '2026-07-18 04:24:33', '2026-08-03 07:23:10'),
('21', 'Thông số kỹ thuật', 'attributes', '2026-07-18 05:27:04', NULL),
('22', 'Danh mục tài khoản', 'accounts', '2026-07-18 05:49:08', NULL),
('23', 'Mã phí', 'cost-items', '2026-07-18 05:49:08', NULL),
('24', 'Mã vụ việc', 'projects', '2026-07-18 05:49:08', NULL),
('25', 'Phiếu thu / chi', 'vouchers', '2026-07-18 05:49:08', NULL),
('26', 'Sổ quỹ', 'cash-book', '2026-07-18 05:49:08', NULL),
('27', 'Phiếu kế toán', 'journal', '2026-07-18 06:10:16', NULL),
('28', 'Đối tượng (khách/NCC)', 'partners', '2026-07-18 06:21:12', NULL),
('29', 'Công nợ', 'debt', '2026-07-18 06:21:12', NULL),
('30', 'Nhật ký chung', 'nhat-ky-chung', '2026-07-18 06:25:37', NULL),
('31', 'Sổ cái / chi tiết TK', 'so-cai', '2026-07-18 06:25:37', NULL),
('32', 'Danh mục kho', 'warehouses', '2026-07-18 10:20:14', NULL),
('33', 'Phiếu nhập kho', 'goods-receipts', '2026-07-18 10:20:14', NULL),
('34', 'Phiếu xuất kho', 'goods-issues', '2026-07-18 10:20:14', NULL),
('35', 'Tồn kho', 'ton-kho', '2026-07-18 10:20:14', NULL),
('36', 'Thẻ kho', 'the-kho', '2026-07-18 10:20:14', NULL),
('37', 'Báo giá', 'quotations', '2026-07-18 11:18:24', NULL),
('38', 'Hoá đơn bán', 'sales-invoices', '2026-07-18 11:18:24', NULL),
('39', 'Báo cáo bán hàng', 'bao-cao-ban-hang', '2026-07-18 11:18:24', NULL),
('40', 'Phiếu bảo hành', 'warranty', '2026-07-18 14:39:18', NULL),
('41', 'Lịch bảo hành', 'lich-bao-hanh', '2026-07-18 14:39:18', NULL),
('42', 'Nhóm khách hàng', 'customer-groups', '2026-07-18 14:39:18', NULL),
('43', 'Kiểm duyệt đánh giá', 'reviews', '2026-07-18 14:39:18', NULL),
('44', 'Báo cáo CSKH', 'bao-cao-cskh', '2026-07-18 14:39:18', NULL),
('45', 'Điều chuyển kho', 'transfers', '2026-07-18 16:38:01', NULL),
('46', 'Kiểm kê kho', 'stock-takes', '2026-07-18 16:38:01', NULL),
('47', 'Danh mục tin', 'news-categories', '2026-07-18 18:29:25', NULL),
('48', 'Dự án', 'du-an', '2026-07-18 18:29:25', NULL),
('49', 'Phòng ban', 'departments', '2026-07-19 04:00:53', NULL),
('50', 'Chức vụ', 'positions', '2026-07-19 04:00:53', NULL),
('51', 'Nhân viên', 'employees', '2026-07-19 04:00:53', NULL),
('52', 'Đơn nghỉ phép', 'leave-requests', '2026-07-19 04:00:53', NULL),
('53', 'Cấu hình website', 'settings', '2026-07-19 04:22:20', NULL),
('54', 'Đơn hàng', 'orders', '2026-07-19 04:31:45', NULL),
('55', 'Thư viện ảnh/video', 'galleries', '2026-07-19 04:57:08', NULL),
('56', 'Menu website', 'menus', '2026-07-19 05:15:15', NULL),
('57', 'Thống kê truy cập', 'thong-ke', '2026-07-19 05:21:58', NULL),
('58', 'Hỗ trợ / Chat', 'chat', '2026-07-19 05:32:53', NULL),
('59', 'Vị trí trong kho', 'warehouse-locations', '2026-07-19 06:00:03', NULL),
('60', 'Hàng tồn lâu', 'ton-kho-lau', '2026-07-19 06:00:03', NULL),
('61', 'Biến động tồn', 'bien-dong-ton', '2026-07-19 06:35:38', NULL),
('62', 'Đăng ký bản tin', 'newsletter', '2026-07-19 06:42:22', NULL),
('63', 'Hộp thư liên hệ', 'contact-messages', '2026-07-19 06:42:22', NULL),
('64', 'Nhắc bảo trì', 'nhac-bao-tri', '2026-07-19 08:00:35', NULL),
('65', 'Banner', 'banners', '2026-07-27 16:29:50', NULL),
('66', 'Khách hàng', 'customers', '2026-08-03 07:23:10', NULL);

-- --------------------------------------------------------
-- Bang `news`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(280) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` datetime DEFAULT NULL,
  `view_count` int NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_slug` (`slug`),
  KEY `idx_news_cat` (`category_id`),
  KEY `idx_news_pub` (`is_published`,`published_at`),
  CONSTRAINT `fk_news_cat` FOREIGN KEY (`category_id`) REFERENCES `news_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `news` (`id`, `category_id`, `title`, `slug`, `meta_title`, `meta_description`, `summary`, `content`, `thumbnail`, `is_published`, `published_at`, `view_count`, `created_by`, `create_at`, `update_at`) VALUES
('3', '1', 'Tân Phát khai trương kho chi nhánh Miền Nam', 'tan-phat-khai-truong-kho-chi-nhanh-mien-nam', 'Tân Phát khai trương kho chi nhánh Miền Nam', 'Mở rộng mạng lưới phân phối phụ tùng chính hãng tại phía Nam.', 'Mở rộng mạng lưới phân phối phụ tùng chính hãng tại phía Nam.', '<p>Nhằm phục vụ khách hàng khu vực phía Nam tốt hơn, Tân Phát chính thức khai trương kho chi nhánh tại KCN Sóng Thần, Bình Dương.</p>', NULL, '1', '2026-07-17 11:11:57', '223', '1', '2026-07-19 11:11:56', NULL),
('4', '1', 'Cách nhận biết má phanh cần thay thế', 'cach-nhan-biet-ma-phanh-can-thay-the', 'Cách nhận biết má phanh cần thay thế', '5 dấu hiệu cho thấy đã đến lúc thay má phanh ô tô.', '5 dấu hiệu cho thấy đã đến lúc thay má phanh ô tô.', '<p>Tiếng kêu ken két, phanh ăn kém, đèn báo phanh sáng... là những dấu hiệu cần kiểm tra ngay hệ thống phanh.</p>', NULL, '1', '2026-07-12 11:11:57', '398', '1', '2026-07-19 11:11:56', NULL),
('5', '1', 'Chương trình khuyến mãi lọc dầu tháng 7', 'chuong-trinh-khuyen-mai-loc-dau-thang-7', 'Chương trình khuyến mãi lọc dầu tháng 7', 'Giảm đến 20% cho các loại lọc dầu chính hãng.', 'Giảm đến 20% cho các loại lọc dầu chính hãng.', '<p>Từ 01/07 đến 31/07, mua lọc dầu Denso/Bosch được giảm giá và tặng công thay dầu.</p>', NULL, '1', '2026-07-07 11:11:57', '82', '1', '2026-07-19 11:11:56', NULL),
('6', '1', 'Hướng dẫn bảo dưỡng xe mùa mưa', 'huong-dan-bao-duong-xe-mua-mua', 'Hướng dẫn bảo dưỡng xe mùa mưa', 'Những lưu ý quan trọng để xe vận hành an toàn mùa mưa.', 'Những lưu ý quan trọng để xe vận hành an toàn mùa mưa.', '<p>Kiểm tra gạt mưa, lốp, phanh và hệ thống điện trước mỗi chuyến đi trong mùa mưa.</p>', NULL, '1', '2026-07-02 11:11:57', '397', '1', '2026-07-19 11:11:56', NULL),
('7', '1', 'Phân biệt bugi Iridium và bugi thường', 'phan-biet-bugi-iridium-va-bugi-thuong', 'Phân biệt bugi Iridium và bugi thường', 'Ưu điểm của bugi Iridium so với bugi truyền thống.', 'Ưu điểm của bugi Iridium so với bugi truyền thống.', '<p>Bugi Iridium bền hơn, đánh lửa mạnh và tiết kiệm nhiên liệu hơn bugi thường.</p>', NULL, '1', '2026-06-27 11:11:57', '179', '1', '2026-07-19 11:11:56', NULL),
('8', '1', 'Tân Phát đạt chứng nhận đại lý chính hãng Bosch', 'tan-phat-dat-chung-nhan-dai-ly-chinh-hang-bosch', 'Tân Phát đạt chứng nhận đại lý chính hãng Bosch', 'Cột mốc quan trọng khẳng định uy tín.', 'Cột mốc quan trọng khẳng định uy tín.', '&#38;#60;p&#38;#62;Tân Phát vinh dự trở thành đại lý uỷ quyền chính hãng của Bosch tại Việt Nam.&#38;#60;/p&#38;#62;', NULL, '1', '2026-06-22 11:11:57', '396', '1', '2026-07-19 11:11:56', '2026-07-27 12:57:38'),
('9', '3', 'Khuyến mãi Black Friday', 'khuyen-mai-black-friday', NULL, NULL, 'Nhanh tay đặt hàng', 'Vào 30/11/2026, toàn bộ các mặt hàng sẽ khuyến mãi từ 10% đến 50%.', NULL, '0', NULL, '0', '15', '2026-07-27 13:00:17', '2026-07-27 13:00:50');

-- --------------------------------------------------------
-- Bang `news_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `news_categories`;
CREATE TABLE `news_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_news_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `news_categories` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Tin công ty', 'tin-cong-ty', '0', '1', '2026-07-18 18:29:25', NULL),
('2', 'Kiến thức kỹ thuật', 'kien-thuc-ky-thuat', '1', '1', '2026-07-18 18:29:25', NULL),
('3', 'Khuyến mãi', 'khuyen-mai', '2', '1', '2026-07-18 18:29:25', NULL);

-- --------------------------------------------------------
-- Bang `newsletter_subscribers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `newsletter_subscribers`;
CREATE TABLE `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `newsletter_subscribers` (`id`, `email`, `status`, `source`, `create_at`) VALUES
('2', 'nguyenvana@gmail.com', '1', 'storefront', '2026-07-19 11:11:56'),
('3', 'tranthib@gmail.com', '1', 'storefront', '2026-07-19 11:11:56'),
('4', 'lehoangc@yahoo.com', '1', 'storefront', '2026-07-19 11:11:56'),
('5', 'phamd@gmail.com', '1', 'storefront', '2026-07-19 11:11:56'),
('6', 'garagexyz@gmail.com', '1', 'storefront', '2026-07-19 11:11:56'),
('7', 'khachle@gmail.com', '1', 'storefront', '2026-07-19 11:11:56'),
('8', 'hello123@gmail.com', '1', 'storefront', '2026-07-27 11:53:11');

-- --------------------------------------------------------
-- Bang `order_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `part_id` int DEFAULT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `fk_oi_part` (`part_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `part_id`, `part_name`, `part_code`, `quantity`, `unit_price`, `amount`) VALUES
('5', '5', '367', 'Lọc dầu động cơ Toyota', 'PT-0004', '3.000', '99000.00', '297000.00'),
('6', '5', '369', 'Bugi Iridium NGK', 'PT-0006', '2.000', '189000.00', '378000.00'),
('7', '6', '368', 'Lọc gió động cơ Vios', 'PT-0005', '1.000', '159000.00', '159000.00'),
('8', '7', '376', 'Lọc dầu Honda CR-V', 'PT-0013', '2.000', '115000.00', '230000.00'),
('9', '8', '378', 'Bugi NGK Laser Kia', 'PT-0015', '3.000', '209000.00', '627000.00'),
('10', '9', '371', 'Ắc quy GS 45Ah', 'PT-0008', '1.000', '1380000.00', '1380000.00');

-- --------------------------------------------------------
-- Bang `orders`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_code` int DEFAULT NULL,
  `province_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_code` int DEFAULT NULL,
  `ward_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `sales_invoice_id` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_no` (`order_no`),
  KEY `idx_order_member` (`member_id`),
  KEY `idx_order_status` (`status`),
  KEY `fk_order_invoice` (`sales_invoice_id`),
  KEY `idx_orders_province` (`province_code`),
  CONSTRAINT `fk_order_invoice` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_order_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `order_no`, `member_id`, `customer_name`, `phone`, `email`, `address`, `province_code`, `province_name`, `ward_code`, `ward_name`, `note`, `payment_method`, `subtotal`, `total_amount`, `status`, `sales_invoice_id`, `create_at`, `update_at`) VALUES
('5', 'DH-000001', NULL, 'Trần Văn Nam', '0977111222', NULL, '15 Cầu Giấy, Hà Nội', NULL, NULL, NULL, NULL, NULL, 'cod', '675000.00', '675000.00', 'new', NULL, '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('6', 'DH-000002', NULL, 'Lê Thị Hoa', '0966333444', NULL, '78 Hai Bà Trưng, Hà Nội', NULL, NULL, NULL, NULL, NULL, 'bank_transfer', '159000.00', '159000.00', 'confirmed', NULL, '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('7', 'DH-000003', NULL, 'Phạm Quốc Việt', '0955666777', NULL, '230 Láng Hạ, Hà Nội', NULL, NULL, NULL, NULL, NULL, 'cod', '230000.00', '230000.00', 'completed', NULL, '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('8', 'DH-000004', NULL, 'sfgsad', 'sdgsdg', 'dsà', 'agdsdfa', NULL, NULL, NULL, NULL, NULL, 'bank_transfer', '627000.00', '627000.00', 'new', NULL, '2026-07-27 12:21:24', '2026-07-27 12:21:24'),
('9', 'DH-000005', NULL, 'sdfgsa', 'sdf', NULL, 's', NULL, NULL, NULL, NULL, NULL, 'cod', '1380000.00', '1380000.00', 'new', NULL, '2026-07-27 12:22:19', '2026-07-27 12:22:19');

-- --------------------------------------------------------
-- Bang `part_attribute_values`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_attribute_values`;
CREATE TABLE `part_attribute_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pav_part_attr` (`part_id`,`attribute_id`),
  KEY `idx_pav_attr` (`attribute_id`),
  KEY `idx_pav_value` (`value`),
  CONSTRAINT `fk_pav_attr` FOREIGN KEY (`attribute_id`) REFERENCES `part_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pav_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `part_attributes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_attributes`;
CREATE TABLE `part_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_attributes_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_attributes` (`id`, `name`, `slug`, `unit`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Chất liệu', 'chat-lieu', NULL, '0', '1', '2026-07-18 05:27:04', NULL),
('2', 'Trọng lượng', 'trong-luong', 'kg', '1', '1', '2026-07-18 05:27:04', NULL),
('3', 'Kích thước', 'kich-thuoc', 'mm', '2', '1', '2026-07-18 05:27:04', NULL),
('4', 'Điện áp', 'dien-ap', 'V', '3', '1', '2026-07-18 05:27:04', NULL);

-- --------------------------------------------------------
-- Bang `part_brands`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_brands`;
CREATE TABLE `part_brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_brands_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_brands` (`id`, `name`, `slug`, `logo`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Bosch', 'bosch', NULL, '0', '1', '2026-07-18 04:24:33', NULL),
('2', 'Denso', 'denso', NULL, '1', '1', '2026-07-18 04:24:33', NULL),
('3', 'Aisin', 'aisin', NULL, '2', '1', '2026-07-18 04:24:33', NULL),
('4', 'NGK', 'ngk', NULL, '3', '1', '2026-07-18 04:24:33', NULL),
('5', 'Mann Filter', 'mann-filter', NULL, '4', '1', '2026-07-18 04:24:33', NULL),
('6', 'Toyota Genuine', 'toyota-genuine', NULL, '5', '1', '2026-07-18 04:24:33', NULL);

-- --------------------------------------------------------
-- Bang `part_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_categories`;
CREATE TABLE `part_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_categories_slug` (`slug`),
  KEY `idx_part_categories_parent` (`parent_id`),
  CONSTRAINT `fk_part_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `part_categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_categories` (`id`, `parent_id`, `name`, `slug`, `image`, `description`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', NULL, 'Hệ thống phanh', 'he-thong-phanh', 'public/assets/uploads/categories/he-thong-phanh-00f23c22.png', NULL, '0', '1', '2026-07-18 04:24:33', '2026-07-28 10:07:44'),
('2', '1', 'Má phanh', 'ma-phanh', NULL, NULL, '1', '1', '2026-07-18 04:24:33', '2026-07-27 11:50:15'),
('3', '1', 'Đĩa phanh', 'dia-phanh', NULL, NULL, '0', '1', '2026-07-18 04:24:33', '2026-07-27 11:50:28'),
('4', '1', 'Dầu phanh', 'dau-phanh', NULL, NULL, '2', '1', '2026-07-18 04:24:33', NULL),
('5', NULL, 'Động cơ', 'dong-co', NULL, NULL, '1', '1', '2026-07-18 04:24:33', NULL),
('6', '5', 'Lọc dầu', 'loc-dau', NULL, NULL, '0', '1', '2026-07-18 04:24:33', NULL),
('7', '5', 'Lọc gió', 'loc-gio', NULL, NULL, '1', '1', '2026-07-18 04:24:33', NULL),
('8', '5', 'Bugi', 'bugi', NULL, NULL, '2', '1', '2026-07-18 04:24:33', NULL),
('9', '5', 'Dây curoa', 'day-curoa', NULL, NULL, '3', '1', '2026-07-18 04:24:33', NULL),
('10', NULL, 'Hệ thống điện', 'he-thong-dien', NULL, NULL, '2', '1', '2026-07-18 04:24:33', NULL),
('11', '10', 'Ắc quy', 'ac-quy', NULL, NULL, '0', '1', '2026-07-18 04:24:33', NULL),
('12', '10', 'Đèn', 'den-xe', NULL, NULL, '1', '1', '2026-07-18 04:24:33', NULL),
('13', '10', 'Máy phát', 'may-phat', NULL, NULL, '2', '1', '2026-07-18 04:24:33', NULL),
('14', NULL, 'Hệ thống treo', 'he-thong-treo', NULL, NULL, '3', '1', '2026-07-18 04:24:33', NULL),
('15', '14', 'Giảm xóc', 'giam-xoc', NULL, NULL, '0', '1', '2026-07-18 04:24:33', NULL),
('17', '14', 'Lò xo', 'lo-xo', NULL, NULL, '1', '1', '2026-07-27 11:51:12', NULL);

-- --------------------------------------------------------
-- Bang `part_fitments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_fitments`;
CREATE TABLE `part_fitments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_id` int NOT NULL,
  `car_year_id` int NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fitment` (`part_id`,`car_year_id`),
  KEY `idx_fitment_car_year` (`car_year_id`),
  CONSTRAINT `fk_fitment_car_year` FOREIGN KEY (`car_year_id`) REFERENCES `car_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fitment_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_fitments` (`id`, `part_id`, `car_year_id`, `note`, `create_at`) VALUES
('598', '395', '327', NULL, '2026-07-28 10:55:45');

-- --------------------------------------------------------
-- Bang `part_images`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_images`;
CREATE TABLE `part_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_id` int NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_part_images_part` (`part_id`),
  CONSTRAINT `fk_part_images_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_images` (`id`, `part_id`, `image`, `sort_order`, `is_primary`, `create_at`) VALUES
('3', '371', 'ac-quy-gs-45ah-pt-0008-02ef907b1b.gif', '1', '1', '2026-07-19 11:37:00'),
('4', '364', 'pt-0001-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('5', '364', 'pt-0001-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('6', '364', 'pt-0001-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('7', '365', 'pt-0002-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('8', '365', 'pt-0002-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('9', '365', 'pt-0002-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('10', '366', 'pt-0003-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('11', '366', 'pt-0003-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('12', '366', 'pt-0003-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('13', '367', 'pt-0004-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('14', '367', 'pt-0004-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('15', '367', 'pt-0004-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('16', '368', 'pt-0005-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('17', '368', 'pt-0005-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('18', '368', 'pt-0005-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('19', '369', 'pt-0006-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('20', '369', 'pt-0006-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('21', '369', 'pt-0006-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('22', '370', 'pt-0007-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('23', '370', 'pt-0007-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('24', '370', 'pt-0007-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('25', '372', 'pt-0009-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('26', '372', 'pt-0009-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('27', '372', 'pt-0009-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('28', '373', 'pt-0010-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('29', '373', 'pt-0010-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('30', '373', 'pt-0010-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('31', '374', 'pt-0011-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('32', '374', 'pt-0011-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('33', '374', 'pt-0011-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('34', '375', 'pt-0012-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('35', '375', 'pt-0012-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('36', '375', 'pt-0012-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('37', '376', 'pt-0013-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('38', '376', 'pt-0013-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('39', '376', 'pt-0013-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('40', '377', 'pt-0014-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('41', '377', 'pt-0014-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('42', '377', 'pt-0014-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('43', '378', 'pt-0015-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('44', '378', 'pt-0015-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('45', '378', 'pt-0015-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('46', '379', 'pt-0016-1-demo.svg', '0', '1', '2026-07-19 11:41:45'),
('47', '379', 'pt-0016-2-demo.svg', '1', '0', '2026-07-19 11:41:45'),
('48', '379', 'pt-0016-3-demo.svg', '2', '0', '2026-07-19 11:41:45'),
('50', '395', 'test-e10f64d6bf.jpg', '1', '1', '2026-07-28 10:55:45'),
('51', '395', 'test-31494bab32.jpg', '2', '0', '2026-07-28 10:55:45'),
('52', '395', 'test-066d6ff97f.jpg', '3', '0', '2026-07-28 10:55:45');

-- --------------------------------------------------------
-- Bang `part_manufacturers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_manufacturers`;
CREATE TABLE `part_manufacturers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_manufacturers_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_manufacturers` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'jhg', 'jhg', '0', '1', '2026-07-18 04:28:02', NULL);

-- --------------------------------------------------------
-- Bang `part_origins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_origins`;
CREATE TABLE `part_origins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_origins_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_origins` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Nhật Bản', 'nhat-ban', '0', '1', '2026-07-18 04:24:33', NULL),
('2', 'Đức', 'duc', '1', '1', '2026-07-18 04:24:33', NULL),
('3', 'Hàn Quốc', 'han-quoc', '2', '1', '2026-07-18 04:24:33', NULL),
('4', 'Thái Lan', 'thai-lan', '3', '1', '2026-07-18 04:24:33', NULL),
('5', 'Trung Quốc', 'trung-quoc', '4', '1', '2026-07-18 04:24:33', NULL),
('6', 'Việt Nam', 'viet-nam', '5', '1', '2026-07-18 04:24:33', NULL);

-- --------------------------------------------------------
-- Bang `part_related`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_related`;
CREATE TABLE `part_related` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_id` int NOT NULL,
  `related_part_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_related` (`part_id`,`related_part_id`),
  KEY `idx_part_related_part` (`part_id`),
  KEY `idx_part_related_related` (`related_part_id`),
  CONSTRAINT `fk_part_related_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_part_related_related` FOREIGN KEY (`related_part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `part_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_reviews`;
CREATE TABLE `part_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_id` int NOT NULL,
  `member_id` int DEFAULT NULL,
  `author_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT '5',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_review_part` (`part_id`,`status`),
  KEY `fk_review_member` (`member_id`),
  CONSTRAINT `fk_review_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_review_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_reviews` (`id`, `part_id`, `member_id`, `author_name`, `rating`, `comment`, `status`, `create_at`) VALUES
('2', '364', NULL, 'Nguyễn Minh', '5', 'Má phanh chính hãng, ăn phanh êm, lắp vừa khít.', '1', '2026-07-19 11:11:56'),
('3', '367', NULL, 'Trần Hải', '5', 'Lọc dầu Denso xịn, giá tốt.', '1', '2026-07-19 11:11:56'),
('4', '371', NULL, 'Lê Văn Bình', '4', 'Ắc quy khỏe, giao nhanh.', '1', '2026-07-19 11:11:56'),
('5', '369', NULL, 'Phạm Tuấn', '5', 'Bugi NGK Iridium nổ máy nhạy hơn hẳn.', '1', '2026-07-19 11:11:56'),
('6', '372', NULL, 'Đỗ Quang', '4', 'Đèn LED sáng, nhưng giá hơi cao.', '1', '2026-07-19 11:11:56'),
('7', '375', '4', '123', '5', 'sa', '0', '2026-07-27 12:32:11');

-- --------------------------------------------------------
-- Bang `part_units`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `part_units`;
CREATE TABLE `part_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_units_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `part_units` (`id`, `name`, `slug`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Cái', 'cai', '0', '1', '2026-07-18 04:24:33', NULL),
('2', 'Bộ', 'bo', '1', '1', '2026-07-18 04:24:33', NULL),
('3', 'Chiếc', 'chiec', '2', '1', '2026-07-18 04:24:33', NULL),
('4', 'Lít', 'lit', '3', '1', '2026-07-18 04:24:33', NULL),
('5', 'Hộp', 'hop', '4', '1', '2026-07-18 04:24:33', NULL),
('6', 'Mét', 'met', '5', '1', '2026-07-18 04:24:33', NULL);

-- --------------------------------------------------------
-- Bang `partners`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partners`;
CREATE TABLE `partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `group_id` int DEFAULT NULL,
  `tax_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_partners_code` (`code`),
  KEY `idx_partners_type` (`type`),
  KEY `fk_partners_group` (`group_id`),
  CONSTRAINT `fk_partners_group` FOREIGN KEY (`group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partners` (`id`, `code`, `name`, `type`, `group_id`, `tax_code`, `phone`, `address`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('4', 'KH-0001', 'Garage Thành Công', 'customer', '3', '0102030405', '0901234567', '12 Trần Phú, Hà Nội', '0', '1', '2026-07-19 11:11:56', NULL),
('5', 'KH-0002', 'Đại lý phụ tùng Phú Sơn', 'customer', '2', '0203040506', '0912345678', '45 Lê Lợi, Bắc Ninh', '0', '1', '2026-07-19 11:11:56', NULL),
('6', 'KH-0003', 'Anh Nguyễn Văn Hùng', 'customer', '1', NULL, '0987654321', '89 Nguyễn Trãi, Hà Nội', '0', '1', '2026-07-19 11:11:56', NULL),
('7', 'KH-0004', 'Gara Ô tô Minh Phát', 'customer', '3', '0304050607', '0934567890', '203 Giải Phóng, Hà Nội', '0', '1', '2026-07-19 11:11:56', NULL),
('8', 'NCC-001', 'Công ty Bosch Việt Nam', 'supplier', NULL, '0300123456', '02838220000', 'Long Thành, Đồng Nai', '0', '1', '2026-07-19 11:11:56', NULL),
('9', 'NCC-002', 'NCC Phụ tùng Miền Bắc', 'supplier', NULL, '0100987654', '02439990000', 'Gia Lâm, Hà Nội', '0', '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `parts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `parts`;
CREATE TABLE `parts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oem_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(280) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `manufacturer_id` int DEFAULT NULL,
  `origin_id` int DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(15,2) DEFAULT NULL,
  `warranty_month` smallint DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parts_code` (`code`),
  UNIQUE KEY `uq_parts_slug` (`slug`),
  KEY `idx_parts_category` (`category_id`),
  KEY `idx_parts_brand` (`brand_id`),
  KEY `idx_parts_status` (`status`),
  KEY `idx_parts_oem` (`oem_code`),
  KEY `fk_parts_manufacturer` (`manufacturer_id`),
  KEY `fk_parts_origin` (`origin_id`),
  KEY `fk_parts_unit` (`unit_id`),
  CONSTRAINT `fk_parts_brand` FOREIGN KEY (`brand_id`) REFERENCES `part_brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_parts_category` FOREIGN KEY (`category_id`) REFERENCES `part_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_parts_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `part_manufacturers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_parts_origin` FOREIGN KEY (`origin_id`) REFERENCES `part_origins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_parts_unit` FOREIGN KEY (`unit_id`) REFERENCES `part_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parts` (`id`, `code`, `oem_code`, `name`, `slug`, `category_id`, `brand_id`, `manufacturer_id`, `origin_id`, `unit_id`, `price`, `sale_price`, `warranty_month`, `description`, `status`, `create_at`, `update_at`) VALUES
('364', 'PT-0001', '04465-0D260', 'Má phanh trước Toyota Vios', 'ma-phanh-truoc-toyota-vios-pt-0001', '2', '1', NULL, '1', '2', '650000.00', '590000.00', '6', 'Phụ tùng chính hãng Bosch, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('365', 'PT-0002', '04466-33471', 'Má phanh sau Toyota Camry', 'ma-phanh-sau-toyota-camry-pt-0002', '2', '3', NULL, '1', '2', '720000.00', '680000.00', '6', 'Phụ tùng chính hãng Aisin, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('366', 'PT-0003', '43512-06130', 'Đĩa phanh trước Vios', 'dia-phanh-truoc-vios-pt-0003', '3', '1', NULL, '1', '3', '980000.00', '920000.00', '12', 'Phụ tùng chính hãng Bosch, bảo hành 12 tháng.', '1', '2026-07-19 11:11:56', NULL),
('367', 'PT-0004', '90915-YZZD4', 'Lọc dầu động cơ Toyota', 'loc-dau-dong-co-toyota-pt-0004', '6', '2', NULL, '1', '3', '120000.00', '99000.00', '3', 'Phụ tùng chính hãng Denso, bảo hành 3 tháng.', '1', '2026-07-19 11:11:56', NULL),
('368', 'PT-0005', '17801-0D060', 'Lọc gió động cơ Vios', 'loc-gio-dong-co-vios-pt-0005', '7', '5', NULL, '2', '3', '180000.00', '159000.00', '3', 'Phụ tùng chính hãng Mann Filter, bảo hành 3 tháng.', '1', '2026-07-19 11:11:56', NULL),
('369', 'PT-0006', 'SK20R11', 'Bugi Iridium NGK', 'bugi-iridium-ngk-pt-0006', '8', '4', NULL, '1', '3', '210000.00', '189000.00', '6', 'Phụ tùng chính hãng NGK, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('370', 'PT-0007', '13568-09210', 'Dây curoa cam Toyota', 'day-curoa-cam-toyota-pt-0007', '9', '6', NULL, '1', '3', '850000.00', '800000.00', '12', 'Phụ tùng chính hãng Toyota Genuine, bảo hành 12 tháng.', '1', '2026-07-19 11:11:56', NULL),
('371', 'PT-0008', '28800-N', 'Ắc quy GS 45Ah', 'ac-quy-gs-45ah-pt-0008', '11', '1', NULL, '6', NULL, '1450000.00', '1380000.00', '18', 'Phụ tùng chính hãng Bosch, bảo hành 18 tháng.', '1', '2026-07-19 11:11:56', '2026-07-27 12:54:24'),
('372', 'PT-0009', '81150-0D', 'Đèn pha Toyota Vios LED', 'den-pha-toyota-vios-led-pt-0009', '12', '6', NULL, '4', '3', '2650000.00', '2500000.00', '12', 'Phụ tùng chính hãng Toyota Genuine, bảo hành 12 tháng.', '1', '2026-07-19 11:11:56', NULL),
('373', 'PT-0010', '27060-0', 'Máy phát điện Honda City', 'may-phat-dien-honda-city-pt-0010', '13', '2', NULL, '1', '3', '3850000.00', '3650000.00', '12', 'Phụ tùng chính hãng Denso, bảo hành 12 tháng.', '1', '2026-07-19 11:11:56', NULL),
('374', 'PT-0011', '48510-', 'Giảm xóc trước Mazda CX-5', 'giam-xoc-truoc-mazda-cx-5-pt-0011', '15', '3', NULL, '1', '3', '1950000.00', '1850000.00', '12', 'Phụ tùng chính hãng Aisin, bảo hành 12 tháng.', '1', '2026-07-19 11:11:56', NULL),
('375', 'PT-0012', '48231-', 'Lò xo giảm xóc sau Ranger', 'lo-xo-giam-xoc-sau-ranger-pt-0012', NULL, '1', NULL, '4', '3', '680000.00', '640000.00', '6', 'Phụ tùng chính hãng Bosch, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('376', 'PT-0013', '04152-YZZA1', 'Lọc dầu Honda CR-V', 'loc-dau-honda-cr-v-pt-0013', '6', '2', NULL, '1', '3', '135000.00', '115000.00', '3', 'Phụ tùng chính hãng Denso, bảo hành 3 tháng.', '1', '2026-07-19 11:11:56', NULL),
('377', 'PT-0014', '17220-5', 'Lọc gió Honda City', 'loc-gio-honda-city-pt-0014', '7', '5', NULL, '2', '3', '195000.00', '175000.00', '3', 'Phụ tùng chính hãng Mann Filter, bảo hành 3 tháng.', '1', '2026-07-19 11:11:56', NULL),
('378', 'PT-0015', 'DF6H-11', 'Bugi NGK Laser Kia', 'bugi-ngk-laser-kia-pt-0015', '8', '4', NULL, '1', '3', '230000.00', '209000.00', '6', 'Phụ tùng chính hãng NGK, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('379', 'PT-0016', '45022-', 'Má phanh trước Ford Ranger', 'ma-phanh-truoc-ford-ranger-pt-0016', '2', '1', NULL, '2', '2', '890000.00', '840000.00', '6', 'Phụ tùng chính hãng Bosch, bảo hành 6 tháng.', '1', '2026-07-19 11:11:56', NULL),
('395', 'Test', NULL, 'Test', 'test', '2', '2', '1', '2', '2', '4000.00', NULL, '4', 'đấ', '1', '2026-07-28 10:55:45', NULL);

-- --------------------------------------------------------
-- Bang `permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_id` int DEFAULT NULL,
  `group_id` int DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_permissions_group` (`group_id`),
  KEY `idx_permissions_module` (`module_id`),
  CONSTRAINT `fk_permissions_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_permissions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `module_id`, `group_id`, `role`) VALUES
('265', '9', '10', 'view'),
('266', '9', '10', 'add'),
('267', '9', '10', 'edit'),
('268', '5', '9', 'view'),
('269', '6', '9', 'view'),
('270', '8', '9', 'view'),
('271', '8', '9', 'edit'),
('272', '8', '9', 'delete'),
('273', '9', '9', 'view'),
('274', '9', '9', 'edit'),
('275', '9', '9', 'delete'),
('276', '9', '9', 'permission'),
('277', '10', '9', 'view'),
('278', '10', '9', 'add'),
('279', '10', '9', 'edit'),
('280', '10', '9', 'delete'),
('281', '11', '9', 'view'),
('282', '11', '9', 'add'),
('283', '11', '9', 'edit'),
('284', '11', '9', 'delete'),
('285', '12', '9', 'view'),
('286', '12', '9', 'add'),
('287', '12', '9', 'edit'),
('288', '12', '9', 'delete'),
('289', '13', '9', 'view'),
('290', '13', '9', 'add'),
('291', '13', '9', 'edit'),
('292', '13', '9', 'delete'),
('293', '14', '9', 'view'),
('294', '14', '9', 'add'),
('295', '14', '9', 'edit'),
('296', '14', '9', 'delete'),
('297', '15', '9', 'view'),
('298', '15', '9', 'add'),
('299', '15', '9', 'edit'),
('300', '15', '9', 'delete'),
('301', '16', '9', 'view'),
('302', '16', '9', 'add'),
('303', '16', '9', 'edit'),
('304', '16', '9', 'delete'),
('305', '17', '9', 'view'),
('306', '17', '9', 'add'),
('307', '17', '9', 'edit'),
('308', '17', '9', 'delete'),
('309', '18', '9', 'view'),
('310', '18', '9', 'add'),
('311', '18', '9', 'edit'),
('312', '18', '9', 'delete'),
('313', '19', '9', 'view'),
('314', '19', '9', 'add'),
('315', '19', '9', 'edit'),
('316', '19', '9', 'delete'),
('317', '20', '9', 'view'),
('318', '20', '9', 'add'),
('319', '20', '9', 'edit'),
('320', '20', '9', 'delete'),
('321', '5', '9', 'add'),
('322', '5', '9', 'edit'),
('323', '5', '9', 'delete'),
('324', '21', '9', 'view'),
('325', '21', '9', 'add'),
('326', '21', '9', 'edit'),
('327', '21', '9', 'delete'),
('328', '22', '9', 'view'),
('329', '22', '9', 'add'),
('330', '22', '9', 'edit'),
('331', '22', '9', 'delete'),
('332', '23', '9', 'view'),
('333', '23', '9', 'add'),
('334', '23', '9', 'edit'),
('335', '23', '9', 'delete'),
('336', '24', '9', 'view'),
('337', '24', '9', 'add'),
('338', '24', '9', 'edit'),
('339', '24', '9', 'delete'),
('340', '25', '9', 'view'),
('341', '25', '9', 'add'),
('342', '25', '9', 'edit'),
('343', '25', '9', 'delete'),
('344', '26', '9', 'view'),
('345', '27', '9', 'view'),
('346', '27', '9', 'add'),
('347', '27', '9', 'edit'),
('348', '27', '9', 'delete'),
('349', '28', '9', 'view'),
('350', '28', '9', 'add'),
('351', '28', '9', 'edit'),
('352', '28', '9', 'delete'),
('353', '29', '9', 'view'),
('354', '30', '9', 'view'),
('355', '31', '9', 'view'),
('356', '32', '9', 'view'),
('357', '32', '9', 'add'),
('358', '32', '9', 'edit'),
('359', '32', '9', 'delete'),
('360', '33', '9', 'view'),
('361', '33', '9', 'add'),
('362', '33', '9', 'edit'),
('363', '33', '9', 'delete'),
('364', '34', '9', 'view'),
('365', '34', '9', 'add'),
('366', '34', '9', 'edit'),
('367', '34', '9', 'delete'),
('368', '35', '9', 'view'),
('369', '36', '9', 'view'),
('370', '37', '9', 'view'),
('371', '37', '9', 'add'),
('372', '37', '9', 'edit'),
('373', '37', '9', 'delete'),
('374', '38', '9', 'view'),
('375', '38', '9', 'add'),
('376', '38', '9', 'edit'),
('377', '38', '9', 'delete'),
('378', '39', '9', 'view'),
('379', '40', '9', 'view'),
('380', '40', '9', 'add'),
('381', '40', '9', 'edit'),
('382', '40', '9', 'delete'),
('383', '41', '9', 'view'),
('384', '42', '9', 'view'),
('385', '42', '9', 'add'),
('386', '42', '9', 'edit'),
('387', '42', '9', 'delete'),
('388', '43', '9', 'view'),
('389', '43', '9', 'add'),
('390', '43', '9', 'edit'),
('391', '43', '9', 'delete'),
('392', '44', '9', 'view'),
('393', '45', '9', 'view'),
('394', '45', '9', 'add'),
('395', '45', '9', 'edit'),
('396', '45', '9', 'delete'),
('397', '46', '9', 'view'),
('398', '46', '9', 'add'),
('399', '46', '9', 'edit'),
('400', '46', '9', 'delete'),
('401', '6', '9', 'add'),
('402', '6', '9', 'edit'),
('403', '6', '9', 'delete'),
('404', '47', '9', 'view'),
('405', '47', '9', 'add'),
('406', '47', '9', 'edit'),
('407', '47', '9', 'delete'),
('408', '48', '9', 'view'),
('409', '48', '9', 'add'),
('410', '48', '9', 'edit'),
('411', '48', '9', 'delete'),
('412', '49', '9', 'view'),
('413', '49', '9', 'add'),
('414', '49', '9', 'edit'),
('415', '49', '9', 'delete'),
('416', '50', '9', 'view'),
('417', '50', '9', 'add'),
('418', '50', '9', 'edit'),
('419', '50', '9', 'delete'),
('420', '51', '9', 'view'),
('421', '51', '9', 'add'),
('422', '51', '9', 'edit'),
('423', '51', '9', 'delete'),
('424', '52', '9', 'view'),
('425', '52', '9', 'add'),
('426', '52', '9', 'edit'),
('427', '52', '9', 'delete'),
('428', '53', '9', 'view'),
('429', '53', '9', 'edit'),
('430', '54', '9', 'view'),
('431', '54', '9', 'add'),
('432', '54', '9', 'edit'),
('433', '54', '9', 'delete'),
('434', '55', '9', 'view'),
('435', '55', '9', 'add'),
('436', '55', '9', 'edit'),
('437', '55', '9', 'delete'),
('438', '56', '9', 'view'),
('439', '56', '9', 'add'),
('440', '56', '9', 'edit'),
('441', '56', '9', 'delete'),
('442', '57', '9', 'view'),
('443', '58', '9', 'view'),
('444', '58', '9', 'edit'),
('445', '58', '9', 'delete'),
('446', '59', '9', 'view'),
('447', '59', '9', 'add'),
('448', '59', '9', 'edit'),
('449', '59', '9', 'delete'),
('450', '60', '9', 'view'),
('451', '61', '9', 'view'),
('452', '62', '9', 'view'),
('453', '62', '9', 'add'),
('454', '62', '9', 'edit'),
('455', '62', '9', 'delete'),
('456', '63', '9', 'view'),
('457', '63', '9', 'add'),
('458', '63', '9', 'edit'),
('459', '63', '9', 'delete'),
('460', '64', '9', 'view'),
('461', '64', '9', 'edit'),
('462', '65', '9', 'view'),
('463', '65', '9', 'add'),
('464', '65', '9', 'edit');
INSERT INTO `permissions` (`id`, `module_id`, `group_id`, `role`) VALUES
('465', '65', '9', 'delete'),
('469', '8', '9', 'add'),
('470', '9', '9', 'add'),
('471', '66', '9', 'view'),
('472', '66', '9', 'edit');

-- --------------------------------------------------------
-- Bang `positions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `positions` (`id`, `name`, `description`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'Nhân viên', NULL, '0', '1', '2026-07-19 04:00:53', NULL),
('2', 'Trưởng phòng', NULL, '1', '1', '2026-07-19 04:00:53', NULL),
('3', 'Phó phòng', NULL, '2', '1', '2026-07-19 04:00:53', NULL),
('4', 'Giám đốc', NULL, '3', '1', '2026-07-19 04:00:53', NULL);

-- --------------------------------------------------------
-- Bang `quotation_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quotation_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quote` (`quotation_id`),
  KEY `idx_qi_part` (`part_id`),
  CONSTRAINT `fk_qi_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quotation_items` (`id`, `quotation_id`, `part_id`, `quantity`, `unit_price`, `discount_percent`, `amount`, `note`) VALUES
('4', '3', '372', '2.000', '2500000.00', '8.00', '4600000.00', NULL),
('5', '3', '374', '4.000', '1850000.00', '8.00', '6808000.00', NULL),
('6', '4', '379', '6.000', '840000.00', '8.00', '4636800.00', NULL),
('7', '4', '365', '4.000', '680000.00', '8.00', '2502400.00', NULL),
('8', '5', '378', '1.000', '209000.00', '0.00', '209000.00', NULL),
('9', '5', '369', '6.000', '189000.00', '0.00', '1134000.00', NULL),
('10', '5', '371', '1.000', '1380000.00', '0.00', '1380000.00', NULL),
('11', '6', '371', '1.000', '1380000.00', '0.00', '1380000.00', NULL),
('12', '7', '369', '1.000', '189000.00', '0.00', '189000.00', NULL);

-- --------------------------------------------------------
-- Bang `quotations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quote_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quote_no` (`quote_no`),
  KEY `idx_quote_customer` (`customer_id`),
  CONSTRAINT `fk_quote_customer` FOREIGN KEY (`customer_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quotations` (`id`, `quote_no`, `customer_id`, `customer_name`, `quote_date`, `valid_until`, `vat_rate`, `subtotal`, `tax_amount`, `total_amount`, `status`, `note`, `created_by`, `create_at`, `update_at`) VALUES
('3', 'BG-000001', '7', 'Gara Ô tô Minh Phát', '2026-07-05', '2026-07-20', '10.00', '11408000.00', '1140800.00', '12548800.00', 'sent', NULL, '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('4', 'BG-000002', '4', 'Garage Thành Công', '2026-07-12', '2026-07-27', '10.00', '7139200.00', '713920.00', '7853120.00', 'accepted', NULL, '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('5', 'BG-000003', NULL, 'dsgaa', '2026-07-27', NULL, '0.00', '2723000.00', '0.00', '2723000.00', 'sent', 'SĐT: sad', NULL, '2026-07-27 12:15:49', '2026-07-27 12:15:49'),
('6', 'BG-000004', NULL, 'dsfga', '2026-07-27', NULL, '0.00', '1380000.00', '0.00', '1380000.00', 'sent', NULL, NULL, '2026-07-27 12:16:29', '2026-07-27 12:16:29'),
('7', 'BG-000005', NULL, 's', '2026-07-27', NULL, '0.00', '189000.00', '0.00', '189000.00', 'sent', 'SĐT: addsd — gadsd', NULL, '2026-07-27 12:17:01', '2026-07-27 12:17:01');

-- --------------------------------------------------------
-- Bang `sales_invoice_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_invoice_items`;
CREATE TABLE `sales_invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cost_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sii_invoice` (`invoice_id`),
  KEY `idx_sii_part` (`part_id`),
  CONSTRAINT `fk_sii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sii_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales_invoice_items` (`id`, `invoice_id`, `part_id`, `quantity`, `unit_price`, `discount_percent`, `amount`, `unit_cost`, `cost_amount`, `note`) VALUES
('6', '6', '364', '4.000', '590000.00', '8.00', '2171200.00', '420000.00', '1680000.00', NULL),
('7', '6', '367', '10.000', '99000.00', '8.00', '910800.00', '70000.00', '700000.00', NULL),
('8', '6', '369', '8.000', '189000.00', '8.00', '1391040.00', '130000.00', '1040000.00', NULL),
('9', '7', '371', '6.000', '1380000.00', '5.00', '7866000.00', '1050000.00', '6300000.00', NULL),
('10', '7', '366', '3.000', '920000.00', '5.00', '2622000.00', '650000.00', '1950000.00', NULL),
('11', '8', '368', '2.000', '159000.00', '0.00', '318000.00', '110000.00', '220000.00', NULL),
('12', '8', '378', '4.000', '209000.00', '0.00', '836000.00', '150000.00', '600000.00', NULL),
('14', '9', '364', '1.000', '590000.00', '0.00', '590000.00', '420000.00', '420000.00', NULL);

-- --------------------------------------------------------
-- Bang `sales_invoices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_invoices`;
CREATE TABLE `sales_invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warehouse_id` int NOT NULL,
  `quotation_id` int DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cost_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `acc_voucher_id` int DEFAULT NULL,
  `einvoice_status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `einvoice_serial` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `einvoice_form` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `einvoice_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `einvoice_issued_at` datetime DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_no` (`invoice_no`),
  KEY `idx_inv_customer` (`customer_id`),
  KEY `idx_inv_status` (`status`),
  KEY `fk_inv_warehouse` (`warehouse_id`),
  KEY `fk_inv_quote` (`quotation_id`),
  KEY `fk_inv_voucher` (`acc_voucher_id`),
  CONSTRAINT `fk_inv_customer` FOREIGN KEY (`customer_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_quote` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_voucher` FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales_invoices` (`id`, `invoice_no`, `customer_id`, `customer_name`, `warehouse_id`, `quotation_id`, `invoice_date`, `vat_rate`, `subtotal`, `tax_amount`, `total_amount`, `cost_amount`, `status`, `acc_voucher_id`, `einvoice_status`, `einvoice_serial`, `einvoice_form`, `einvoice_no`, `einvoice_issued_at`, `note`, `created_by`, `create_at`, `update_at`) VALUES
('6', 'HD-000001', '4', 'Garage Thành Công', '1', NULL, '2026-06-10', '10.00', '4473040.00', '447304.00', '4920344.00', '3420000.00', '1', '24', 'issued', 'K26TTP', '1', '00000001', '2026-07-19 11:11:56', NULL, '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('7', 'HD-000002', '5', 'Đại lý phụ tùng Phú Sơn', '1', NULL, '2026-06-25', '10.00', '10488000.00', '1048800.00', '11536800.00', '8250000.00', '1', '25', 'issued', 'K26TTP', '1', '00000002', '2026-07-19 11:11:56', NULL, '1', '2026-07-19 11:11:57', '2026-07-19 11:11:57'),
('8', 'HD-000003', '6', 'Anh Nguyễn Văn Hùng', '1', NULL, '2026-07-08', '10.00', '1154000.00', '115400.00', '1269400.00', '820000.00', '1', '26', 'issued', 'K26TTP', '1', '00000003', '2026-07-28 11:56:28', NULL, '1', '2026-07-19 11:11:57', '2026-07-28 11:56:28'),
('9', 'HD-000004', '8', NULL, '1', NULL, '2026-07-28', '10.00', '590000.00', '59000.00', '649000.00', '420000.00', '1', NULL, 'issued', 'K26TTP', '1', '00000004', '2026-07-28 12:06:51', NULL, '15', '2026-07-28 11:57:05', '2026-07-28 12:06:51');

-- --------------------------------------------------------
-- Bang `site_projects`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `site_projects`;
CREATE TABLE `site_projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(280) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_projects_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_projects` (`id`, `name`, `slug`, `meta_title`, `meta_description`, `client`, `location`, `summary`, `content`, `thumbnail`, `completed_at`, `is_published`, `sort_order`, `created_by`, `create_at`, `update_at`) VALUES
('2', 'Cung cấp phụ tùng cho chuỗi Gara ABC', 'cung-cap-phu-tung-cho-chuoi-gara-abc', 'Cung cấp phụ tùng cho chuỗi Gara ABC', 'Hợp đồng cung cấp phụ tùng dài hạn cho 12 gara.', 'Chuỗi Gara ABC', 'Hà Nội', 'Hợp đồng cung cấp phụ tùng dài hạn cho 12 gara.', '<p>Hợp đồng cung cấp phụ tùng dài hạn cho 12 gara.</p>', NULL, '2026-07-04', '1', '0', '1', '2026-07-19 11:11:56', NULL),
('3', 'Trang bị thiết bị cho xưởng dịch vụ Toyota', 'trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', 'Trang bị thiết bị cho xưởng dịch vụ Toyota', 'Cung cấp và lắp đặt thiết bị nâng hạ, máy chẩn đoán.', 'Toyota Long Biên', 'Hà Nội', 'Cung cấp và lắp đặt thiết bị nâng hạ, máy chẩn đoán.', '<p>Cung cấp và lắp đặt thiết bị nâng hạ, máy chẩn đoán.</p>', NULL, '2026-06-04', '1', '1', '1', '2026-07-19 11:11:56', NULL),
('4', 'Dự án phụ tùng đội xe doanh nghiệp', 'du-an-phu-tung-doi-xe-doanh-nghiep', 'Dự án phụ tùng đội xe doanh nghiệp', 'Bảo dưỡng định kỳ đội xe 50 chiếc.', 'Công ty Vận tải Minh Anh', 'Bắc Ninh', 'Bảo dưỡng định kỳ đội xe 50 chiếc.', '<p>Bảo dưỡng định kỳ đội xe 50 chiếc.</p>', NULL, '2026-05-05', '1', '2', '1', '2026-07-19 11:11:56', NULL),
('5', 'Cung ứng ắc quy cho đại lý miền Bắc', 'cung-ung-ac-quy-cho-dai-ly-mien-bac', 'Cung ứng ắc quy cho đại lý miền Bắc', 'Phân phối ắc quy GS chính hãng khu vực miền Bắc.', 'Đại lý Phú Sơn', 'Bắc Ninh', 'Phân phối ắc quy GS chính hãng khu vực miền Bắc.', '<p>Phân phối ắc quy GS chính hãng khu vực miền Bắc.</p>', NULL, '2026-04-05', '1', '3', '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `site_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `skey` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `svalue` text COLLATE utf8mb4_unicode_ci,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`id`, `skey`, `svalue`, `update_at`) VALUES
('1', 'site_name', 'Công ty TNHH Phụ tùng Ô tô Tân Phát', '2026-07-19 12:50:37'),
('2', 'site_slogan', 'Phụ tùng & thiết bị gara ô tô chính hãng', '2026-07-19 12:50:37'),
('3', 'meta_description', 'Tân Phát - nhà cung cấp phụ tùng và thiết bị gara ô tô chính hãng. Tư vấn tương thích theo hãng, model, đời xe.', '2026-07-19 12:50:37'),
('4', 'meta_keywords', 'phụ tùng ô tô, thiết bị gara, má phanh, lọc dầu, ắc quy, Tân Phát', '2026-07-19 12:50:37'),
('5', 'og_image', '', '2026-07-19 04:22:20'),
('6', 'hotline', '1900 6363', '2026-07-19 12:50:37'),
('7', 'email', 'info@tanphat.vn', '2026-07-19 12:50:37'),
('8', 'address', 'Số 88 Nguyễn Văn Cừ, Long Biên, Hà Nội', '2026-07-19 12:50:37'),
('9', 'facebook', 'https://facebook.com/tanphat.auto', '2026-07-19 12:50:37'),
('10', 'zalo', '1900 6363', '2026-07-19 12:50:37'),
('11', 'bank_name', 'Vietcombank - CN Hà Nội', '2026-07-19 12:50:37'),
('12', 'bank_account', '0011000123456', '2026-07-19 12:50:37'),
('13', 'bank_holder', 'CONG TY TNHH PHU TUNG O TO TAN PHAT', '2026-07-19 12:50:37'),
('14', 'maintenance_interval_months', '6', '2026-07-19 08:01:36'),
('15', 'maintenance_window_days', '30', '2026-07-19 08:01:36'),
('16', 'einvoice_serial', 'K26TTP', NULL),
('17', 'einvoice_form', '1', NULL),
('21', 'tax_code', '0101234567', '2026-07-19 12:50:37');

-- --------------------------------------------------------
-- Bang `stock_cards`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_cards`;
CREATE TABLE `stock_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `warehouse_id` int NOT NULL,
  `part_id` int NOT NULL,
  `move_date` date NOT NULL,
  `doc_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_id` int NOT NULL,
  `doc_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_in` decimal(15,3) NOT NULL DEFAULT '0.000',
  `qty_out` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `balance_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cards_wh_part` (`warehouse_id`,`part_id`,`id`),
  KEY `idx_cards_doc` (`doc_type`,`doc_id`),
  KEY `idx_cards_part_date` (`part_id`,`move_date`),
  CONSTRAINT `fk_cards_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cards_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_cards` (`id`, `warehouse_id`, `part_id`, `move_date`, `doc_type`, `doc_id`, `doc_no`, `qty_in`, `qty_out`, `unit_cost`, `balance_qty`, `balance_value`, `note`, `create_at`) VALUES
('19', '1', '364', '2026-03-05', 'receipt', '8', 'PNK-000001', '40.000', '0.000', '420000.00', '40.000', '16800000.00', NULL, '2026-07-19 11:11:57'),
('20', '1', '366', '2026-03-05', 'receipt', '8', 'PNK-000001', '25.000', '0.000', '650000.00', '25.000', '16250000.00', NULL, '2026-07-19 11:11:57'),
('21', '1', '371', '2026-03-05', 'receipt', '8', 'PNK-000001', '30.000', '0.000', '1050000.00', '30.000', '31500000.00', NULL, '2026-07-19 11:11:57'),
('22', '1', '379', '2026-03-05', 'receipt', '8', 'PNK-000001', '20.000', '0.000', '600000.00', '20.000', '12000000.00', NULL, '2026-07-19 11:11:57'),
('23', '1', '367', '2026-04-12', 'receipt', '9', 'PNK-000002', '120.000', '0.000', '70000.00', '120.000', '8400000.00', NULL, '2026-07-19 11:11:57'),
('24', '1', '368', '2026-04-12', 'receipt', '9', 'PNK-000002', '80.000', '0.000', '110000.00', '80.000', '8800000.00', NULL, '2026-07-19 11:11:57'),
('25', '1', '369', '2026-04-12', 'receipt', '9', 'PNK-000002', '100.000', '0.000', '130000.00', '100.000', '13000000.00', NULL, '2026-07-19 11:11:57'),
('26', '1', '370', '2026-04-12', 'receipt', '9', 'PNK-000002', '15.000', '0.000', '560000.00', '15.000', '8400000.00', NULL, '2026-07-19 11:11:57'),
('27', '1', '376', '2026-04-12', 'receipt', '9', 'PNK-000002', '90.000', '0.000', '80000.00', '90.000', '7200000.00', NULL, '2026-07-19 11:11:57'),
('28', '1', '377', '2026-04-12', 'receipt', '9', 'PNK-000002', '70.000', '0.000', '120000.00', '70.000', '8400000.00', NULL, '2026-07-19 11:11:57'),
('29', '1', '378', '2026-04-12', 'receipt', '9', 'PNK-000002', '60.000', '0.000', '150000.00', '60.000', '9000000.00', NULL, '2026-07-19 11:11:57'),
('30', '1', '365', '2026-05-20', 'receipt', '10', 'PNK-000003', '22.000', '0.000', '480000.00', '22.000', '10560000.00', NULL, '2026-07-19 11:11:57'),
('31', '1', '372', '2026-05-20', 'receipt', '10', 'PNK-000003', '12.000', '0.000', '1900000.00', '12.000', '22800000.00', NULL, '2026-07-19 11:11:57'),
('32', '1', '373', '2026-05-20', 'receipt', '10', 'PNK-000003', '8.000', '0.000', '2900000.00', '8.000', '23200000.00', NULL, '2026-07-19 11:11:57'),
('33', '1', '374', '2026-05-20', 'receipt', '10', 'PNK-000003', '16.000', '0.000', '1450000.00', '16.000', '23200000.00', NULL, '2026-07-19 11:11:57'),
('34', '1', '375', '2026-05-20', 'receipt', '10', 'PNK-000003', '24.000', '0.000', '470000.00', '24.000', '11280000.00', NULL, '2026-07-19 11:11:57'),
('35', '1', '364', '2026-06-10', 'sale_invoice', '6', 'HD-000001', '0.000', '4.000', '420000.00', '36.000', '15120000.00', NULL, '2026-07-19 11:11:57'),
('36', '1', '367', '2026-06-10', 'sale_invoice', '6', 'HD-000001', '0.000', '10.000', '70000.00', '110.000', '7700000.00', NULL, '2026-07-19 11:11:57'),
('37', '1', '369', '2026-06-10', 'sale_invoice', '6', 'HD-000001', '0.000', '8.000', '130000.00', '92.000', '11960000.00', NULL, '2026-07-19 11:11:57'),
('38', '1', '371', '2026-06-25', 'sale_invoice', '7', 'HD-000002', '0.000', '6.000', '1050000.00', '24.000', '25200000.00', NULL, '2026-07-19 11:11:57'),
('39', '1', '366', '2026-06-25', 'sale_invoice', '7', 'HD-000002', '0.000', '3.000', '650000.00', '22.000', '14300000.00', NULL, '2026-07-19 11:11:57'),
('40', '1', '368', '2026-07-08', 'sale_invoice', '8', 'HD-000003', '0.000', '2.000', '110000.00', '78.000', '8580000.00', NULL, '2026-07-19 11:11:57'),
('41', '1', '378', '2026-07-08', 'sale_invoice', '8', 'HD-000003', '0.000', '4.000', '150000.00', '56.000', '8400000.00', NULL, '2026-07-19 11:11:57'),
('42', '1', '364', '2026-07-28', 'sale_invoice', '9', 'HD-000004', '0.000', '1.000', '420000.00', '35.000', '14700000.00', NULL, '2026-07-28 11:58:03');

-- --------------------------------------------------------
-- Bang `stock_reservations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_reservations`;
CREATE TABLE `stock_reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_resv_order` (`order_id`),
  KEY `idx_resv_part` (`part_id`),
  CONSTRAINT `fk_resv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resv_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_reservations` (`id`, `order_id`, `part_id`, `quantity`, `create_at`) VALUES
('3', '5', '367', '3.000', '2026-07-19 11:11:57'),
('4', '5', '369', '2.000', '2026-07-19 11:11:57'),
('5', '6', '368', '1.000', '2026-07-19 11:11:57'),
('6', '8', '378', '3.000', '2026-07-27 12:21:24'),
('7', '9', '371', '1.000', '2026-07-27 12:22:19');

-- --------------------------------------------------------
-- Bang `stock_take_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_take_items`;
CREATE TABLE `stock_take_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `take_id` int NOT NULL,
  `part_id` int NOT NULL,
  `book_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `actual_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `diff_qty` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `diff_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sti_take` (`take_id`),
  KEY `idx_sti_part` (`part_id`),
  CONSTRAINT `fk_sti_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sti_take` FOREIGN KEY (`take_id`) REFERENCES `stock_takes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `stock_takes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_takes`;
CREATE TABLE `stock_takes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `take_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse_id` int NOT NULL,
  `take_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surplus_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shortage_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `acc_voucher_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_take_no` (`take_no`),
  KEY `idx_take_wh` (`warehouse_id`),
  KEY `fk_take_voucher` (`acc_voucher_id`),
  CONSTRAINT `fk_take_voucher` FOREIGN KEY (`acc_voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_take_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `stocks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stocks`;
CREATE TABLE `stocks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `warehouse_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `avg_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_wh_part` (`warehouse_id`,`part_id`),
  KEY `idx_stocks_part` (`part_id`),
  CONSTRAINT `fk_stocks_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stocks_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stocks` (`id`, `warehouse_id`, `part_id`, `quantity`, `avg_cost`, `update_at`) VALUES
('12', '1', '364', '35.000', '420000.00', '2026-07-28 11:58:03'),
('13', '1', '366', '22.000', '650000.00', '2026-07-19 11:11:57'),
('14', '1', '371', '24.000', '1050000.00', '2026-07-19 11:11:57'),
('15', '1', '379', '20.000', '600000.00', '2026-07-19 11:11:57'),
('16', '1', '367', '110.000', '70000.00', '2026-07-19 11:11:57'),
('17', '1', '368', '78.000', '110000.00', '2026-07-19 11:11:57'),
('18', '1', '369', '92.000', '130000.00', '2026-07-19 11:11:57'),
('19', '1', '370', '15.000', '560000.00', '2026-07-19 11:11:57'),
('20', '1', '376', '90.000', '80000.00', '2026-07-19 11:11:57'),
('21', '1', '377', '70.000', '120000.00', '2026-07-19 11:11:57'),
('22', '1', '378', '56.000', '150000.00', '2026-07-19 11:11:57'),
('23', '1', '365', '22.000', '480000.00', '2026-07-19 11:11:57'),
('24', '1', '372', '12.000', '1900000.00', '2026-07-19 11:11:57'),
('25', '1', '373', '8.000', '2900000.00', '2026-07-19 11:11:57'),
('26', '1', '374', '16.000', '1450000.00', '2026-07-19 11:11:57'),
('27', '1', '375', '24.000', '470000.00', '2026-07-19 11:11:57');

-- --------------------------------------------------------
-- Bang `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `group_id` int DEFAULT NULL,
  `current_activity` datetime DEFAULT NULL,
  `forgot_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `fk_users_group` (`group_id`),
  CONSTRAINT `fk_users_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `group_id`, `current_activity`, `forgot_key`, `active_key`, `create_at`, `update_at`) VALUES
('15', 'Tạ Hoàng An', 'hoangan.web@gmail.com', '$2y$10$jUjJwzRtdfDsVYWodWdgP.68TQ8yspp14uL9.WlBfAHPf30xpxnxC', '1', '9', '2021-12-11 03:00:48', NULL, NULL, NULL, NULL),
('16', 'Hoàng Anh', 'hoang@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '10', NULL, NULL, NULL, NULL, NULL),
('17', 'Văn Tuấn', 'tuan@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '11', NULL, NULL, NULL, NULL, NULL),
('20', 'Nguyễn Văn A', 'nguyenvana@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '1', '9', NULL, NULL, NULL, '2021-09-15 18:23:26', NULL),
('23', 'Hoàng Anh', 'hoanganh@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', '0', '10', NULL, NULL, NULL, '2021-09-17 09:11:08', NULL);

-- --------------------------------------------------------
-- Bang `visits`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/',
  `referrer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyword` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visit_date` (`create_at`),
  KEY `idx_visit_url` (`url`),
  KEY `idx_visits_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visits` (`id`, `url`, `referrer`, `keyword`, `ip`, `user_agent`, `member_id`, `create_at`) VALUES
('6', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 05:33:26'),
('7', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 06:00:22'),
('8', '/', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:12:14'),
('9', '/', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:30'),
('10', '/', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:31'),
('11', 'san-pham', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:32'),
('12', 'san-pham', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:33'),
('13', 'du-an', 'http://127.0.0.1:8899/san-pham?promo=1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:33'),
('14', 'thu-vien', 'http://127.0.0.1:8899/du-an', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:34'),
('15', 'tin-tuc', 'http://127.0.0.1:8899/thu-vien', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:34'),
('16', 'gio-hang', 'http://127.0.0.1:8899/tin-tuc', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:40'),
('17', '/', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:44'),
('18', 'san-pham', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:47'),
('19', 'san-pham', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:48'),
('20', '/', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:48'),
('21', '/', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:49'),
('22', 'gio-hang', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:32:59'),
('23', '/', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:33:01'),
('24', 'san-pham', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:33:01'),
('25', '/', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 06:33:03'),
('26', 'lien-he', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 06:42:49'),
('27', 'lien-he', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 06:42:49'),
('28', 'lien-he', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 10:38:08'),
('29', 'lien-he', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 10:39:10'),
('30', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('31', 'san-pham', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('32', 'tin-tuc', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('33', 'du-an', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('34', 'lien-he', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('35', 'san-pham', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:13:11'),
('36', '/', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:35:43'),
('37', 'san-pham/giam-xoc-truoc-mazda-cx-5-pt-0011', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:35:50'),
('38', '/', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:35:55'),
('39', 'gio-hang', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:02'),
('40', 'san-pham', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:04'),
('41', 'san-pham', 'http://127.0.0.1:8899/san-pham?promo=1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:05'),
('42', '/', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:06'),
('43', 'du-an', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:07'),
('44', 'san-pham', 'http://127.0.0.1:8899/du-an', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:10'),
('45', 'thu-vien', 'http://127.0.0.1:8899/san-pham?promo=1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:11'),
('46', 'san-pham', 'http://127.0.0.1:8899/thu-vien', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:15'),
('47', 'tin-tuc', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:16'),
('48', 'san-pham', 'http://127.0.0.1:8899/tin-tuc', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:19'),
('49', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:36:21'),
('50', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:37:06'),
('51', 'san-pham', 'http://127.0.0.1:8899/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:37:08'),
('52', 'san-pham', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:37:11'),
('53', 'san-pham', 'http://127.0.0.1:8899/san-pham?module=san-pham&page=2', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:37:29'),
('54', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 11:37:30'),
('55', 'san-pham', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:41:59'),
('56', 'san-pham/ma-phanh-truoc-toyota-vios-pt-0001', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 11:42:00'),
('57', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:28'),
('58', 'san-pham', 'http://127.0.0.1:8899/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:30'),
('59', 'san-pham/day-curoa-cam-toyota-pt-0007', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:37'),
('60', 'gio-hang', 'http://127.0.0.1:8899/san-pham/day-curoa-cam-toyota-pt-0007', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:42'),
('61', 'dat-hang', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:45'),
('62', 'gio-hang', 'http://127.0.0.1:8899/dat-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:52'),
('63', 'gio-hang', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:45:58'),
('64', 'gio-hang', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:46:05'),
('65', 'gio-hang', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:46:10'),
('66', '/', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:46:14'),
('67', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 12:49:15'),
('68', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 12:50:39'),
('69', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 12:53:28'),
('70', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 12:53:28'),
('71', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 12:53:28'),
('72', '/', 'http://127.0.0.1:8899/gio-hang', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 12:54:25'),
('73', '/', NULL, NULL, '127.0.0.1', 'curl/8.15.0', NULL, '2026-07-19 13:19:25'),
('74', 'san-pham', 'http://127.0.0.1:8899/', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 15:19:24'),
('75', 'san-pham', 'http://127.0.0.1:8899/san-pham', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 15:19:25'),
('76', 'san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'http://127.0.0.1:8899/san-pham?category%5B%5D=2&car_model=&price_min=&price_max=&sort=&_token=da5b0f6434f3f17b40db7fb09d91af618173a75bd4bb3ac332e55ffba74fa178&part_id=371', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-19 15:19:28'),
('77', '/', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:49:07'),
('78', '/', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:49:20'),
('79', '/', NULL, NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:52:38'),
('80', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:52:47'),
('81', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:53:17'),
('82', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:56:51'),
('83', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-truoc-ford-ranger-pt-0016', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:56:55'),
('84', '/', 'http://etek.rikkeiedu.org/gio-hang', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:56:57'),
('85', 'gio-hang', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:05'),
('86', '/', 'http://etek.rikkeiedu.org/gio-hang', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:08'),
('87', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:13'),
('88', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:16'),
('89', '/', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-truoc-ford-ranger-pt-0016', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:18'),
('90', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:18'),
('91', '/', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:21'),
('92', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:21'),
('93', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:27'),
('94', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:28'),
('95', 'san-pham', 'http://etek.rikkeiedu.org/du-an', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:57:29'),
('96', '/', NULL, NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:58:44'),
('97', '/', NULL, NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:58:44'),
('98', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:23'),
('99', 'du-an', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-truoc-ford-ranger-pt-0016', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:35'),
('100', 'san-pham', 'http://etek.rikkeiedu.org/du-an', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:36'),
('101', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:37'),
('102', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', 'aaa', '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:39'),
('103', '/', 'http://etek.rikkeiedu.org/san-pham?q=aaa', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:40'),
('104', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:42'),
('105', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-19 15:59:44'),
('106', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:40:23'),
('107', '/', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:40:44'),
('108', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:42:38'),
('109', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:42:40'),
('110', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:44:32'),
('111', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:44:33'),
('112', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:44:34'),
('113', '/', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:30'),
('114', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:31'),
('115', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:33'),
('116', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:34'),
('117', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:39'),
('118', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:40'),
('119', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:42'),
('120', 'du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', 'http://etek.rikkeiedu.org/du-an', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:46'),
('121', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:48'),
('122', 'du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:49'),
('123', 'du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', 'http://etek.rikkeiedu.org/du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:49'),
('124', 'du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', 'http://etek.rikkeiedu.org/du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:49'),
('125', 'du-an', 'http://etek.rikkeiedu.org/du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:51'),
('126', 'san-pham', 'http://etek.rikkeiedu.org/du-an', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:51'),
('127', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:53'),
('128', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:54'),
('129', 'san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=2&car_model=&price_min=&price_max=&sort=&_token=7a389d9c9a954e56ae37d90262be873ae319721386161c4c77d9d0cbf1bf3485&part_id=371', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:48:55'),
('130', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:00'),
('131', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:08'),
('132', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:10'),
('133', 'du-an', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:11'),
('134', 'thu-vien', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:13'),
('135', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:15'),
('136', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:16'),
('137', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:17'),
('138', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:18'),
('139', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:18'),
('140', '/', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:49:23'),
('141', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 03:51:50'),
('142', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 03:54:06'),
('143', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:16'),
('144', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:20'),
('145', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:53'),
('146', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:53'),
('147', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:54'),
('148', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:54'),
('149', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-20 03:58:54'),
('150', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:52'),
('151', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:52'),
('152', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:53'),
('153', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:53'),
('154', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:53'),
('155', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:53'),
('156', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:54'),
('157', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:54'),
('158', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:00:54'),
('159', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 04:01:19'),
('160', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:30'),
('161', '/', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:38'),
('162', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:41'),
('163', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:45'),
('164', 'tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:47'),
('165', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:49'),
('166', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:01:51'),
('167', 'san-pham', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 04:01:55'),
('168', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:39'),
('169', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:40'),
('170', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:40'),
('171', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:40'),
('172', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:40'),
('173', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:42'),
('174', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:02:45'),
('175', 'lien-he', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 04:04:20'),
('176', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:08'),
('177', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:09'),
('178', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:09'),
('179', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:09'),
('180', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:10'),
('181', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:10'),
('182', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:10'),
('183', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:05:11'),
('184', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:11:11'),
('185', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:11:11'),
('186', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:12:56'),
('187', 'san-pham', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Claude/1.22209.0 Chrome/148.0.7778.271 Electron/42.5.1 Safari/537.36 MSIX', NULL, '2026-07-20 04:15:45'),
('188', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:27'),
('189', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:35'),
('190', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:36'),
('191', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:37'),
('192', 'tin-tuc', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:37'),
('193', 'tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:38'),
('194', '/', 'http://etek.rikkeiedu.org/tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:21:40'),
('195', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:27:06'),
('196', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 04:38:34'),
('197', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Zalo iOS/260602804 ZaloTheme/light ZaloLanguage/vn', NULL, '2026-07-20 04:38:37'),
('198', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 04:39:21'),
('199', 'san-pham', 'http://etek.rikkeiedu.org/', 'a', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 04:39:56'),
('200', '/', 'http://etek.rikkeiedu.org/san-pham?q=a', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-20 04:40:04'),
('201', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 04:56:06'),
('202', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:03:14'),
('203', '/', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-truoc-ford-ranger-pt-0016', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:03:20'),
('204', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:10:55'),
('205', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:10:58');
INSERT INTO `visits` (`id`, `url`, `referrer`, `keyword`, `ip`, `user_agent`, `member_id`, `create_at`) VALUES
('206', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:10:59'),
('207', 'thu-vien', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:00'),
('208', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:01'),
('209', 'thu-vien', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:03'),
('210', 'du-an', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:04'),
('211', 'san-pham', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:05'),
('212', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:06'),
('213', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:11:06'),
('214', '/', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 05:59:35'),
('215', '/', NULL, NULL, '49.213.78.21', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, '2026-07-20 06:37:50'),
('216', '/', NULL, NULL, '14.177.129.65', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.47 Mobile Safari/537.36 Zalo android/260602901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-20 06:48:40'),
('217', 'san-pham', 'http://etek.rikkeiedu.org/?zarsrc=31&utm_source=zalo&utm_medium=zalo&utm_campaign=zalo', NULL, '14.177.129.65', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.47 Mobile Safari/537.36 Zalo android/260602901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-20 06:48:56'),
('218', '/', NULL, NULL, '14.177.129.65', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.47 Mobile Safari/537.36 Zalo android/260602901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-20 06:48:58'),
('219', '/', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-20 06:49:44'),
('220', '/', 'http://etek.rikkeiedu.org/admin/products/edit/371', NULL, '222.252.20.108', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-21 06:20:07'),
('221', '/', NULL, NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-22 05:39:43'),
('222', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:38:57'),
('223', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:03'),
('224', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:04'),
('225', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:04'),
('226', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:04'),
('227', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:05'),
('228', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:05'),
('229', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:05'),
('230', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:05'),
('231', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:05'),
('232', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:06'),
('233', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:06'),
('234', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:06'),
('235', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:06'),
('236', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:06'),
('237', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:07'),
('238', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:07'),
('239', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:07'),
('240', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:07'),
('241', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:07'),
('242', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:08'),
('243', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:08'),
('244', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 03:39:08'),
('245', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 03:55:02'),
('246', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 03:55:09'),
('247', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 03:55:09'),
('248', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 03:55:23'),
('249', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 03:55:40'),
('250', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:37'),
('251', 'gioi-thieu', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:37'),
('252', 'san-pham', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:38'),
('253', 'du-an', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:38'),
('254', 'thu-vien', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:38'),
('255', 'tin-tuc', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:38'),
('256', 'gio-hang', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:12:39'),
('257', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:41'),
('258', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:41'),
('259', 'gioi-thieu', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:42'),
('260', 'gioi-thieu', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:42'),
('261', 'san-pham', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:42'),
('262', 'san-pham', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:42'),
('263', 'du-an', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:43'),
('264', 'du-an', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:43'),
('265', 'thu-vien', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:43'),
('266', 'thu-vien', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:43'),
('267', 'tin-tuc', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:43'),
('268', 'tin-tuc', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:44'),
('269', 'gio-hang', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:44'),
('270', 'gio-hang', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:44'),
('271', 'lien-he', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:45'),
('272', 'lien-he', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:45'),
('273', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:54'),
('274', 'gio-hang', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:18:54'),
('275', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:22:18'),
('276', 'gio-hang', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-27 04:22:19'),
('277', 'san-pham', 'http://localhost:88/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:12'),
('278', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:14'),
('279', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:35'),
('280', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:38'),
('281', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:39'),
('282', 'san-pham', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:41'),
('283', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham?q=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 04:32:42'),
('284', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 06:51:58'),
('285', '/', NULL, NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-27 11:29:53'),
('286', '/', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-27 11:30:04'),
('287', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:31:02'),
('288', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:02'),
('289', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:07'),
('290', 'san-pham/loc-dau-honda-cr-v-pt-0013', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:41'),
('291', 'san-pham/loc-dau-honda-cr-v-pt-0013', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:47'),
('292', '/', 'http://etek.rikkeiedu.org/san-pham/loc-dau-honda-cr-v-pt-0013', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:48'),
('293', 'san-pham/giam-xoc-truoc-mazda-cx-5-pt-0011', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:50'),
('294', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/giam-xoc-truoc-mazda-cx-5-pt-0011', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:32:56'),
('295', 'gioi-thieu', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:33:00'),
('296', 'san-pham', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:33:01'),
('297', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-27 11:33:02'),
('298', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:33:08'),
('299', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:33:20'),
('300', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:33:45'),
('301', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:34:00'),
('302', 'san-pham', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:34:26'),
('303', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:34:30'),
('304', '/', 'http://etek.rikkeiedu.org/admin/part-categories', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:08'),
('305', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:15'),
('306', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:23'),
('307', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:24'),
('308', 'gioi-thieu', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:29'),
('309', 'san-pham', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:36'),
('310', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:37'),
('311', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:39'),
('312', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:40'),
('313', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:42'),
('314', 'du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:47'),
('315', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:42:52'),
('316', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:21'),
('317', 'san-pham/loc-dau-honda-cr-v-pt-0013', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:30'),
('318', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:31'),
('319', 'du-an', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:33'),
('320', 'thu-vien', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:34'),
('321', '/', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:36'),
('322', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:50'),
('323', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:53'),
('324', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:54'),
('325', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:56'),
('326', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:57'),
('327', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:51:59'),
('328', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:52:02'),
('329', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:52:46'),
('330', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:52:48'),
('331', 'lien-he', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:53:11'),
('332', 'lien-he', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:53:24'),
('333', 'lien-he', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:11'),
('334', 'lien-he', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:20'),
('335', 'tin-tuc', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:33'),
('336', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:37'),
('337', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=tin-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:38'),
('338', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:40'),
('339', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=khuyen-mai', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:41'),
('340', 'san-pham', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:43'),
('341', '/', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:57:58'),
('342', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:58:00'),
('343', 'san-pham', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:58:56'),
('344', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:58:58'),
('345', 'du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:00'),
('346', 'gioi-thieu', 'http://etek.rikkeiedu.org/du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:03'),
('347', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:04'),
('348', 'san-pham', 'http://etek.rikkeiedu.org/', 'huy', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:11'),
('349', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=huy', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:15'),
('350', 'san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'http://etek.rikkeiedu.org/san-pham?q=phanh', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:19'),
('351', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=huy', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:20'),
('352', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:22'),
('353', 'san-pham/dia-phanh-truoc-vios-pt-0003', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:23'),
('354', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/dia-phanh-truoc-vios-pt-0003', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:25'),
('355', 'san-pham/dia-phanh-truoc-vios-pt-0003', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:27'),
('356', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:29'),
('357', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:31'),
('358', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:33'),
('359', '/', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:38'),
('360', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:39'),
('361', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:41'),
('362', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:52'),
('363', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 11:59:53'),
('364', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:02'),
('365', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-truoc-ford-ranger-pt-0016', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:03'),
('366', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:06'),
('367', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:06'),
('368', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:07'),
('369', '/', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:09'),
('370', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:11'),
('371', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:13'),
('372', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:14'),
('373', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:15'),
('374', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:16'),
('375', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:18'),
('376', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:44'),
('377', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=17&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:47'),
('378', '/', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:48'),
('379', 'san-pham/giam-xoc-truoc-mazda-cx-5-pt-0011', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:52'),
('380', 'san-pham', 'http://etek.rikkeiedu.org/san-pham/giam-xoc-truoc-mazda-cx-5-pt-0011', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:00:55'),
('381', 'san-pham/day-curoa-cam-toyota-pt-0007', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:03'),
('382', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/day-curoa-cam-toyota-pt-0007', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:06'),
('383', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:08'),
('384', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/day-curoa-cam-toyota-pt-0007', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:08'),
('385', 'san-pham/day-curoa-cam-toyota-pt-0007', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:09'),
('386', 'thu-vien', 'http://etek.rikkeiedu.org/san-pham/day-curoa-cam-toyota-pt-0007', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:27'),
('387', 'du-an', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:28'),
('388', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:01:30'),
('389', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:03:42'),
('390', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:28'),
('391', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:29'),
('392', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:31'),
('393', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:33'),
('394', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:35'),
('395', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category[]=10', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:37'),
('396', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:38'),
('397', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=10&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:39'),
('398', '/', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:41'),
('399', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:43'),
('400', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category[]=14', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:46'),
('401', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:48'),
('402', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:49'),
('403', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:50'),
('404', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=5&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:51'),
('405', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:54');
INSERT INTO `visits` (`id`, `url`, `referrer`, `keyword`, `ip`, `user_agent`, `member_id`, `create_at`) VALUES
('406', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=14&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:56'),
('407', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:57'),
('408', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=10&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:05:58'),
('409', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:00'),
('410', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:03'),
('411', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=369', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:05'),
('412', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:07'),
('413', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=369', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:11'),
('414', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=10&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:06:13'),
('415', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:22'),
('416', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=new&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:24'),
('417', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:50'),
('418', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=2&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:51'),
('419', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:52'),
('420', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:53'),
('421', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:07:57'),
('422', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=268&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:02'),
('423', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:13'),
('424', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=100000&price_max=500000&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:22'),
('425', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=500000&price_max=700000&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=369', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:31'),
('426', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&promo=1&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=375', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:38'),
('427', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&car_model=&price_min=&price_max=&promo=1&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=375&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:41'),
('428', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:44'),
('429', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:52'),
('430', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_asc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:55'),
('431', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&car_model=&price_min=&price_max=&sort=price_asc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:08:58'),
('432', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:04'),
('433', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?brand%5B%5D=1&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:09'),
('434', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:12'),
('435', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:17'),
('436', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:19'),
('437', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:21'),
('438', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:25'),
('439', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=3&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:29'),
('440', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:32'),
('441', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:34'),
('442', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:37'),
('443', 'san-pham/loc-dau-dong-co-toyota-pt-0004', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&origin%5B0%5D=1&origin%5B1%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:38'),
('444', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:39'),
('445', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&origin%5B0%5D=1&origin%5B1%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:41'),
('446', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=367', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:45'),
('447', 'san-pham/loc-gio-honda-city-pt-0014', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:49'),
('448', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=367', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:50'),
('449', 'san-pham/loc-gio-dong-co-vios-pt-0005', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:52'),
('450', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=367', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:54'),
('451', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:09:56'),
('452', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:04'),
('453', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:06'),
('454', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:09'),
('455', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:16'),
('456', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&origin%5B0%5D=1&origin%5B1%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:18'),
('457', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&origin%5B0%5D=1&origin%5B1%5D=2&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=379&page=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:25'),
('458', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:28'),
('459', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&origin%5B0%5D=1&origin%5B1%5D=2&origin%5B2%5D=6&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=373&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:40'),
('460', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?brand%5B%5D=3&origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=376', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:43'),
('461', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&brand%5B%5D=3&origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=374', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:44'),
('462', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?brand%5B%5D=3&origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:46'),
('463', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=3&brand%5B%5D=3&origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=374', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:46'),
('464', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?brand%5B%5D=3&origin%5B%5D=1&origin%5B%5D=2&origin%5B%5D=6&car_model=&price_min=&price_max=&sort=price_desc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:48'),
('465', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:10:59'),
('466', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:01'),
('467', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:03'),
('468', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:05'),
('469', 'san-pham', 'http://etek.rikkeiedu.org/', 'nhật', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:12'),
('470', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=nh%E1%BA%ADt', 'nhật', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:16'),
('471', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=nh%E1%BA%ADt&category%5B%5D=2&car_model=&price_min=&price_max=&sort=', 'nhật', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:17'),
('472', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=nh%E1%BA%ADt&car_model=&price_min=&price_max=&sort=', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:23'),
('473', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:25'),
('474', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh&category%5B%5D=7&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:26'),
('475', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:27'),
('476', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=phanh&category%5B%5D=3&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', 'phanh', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:28'),
('477', 'san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:32'),
('478', 'san-pham', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'PT-0002', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:39'),
('479', 'san-pham/ma-phanh-sau-toyota-camry-pt-0002', 'http://etek.rikkeiedu.org/san-pham?q=phanh&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=366', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:41'),
('480', 'san-pham', 'http://etek.rikkeiedu.org/san-pham/ma-phanh-sau-toyota-camry-pt-0002', '04466-33471', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:49'),
('481', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=+04466-33471', '04466-33471', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:56'),
('482', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=04466-33471&category%5B%5D=17&car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=365', '04466-33471', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:11:59'),
('483', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=04466-33471&car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:03'),
('484', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=', '0014', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:10'),
('485', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=0014', 'PT', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:15'),
('486', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=PT', 'NG', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:24'),
('487', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=NG', '00', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:32'),
('488', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?q=00', 'cơ', '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:39'),
('489', '/', 'http://etek.rikkeiedu.org/san-pham?q=c%C6%A1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:46'),
('490', 'gioi-thieu', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:50'),
('491', 'lien-he', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:52'),
('492', 'san-pham', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:57'),
('493', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:12:59'),
('494', 'du-an', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:01'),
('495', 'du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:03'),
('496', 'du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', 'http://etek.rikkeiedu.org/du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:05'),
('497', 'du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', 'http://etek.rikkeiedu.org/du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:06'),
('498', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:07'),
('499', 'du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:09'),
('500', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/cung-ung-ac-quy-cho-dai-ly-mien-bac', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:10'),
('501', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:11'),
('502', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:11'),
('503', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:12'),
('504', 'du-an/du-an-phu-tung-doi-xe-doanh-nghiep', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:12'),
('505', 'du-an', 'http://etek.rikkeiedu.org/du-an/du-an-phu-tung-doi-xe-doanh-nghiep', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:14'),
('506', 'du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:17'),
('507', 'du-an', 'http://etek.rikkeiedu.org/du-an/trang-bi-thiet-bi-cho-xuong-dich-vu-toyota', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:20'),
('508', '/', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:21'),
('509', 'gioi-thieu', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:23'),
('510', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:25'),
('511', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:26'),
('512', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:27'),
('513', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:28'),
('514', 'du-an', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:30'),
('515', 'thu-vien', 'http://etek.rikkeiedu.org/du-an', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:31'),
('516', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:32'),
('517', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:34'),
('518', 'thu-vien', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:35'),
('519', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:36'),
('520', 'thu-vien/hinh-anh-kho-hang', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:37'),
('521', 'tin-tuc', 'http://etek.rikkeiedu.org/thu-vien/hinh-anh-kho-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:39'),
('522', 'thu-vien', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:45'),
('523', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:47'),
('524', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:48'),
('525', 'tin-tuc', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:51'),
('526', 'tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:52'),
('527', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:56'),
('528', 'tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:56'),
('529', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:58'),
('530', 'tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:13:59'),
('531', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:01'),
('532', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:04'),
('533', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=tin-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:06'),
('534', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=khuyen-mai', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:08'),
('535', 'san-pham', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:09'),
('536', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:11'),
('537', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:13'),
('538', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:15'),
('539', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=tin-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:17'),
('540', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:19'),
('541', '/', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:21'),
('542', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:25'),
('543', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:26'),
('544', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:27'),
('545', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:27'),
('546', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:28'),
('547', '/', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:33'),
('548', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:35'),
('549', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:43'),
('550', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:49'),
('551', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:51'),
('552', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:54'),
('553', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:54'),
('554', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:56'),
('555', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:14:59'),
('556', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:01'),
('557', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:03'),
('558', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:07'),
('559', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:09'),
('560', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:11'),
('561', '/', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:17'),
('562', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:23'),
('563', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:24'),
('564', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:25'),
('565', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:28'),
('566', 'dat-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:41'),
('567', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:42'),
('568', 'gio-hang/hoan-tat', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:49'),
('569', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:53'),
('570', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:54'),
('571', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:57'),
('572', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:15:58'),
('573', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:02'),
('574', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:03'),
('575', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:14'),
('576', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:15'),
('577', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:17'),
('578', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:18'),
('579', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:19'),
('580', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:21'),
('581', 'gio-hang/hoan-tat', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:29'),
('582', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:31'),
('583', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=0a70e0f8b3ba856e09dfcc5b3f79a1503132055a7da20c78d81a7e845df28b39&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:32'),
('584', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:37'),
('585', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:46'),
('586', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:47'),
('587', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:16:48'),
('588', 'gio-hang/hoan-tat', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:17:01'),
('589', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:17:03'),
('590', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:18:23'),
('591', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:18:26'),
('592', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:18:28'),
('593', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:00'),
('594', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:16'),
('595', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:20'),
('596', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:23'),
('597', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:24'),
('598', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:26'),
('599', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:29'),
('600', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:30'),
('601', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:31'),
('602', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:33'),
('603', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:34'),
('604', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:35'),
('605', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-iridium-ngk-pt-0006', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:37');
INSERT INTO `visits` (`id`, `url`, `referrer`, `keyword`, `ip`, `user_agent`, `member_id`, `create_at`) VALUES
('606', 'san-pham/bugi-iridium-ngk-pt-0006', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:38'),
('607', 'san-pham', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:39'),
('608', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:40'),
('609', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/bugi-ngk-laser-kia-pt-0015', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:42'),
('610', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:50'),
('611', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:55'),
('612', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:19:57'),
('613', 'dat-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:20:04'),
('614', 'dat-hang/hoan-tat', 'http://etek.rikkeiedu.org/dat-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:24'),
('615', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:26'),
('616', 'thu-vien', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:29'),
('617', 'san-pham', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:30'),
('618', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:31'),
('619', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=&_token=da4ff4dee719e9146b3cb8c863e6a75eed451ecbc7d760a353a160be764c31c7&part_id=371', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:34'),
('620', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:35'),
('621', 'dat-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:21:36'),
('622', 'dat-hang/hoan-tat', 'http://etek.rikkeiedu.org/dat-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:22:19'),
('623', '/', 'http://etek.rikkeiedu.org/dat-hang/hoan-tat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:23:40'),
('624', 'gio-hang', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:23:45'),
('625', 'gio-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:23:51'),
('626', 'lien-he', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:23:55'),
('627', 'gio-hang', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:24:06'),
('628', 'lien-he', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:24:52'),
('629', 'tin-tuc', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:15'),
('630', 'tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:18'),
('631', 'tin-tuc', 'http://etek.rikkeiedu.org/lien-he', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:19'),
('632', 'san-pham', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:22'),
('633', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/san-pham?q=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:40'),
('634', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:41'),
('635', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:25:59'),
('636', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:26:23'),
('637', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:26:47'),
('638', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '3', '2026-07-27 12:27:00'),
('639', '/', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:27:40'),
('640', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:27:43'),
('641', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:27:49'),
('642', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:28:02'),
('643', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:28:48'),
('644', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:28:52'),
('645', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:28:53'),
('646', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:29:13'),
('647', '/', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:17'),
('648', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:21'),
('649', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:25'),
('650', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:36'),
('651', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '5', '2026-07-27 12:29:48'),
('652', '/', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:51'),
('653', 'gio-hang', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:53'),
('654', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:29:54'),
('655', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:29:59'),
('656', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:05'),
('657', 'tin-tuc', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:32'),
('658', 'tin-tuc', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:37'),
('659', '/', 'http://etek.rikkeiedu.org/tin-tuc?cat=kien-thuc-ky-thuat', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:40'),
('660', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:46'),
('661', 'gioi-thieu', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:50'),
('662', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:30:51'),
('663', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:10'),
('664', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:14'),
('665', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:16'),
('666', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:17'),
('667', 'san-pham/may-phat-dien-honda-city-pt-0010', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:18'),
('668', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:20'),
('669', 'san-pham/den-pha-toyota-vios-led-pt-0009', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:21'),
('670', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:22'),
('671', 'san-pham/lo-xo-giam-xoc-sau-ranger-pt-0012', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:31:42'),
('672', 'san-pham/lo-xo-giam-xoc-sau-ranger-pt-0012', 'http://etek.rikkeiedu.org/san-pham/lo-xo-giam-xoc-sau-ranger-pt-0012', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:32:11'),
('673', '/', 'http://etek.rikkeiedu.org/san-pham/lo-xo-giam-xoc-sau-ranger-pt-0012', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:32:50'),
('674', 'thanh-vien/dang-nhap', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-27 12:32:51'),
('675', 'thanh-vien', 'http://etek.rikkeiedu.org/thanh-vien/dang-nhap', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:32:56'),
('676', '/', 'http://etek.rikkeiedu.org/thanh-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:44:39'),
('677', 'tin-tuc', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:44:42'),
('678', 'tin-tuc', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:54:32'),
('679', 'gioi-thieu', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:54:34'),
('680', '/', 'http://etek.rikkeiedu.org/gioi-thieu', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:54:35'),
('681', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:54:36'),
('682', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:54:40'),
('683', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:57:25'),
('684', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 12:57:44'),
('685', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:00:32'),
('686', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:00:55'),
('687', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:00:57'),
('688', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham?module=san-pham&page=2', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:02:24'),
('689', 'thu-vien', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:02:27'),
('690', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:02:29'),
('691', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:03:11'),
('692', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:03:13'),
('693', 'thu-vien', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:03:17'),
('694', 'thu-vien', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:03:39'),
('695', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:03:40'),
('696', '/', 'http://etek.rikkeiedu.org/admin/menus', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '4', '2026-07-27 13:04:27'),
('697', '/', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:52:55'),
('698', 'thu-vien', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:02'),
('699', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:05'),
('700', 'thu-vien/hoat-dong-cong-ty', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:11'),
('701', 'du-an', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:19'),
('702', 'du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', 'http://etek.rikkeiedu.org/du-an', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:21'),
('703', 'san-pham', 'http://etek.rikkeiedu.org/du-an/cung-cap-phu-tung-cho-chuoi-gara-abc', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:24'),
('704', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:27'),
('705', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:28'),
('706', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?promo=1', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:30'),
('707', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:31'),
('708', 'gio-hang', 'http://etek.rikkeiedu.org/', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:34'),
('709', 'thu-vien', 'http://etek.rikkeiedu.org/gio-hang', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:36'),
('710', 'tin-tuc', 'http://etek.rikkeiedu.org/thu-vien', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:38'),
('711', 'tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:39'),
('712', 'san-pham', 'http://etek.rikkeiedu.org/tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:41'),
('713', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-07-28 09:53:42'),
('714', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:03:09'),
('715', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:07:51'),
('716', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:07:58'),
('717', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:07:59'),
('718', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:11:35'),
('719', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:11:51'),
('720', 'tin-tuc', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:43:16'),
('721', 'thu-vien', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:43:17'),
('722', 'tin-tuc', 'http://etek.rikkeiedu.org/thu-vien', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:43:18'),
('723', 'tin-tuc/tan-phat-khai-truong-kho-chi-nhanh-mien-nam', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 10:43:19'),
('724', 'khuyen-mai', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-28 10:50:24'),
('725', '/', NULL, NULL, '222.252.24.15', 'curl/8.12.1', NULL, '2026-07-28 10:50:57'),
('726', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:39:12'),
('727', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:40:58'),
('728', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:13'),
('729', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:16'),
('730', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:21'),
('731', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=3&car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:23'),
('732', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:28'),
('733', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?car_model=263&price_min=&price_max=&sort=', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:34'),
('734', 'san-pham/ac-quy-gs-45ah-pt-0008', 'http://etek.rikkeiedu.org/san-pham', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:36'),
('735', 'gio-hang', 'http://etek.rikkeiedu.org/san-pham/ac-quy-gs-45ah-pt-0008', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:41:51'),
('736', 'dat-hang', 'http://etek.rikkeiedu.org/gio-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:42:01'),
('737', '/', 'http://etek.rikkeiedu.org/dat-hang', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:42:49'),
('738', 'san-pham/ma-phanh-truoc-ford-ranger-pt-0016', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 11:42:54'),
('739', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 12:08:51'),
('740', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 12:08:51'),
('741', 'gio-hang', 'http://etek.rikkeiedu.org/', NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 12:09:13'),
('742', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-28 12:29:32'),
('743', '/', NULL, NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:36:26'),
('744', 'san-pham/loc-gio-honda-city-pt-0014', 'http://etek.rikkeiedu.org/?zarsrc=31&utm_source=zalo&utm_medium=zalo&utm_campaign=zalo', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:36:41'),
('745', '/', NULL, NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:36:56'),
('746', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/?zarsrc=31&utm_source=zalo&utm_medium=zalo&utm_campaign=zalo', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:05'),
('747', '/', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:06'),
('748', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:16'),
('749', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category[]=5', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:21'),
('750', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=5&category%5B%5D=11&car_model=&price_min=&price_max=&sort=', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:36'),
('751', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=5&category%5B%5D=11&car_model=261&price_min=&price_max=&sort=', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:37:41'),
('752', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=5&category%5B%5D=11&car_model=&price_min=&price_max=&sort=', NULL, '27.67.211.85', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:43:05'),
('753', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category[]=5', NULL, '14.231.233.82', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:53:50'),
('754', '/', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=5&category%5B%5D=11&car_model=&price_min=&price_max=&sort=', NULL, '14.231.233.82', 'Mozilla/5.0 (Linux; Android 16; Pixel 7 Pro Build/CP1A.260405.005;) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/150.0.7871.124 Mobile Safari/537.36 Zalo android/260701901 ZaloTheme/light ZaloLanguage/vi', NULL, '2026-07-28 12:53:54'),
('755', '/', NULL, NULL, '123.16.143.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:47:01'),
('756', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:24'),
('757', '/', NULL, NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:28'),
('758', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:30'),
('759', '/', NULL, NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:32'),
('760', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:34'),
('761', 'san-pham', 'http://etek.rikkeiedu.org/san-pham', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:36'),
('762', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=9&car_model=&price_min=&price_max=&sort=', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:41'),
('763', 'san-pham', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&category%5B%5D=9&car_model=&price_min=&price_max=&sort=', NULL, '123.16.143.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 14:51:49'),
('764', '/', 'http://etek.rikkeiedu.org/san-pham?category%5B%5D=1&category%5B%5D=9&car_model=257&price_min=&price_max=&sort=', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 15:42:04'),
('765', '/', NULL, NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:22:19'),
('766', 'san-pham', 'http://etek.rikkeiedu.org/', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:36'),
('767', '/', 'http://etek.rikkeiedu.org/san-pham', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:38'),
('768', '/', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:47'),
('769', 'lien-he', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:50'),
('770', '/', 'http://etek.rikkeiedu.org/lien-he', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:52'),
('771', 'lien-he', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:53'),
('772', 'gio-hang', 'http://etek.rikkeiedu.org/lien-he', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:54'),
('773', '/', 'http://etek.rikkeiedu.org/gio-hang', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:26:57'),
('774', '/', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:27:12'),
('775', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:27:15'),
('776', '/', 'http://etek.rikkeiedu.org/', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-28 16:27:29'),
('777', '/', NULL, NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:20:12'),
('778', '/', 'http://etek.rikkeiedu.org/admin', NULL, '14.231.159.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:28:37'),
('779', '/', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:29:22'),
('780', 'tin-tuc', 'http://etek.rikkeiedu.org/', NULL, '27.72.102.65', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:29:24'),
('781', 'thanh-vien/dang-ky', 'http://etek.rikkeiedu.org/tin-tuc', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:29:34'),
('782', '/', 'http://etek.rikkeiedu.org/thanh-vien/dang-ky', NULL, '14.248.82.176', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:29:37'),
('783', 'san-pham/bugi-ngk-laser-kia-pt-0015', 'http://etek.rikkeiedu.org/', NULL, '14.231.159.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', NULL, '2026-07-29 13:30:39'),
('784', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', NULL, '2026-07-30 06:15:31'),
('785', '/', NULL, NULL, '222.252.24.15', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-01 04:35:18'),
('786', 'san-pham', 'http://etek.rikkeiedu.org/thu-vien/hoat-dong-cong-ty', NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-08-01 04:42:10'),
('787', '/', NULL, NULL, '103.180.138.189', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', NULL, '2026-08-01 04:42:48'),
('788', '/', 'http://localhost:88/', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:40:28'),
('789', '/', 'http://localhost:88/', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:47'),
('790', 'tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', 'http://localhost:88/tan-phat/', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:53'),
('791', 'tin-tuc', 'http://localhost:88/tan-phat/tin-tuc/cach-nhan-biet-ma-phanh-can-thay-the', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:55'),
('792', 'du-an', 'http://localhost:88/tan-phat/tin-tuc', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:56'),
('793', 'san-pham', 'http://localhost:88/tan-phat/du-an', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:57'),
('794', 'san-pham', 'http://localhost:88/tan-phat/san-pham?promo=1', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:57'),
('795', '/', 'http://localhost:88/tan-phat/san-pham', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:41:58'),
('796', '/', 'http://localhost:88/tan-phat/san-pham', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, '2026-08-03 04:45:20');

-- --------------------------------------------------------
-- Bang `warehouse_locations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warehouse_locations`;
CREATE TABLE `warehouse_locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `warehouse_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint(1) NOT NULL DEFAULT '1',
  `full_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wl_wh` (`warehouse_id`),
  KEY `idx_wl_parent` (`parent_id`),
  CONSTRAINT `fk_wl_parent` FOREIGN KEY (`parent_id`) REFERENCES `warehouse_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wl_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warehouse_locations` (`id`, `warehouse_id`, `parent_id`, `code`, `name`, `level`, `full_path`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('5', '1', NULL, 'A', 'Khu A', '1', 'Khu A', '0', '1', '2026-07-19 11:11:56', NULL),
('6', '1', '5', 'A-T1', 'Tầng 1', '2', 'Khu A / Tầng 1', '0', '1', '2026-07-19 11:11:56', NULL),
('7', '1', '5', 'A-T2', 'Tầng 2', '2', 'Khu A / Tầng 2', '0', '1', '2026-07-19 11:11:56', NULL),
('8', '1', '6', 'A-T1-K1', 'Kệ 1', '3', 'Khu A / Tầng 1 / Kệ 1', '0', '1', '2026-07-19 11:11:56', NULL),
('9', '1', '6', 'A-T1-K2', 'Kệ 2', '3', 'Khu A / Tầng 1 / Kệ 2', '0', '1', '2026-07-19 11:11:56', NULL),
('10', '1', '7', 'A-T2-K1', 'Kệ 1', '3', 'Khu A / Tầng 2 / Kệ 1', '0', '1', '2026-07-19 11:11:56', NULL),
('11', '1', NULL, 'B', 'Khu B', '1', 'Khu B', '0', '1', '2026-07-19 11:11:56', NULL),
('12', '1', '11', 'B-T1', 'Tầng 1', '2', 'Khu B / Tầng 1', '0', '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `warehouse_transfer_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warehouse_transfer_items`;
CREATE TABLE `warehouse_transfer_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transfer_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ti_transfer` (`transfer_id`),
  KEY `idx_ti_part` (`part_id`),
  CONSTRAINT `fk_ti_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ti_transfer` FOREIGN KEY (`transfer_id`) REFERENCES `warehouse_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `warehouse_transfers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warehouse_transfers`;
CREATE TABLE `warehouse_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_warehouse_id` int NOT NULL,
  `to_warehouse_id` int NOT NULL,
  `transfer_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transfer_no` (`transfer_no`),
  KEY `idx_transfer_from` (`from_warehouse_id`),
  KEY `idx_transfer_to` (`to_warehouse_id`),
  CONSTRAINT `fk_transfer_from` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_transfer_to` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Bang `warehouses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE `warehouses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warehouses_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warehouses` (`id`, `code`, `name`, `address`, `phone`, `is_default`, `sort_order`, `status`, `create_at`, `update_at`) VALUES
('1', 'KHO01', 'Kho tổng', NULL, NULL, '1', '0', '1', '2026-07-18 10:20:14', NULL),
('3', 'KHO02', 'Kho chi nhánh Miền Nam', 'KCN Sóng Thần, Bình Dương', '0274 3777 999', '0', '1', '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `warranty_handovers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warranty_handovers`;
CREATE TABLE `warranty_handovers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `handover_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warranty_id` int NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receive',
  `handover_date` date NOT NULL,
  `deliverer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accessories` text COLLATE utf8mb4_unicode_ci,
  `condition_note` text COLLATE utf8mb4_unicode_ci,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_handover_no` (`handover_no`),
  KEY `idx_handover_warranty` (`warranty_id`),
  CONSTRAINT `fk_handover_warranty` FOREIGN KEY (`warranty_id`) REFERENCES `warranty_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warranty_handovers` (`id`, `handover_no`, `warranty_id`, `type`, `handover_date`, `deliverer`, `receiver`, `accessories`, `condition_note`, `note`, `created_by`, `create_at`, `update_at`) VALUES
('2', 'BBGN-000001', '4', 'receive', '2026-07-15', 'Garage Thành Công', 'KTV Hoàng', '01 máy phát, 01 dây nối', 'Vỏ trầy nhẹ', NULL, '1', '2026-07-19 11:11:56', NULL);

-- --------------------------------------------------------
-- Bang `warranty_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `warranty_requests`;
CREATE TABLE `warranty_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `partner_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_date` date NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `reminded_at` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `issue` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `technician` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_warranty_no` (`request_no`),
  KEY `idx_warranty_status` (`status`),
  KEY `idx_warranty_partner` (`partner_id`),
  KEY `fk_warranty_part` (`part_id`),
  CONSTRAINT `fk_warranty_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_warranty_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warranty_requests` (`id`, `request_no`, `partner_id`, `customer_name`, `phone`, `part_id`, `product_name`, `serial_no`, `received_date`, `appointment_date`, `completed_date`, `reminded_at`, `status`, `issue`, `diagnosis`, `technician`, `fee`, `note`, `created_by`, `create_at`, `update_at`) VALUES
('4', 'BH-000001', NULL, 'Garage Thành Công', '0901234567', NULL, 'Máy phát điện Honda City', 'MP-2024-001', '2026-07-15', '2026-07-18', NULL, NULL, 'processing', 'Khách báo lỗi khi vận hành', NULL, 'KTV Hoàng', '0.00', NULL, '1', '2026-07-19 11:11:56', NULL),
('5', 'BH-000002', NULL, 'Anh Nguyễn Văn Hùng', '0987654321', NULL, 'Ắc quy GS 45Ah', 'AQ-2025-118', '2026-07-10', '2026-07-13', NULL, NULL, 'received', 'Khách báo lỗi khi vận hành', NULL, NULL, '0.00', NULL, '1', '2026-07-19 11:11:56', NULL),
('6', 'BH-000003', NULL, 'Đại lý Phú Sơn', '0912345678', NULL, 'Giảm xóc Mazda CX-5', 'GX-2025-077', '2026-01-05', '2026-01-08', '2026-01-08', NULL, 'done', 'Khách báo lỗi khi vận hành', 'Đã kiểm tra & xử lý', 'KTV Sơn', '350000.00', NULL, '1', '2026-07-19 11:11:56', NULL),
('7', 'BH-000004', NULL, 'Gara Minh Phát', '0934567890', NULL, 'Đèn pha Vios LED', 'DP-2025-045', '2025-11-20', '2025-11-23', '2025-11-25', NULL, 'done', 'Khách báo lỗi khi vận hành', 'Đã kiểm tra & xử lý', 'KTV Hoàng', '200000.00', NULL, '1', '2026-07-19 11:11:56', '2026-07-27 12:47:12');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
