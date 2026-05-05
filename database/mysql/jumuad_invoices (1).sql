-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 10:44 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jumuad_invoices`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_invoice_number` (OUT `new_invoice_no` VARCHAR(50))   BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE last_seq INT;
    DECLARE yr YEAR;
    DECLARE mo TINYINT;

    SET yr = YEAR(NOW());
    SET mo = MONTH(NOW());

    -- Get prefix from settings
    SELECT setting_value INTO prefix FROM company_settings WHERE setting_key = 'invoice_prefix';

    -- Lock row and increment
    INSERT INTO invoice_sequence (sequence_year, sequence_month, last_number)
        VALUES (yr, mo, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1;

    SELECT last_number INTO last_seq
        FROM invoice_sequence WHERE sequence_year = yr AND sequence_month = mo;

    SET new_invoice_no = CONCAT(prefix, '-', yr, '-', LPAD(last_seq, 5, '0'));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `data_type` enum('text','number','boolean','json','date') DEFAULT 'text',
  `is_editable` tinyint(1) DEFAULT 1,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `data_type`, `is_editable`, `description`, `updated_at`) VALUES
(1, 'pharmacy_name', 'MediCare Pharmacy', 'branding', 'text', 1, 'Display name of the pharmacy', '2026-04-18 11:51:10'),
(2, 'pharmacy_tagline', 'Your Health, Our Priority', 'branding', 'text', 1, 'Tagline shown on invoices', '2026-04-18 11:51:10'),
(3, 'address_line1', '123 Health Street', 'contact', 'text', 1, 'Primary address line', '2026-04-18 11:51:10'),
(4, 'address_line2', 'Cordova, Cebu City', 'contact', 'text', 1, 'Secondary address line', '2026-04-26 11:52:49'),
(5, 'phone', '+63 2 8888 0000', 'contact', 'text', 1, 'Contact number', '2026-04-18 11:51:10'),
(6, 'email', 'info@medicaerx.com', 'contact', 'text', 1, 'Contact email', '2026-04-18 11:51:10'),
(7, 'vat_registered', 'true', 'tax', 'boolean', 1, 'Whether VAT registration applies', '2026-04-18 11:51:10'),
(8, 'vat_number', 'VAT-12345678', 'tax', 'text', 1, 'Official VAT registration number', '2026-04-18 11:51:10'),
(9, 'invoice_prefix', 'INV', 'invoice', 'text', 1, 'Prefix for invoice numbers e.g. INV-2024-0001', '2026-04-18 11:51:10'),
(10, 'invoice_footer_note', 'Thank you for choosing us!', 'invoice', 'text', 1, 'Footer message printed on every invoice', '2026-04-18 11:51:10'),
(11, 'currency_code', 'PHP', 'financial', 'text', 1, 'ISO 4217 currency code', '2026-04-18 11:51:10'),
(12, 'currency_symbol', '₱', 'financial', 'text', 1, 'Symbol displayed on invoices', '2026-04-18 11:51:10'),
(13, 'logo_url', '/assets/logo.png', 'branding', 'text', 1, 'Path or URL of pharmacy logo', '2026-04-18 11:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(30) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_senior` tinyint(1) DEFAULT 0,
  `is_pwd` tinyint(1) DEFAULT 0,
  `id_number` varchar(100) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `first_name`, `last_name`, `date_of_birth`, `phone`, `email`, `address`, `is_senior`, `is_pwd`, `id_number`, `loyalty_points`, `notes`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Analou', 'Jumuad', NULL, '09219968891', NULL, 'Sudlon Catarman,Cordova Cebu', 0, 0, NULL, 0, NULL, '2026-04-26 02:26:22', '2026-04-26 03:21:53'),
(2, NULL, 'Jamaica', 'Jumuad', NULL, '09629382415', NULL, 'Juan Sitoy Street', 0, 0, NULL, 0, NULL, '2026-04-26 02:56:07', '2026-04-26 03:44:33'),
(4, NULL, 'Wendell', 'Cabalhin', NULL, '0943434343', NULL, 'Cebu City', 0, 0, NULL, 0, NULL, '2026-04-26 03:49:44', '2026-04-26 03:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `discount_types`
--

CREATE TABLE `discount_types` (
  `id` int(11) NOT NULL,
  `discount_code` varchar(30) NOT NULL,
  `discount_name` varchar(100) NOT NULL,
  `discount_method` enum('percentage','fixed_amount') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applies_to` enum('line_item','subtotal','both') DEFAULT 'subtotal',
  `requires_id` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discount_types`
--

INSERT INTO `discount_types` (`id`, `discount_code`, `discount_name`, `discount_method`, `discount_value`, `applies_to`, `requires_id`, `is_active`, `valid_from`, `valid_until`, `description`, `created_at`) VALUES
(1, 'SENIOR20', 'Senior Citizen Discount', 'percentage', 20.00, 'subtotal', 1, 1, '2024-01-01', NULL, 'Mandatory 20% for Senior Citizens (RA 9994)', '2026-04-18 11:51:10'),
(2, 'PWD20', 'PWD Discount', 'percentage', 20.00, 'subtotal', 1, 1, '2024-01-01', NULL, 'Mandatory 20% for Persons with Disability (RA 10754)', '2026-04-18 11:51:10'),
(3, 'LOYALTY10', 'Loyalty Member Discount', 'percentage', 10.00, 'subtotal', 0, 1, '2024-01-01', NULL, 'Loyalty card holder benefit', '2026-04-18 11:51:10'),
(4, 'PROMO50', '₱50 Off Promo', 'fixed_amount', 50.00, 'subtotal', 0, 1, '2024-06-01', NULL, 'Seasonal promotional discount', '2026-04-18 11:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` datetime NOT NULL DEFAULT current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `status` enum('draft','issued','paid','voided','refunded') DEFAULT 'draft',
  `payment_method_id` int(11) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_tendered` decimal(12,2) DEFAULT NULL,
  `change_amount` decimal(12,2) DEFAULT NULL,
  `prescription_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `voided_reason` varchar(255) DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `invoice_date`, `customer_id`, `cashier_id`, `status`, `payment_method_id`, `payment_ref`, `subtotal`, `total_discount`, `total_tax`, `grand_total`, `amount_tendered`, `change_amount`, `prescription_no`, `notes`, `voided_reason`, `voided_at`, `created_at`, `updated_at`) VALUES
(1, 'INV-2026-00001', '2026-04-26 02:26:22', 1, 2, 'paid', 1, NULL, 140.00, 0.00, 16.80, 156.80, 199.99, 43.19, NULL, NULL, NULL, NULL, '2026-04-26 02:26:22', '2026-04-26 02:49:45'),
(2, 'INV-2026-00002', '2026-04-26 02:56:07', 2, NULL, 'voided', NULL, NULL, 1850.00, 0.00, 222.00, 2072.00, NULL, NULL, NULL, NULL, 'Customer cancelled the order', '2026-04-26 03:48:04', '2026-04-26 02:56:07', '2026-04-26 03:48:04'),
(3, 'INV-2026-00003', '2026-04-26 02:56:16', 2, 2, 'paid', 1, NULL, 2335.00, 0.00, 280.20, 2615.20, 2620.00, 4.80, NULL, NULL, NULL, NULL, '2026-04-26 02:56:16', '2026-04-26 11:06:18'),
(4, 'INV-2026-00004', '2026-04-26 03:21:53', 1, NULL, 'draft', NULL, NULL, 485.00, 0.00, 58.20, 543.20, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-26 03:21:53', '2026-04-26 03:21:53'),
(5, 'INV-2026-00005', '2026-04-26 03:44:12', 2, NULL, 'voided', NULL, NULL, 1864.00, 0.00, 223.68, 2087.68, NULL, NULL, NULL, NULL, 'Customer cancelled', '2026-04-26 03:50:51', '2026-04-26 03:44:12', '2026-04-26 03:50:51'),
(6, 'INV-2026-00006', '2026-04-26 03:49:44', 4, NULL, 'draft', NULL, NULL, 101.00, 0.00, 12.12, 113.12, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-26 03:49:44', '2026-04-26 18:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_discounts`
--

CREATE TABLE `invoice_discounts` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `discount_type_id` int(11) DEFAULT NULL,
  `discount_code` varchar(30) NOT NULL,
  `discount_name` varchar(100) NOT NULL,
  `discount_method` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL,
  `id_number_used` varchar(100) DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `uom_code` varchar(20) NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `tax_rate_id` int(11) DEFAULT NULL,
  `tax_rate_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `line_subtotal` decimal(12,2) NOT NULL,
  `line_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `product_name`, `generic_name`, `uom_code`, `quantity`, `unit_price`, `tax_rate_id`, `tax_rate_pct`, `line_subtotal`, `line_tax`, `line_discount`, `line_total`, `sort_order`) VALUES
(1, 1, 8, 'Myra-E 400', 'Vitamin E 400 IU', 'PC', 5.000, 28.00, 1, 12.00, 140.00, 16.80, 0.00, 156.80, 0),
(2, 2, 9, 'BP Monitor', 'Automatic Blood Pressure Monitor', 'PC', 1.000, 1850.00, 1, 12.00, 1850.00, 222.00, 0.00, 2072.00, 0),
(3, 3, 9, 'BP Monitor', 'Automatic Blood Pressure Monitor', 'PC', 1.000, 1850.00, 1, 12.00, 1850.00, 222.00, 0.00, 2072.00, 0),
(4, 3, 10, 'Neutrogena Hydro Boost', 'Water Gel Moisturizer', 'PC', 1.000, 485.00, 1, 12.00, 485.00, 58.20, 0.00, 543.20, 0),
(5, 4, 10, 'Neutrogena Hydro Boost', 'Water Gel Moisturizer', 'PC', 1.000, 485.00, 1, 12.00, 485.00, 58.20, 0.00, 543.20, 0),
(7, 5, 9, 'BP Monitor', 'Automatic Blood Pressure Monitor', 'PC', 1.000, 1850.00, 1, 12.00, 1850.00, 222.00, 0.00, 2072.00, 0),
(8, 5, 3, 'Alaxan FR', 'Ibuprofen + Paracetamol', 'PC', 1.000, 14.00, 1, 12.00, 14.00, 1.68, 0.00, 15.68, 0),
(10, 6, 4, 'Amoxicillin', 'Amoxicillin Trihydrate 500mg', 'PC', 5.000, 18.00, 1, 12.00, 90.00, 10.80, 0.00, 100.80, 0),
(11, 6, 2, 'Neozep Forte', 'Phenylephrine + Paracetamol', 'PC', 1.000, 11.00, 1, 12.00, 11.00, 1.32, 0.00, 12.32, 0);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_sequence`
--

CREATE TABLE `invoice_sequence` (
  `id` int(11) NOT NULL,
  `sequence_year` year(4) NOT NULL,
  `sequence_month` tinyint(4) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_sequence`
--

INSERT INTO `invoice_sequence` (`id`, `sequence_year`, `sequence_month`, `last_number`) VALUES
(1, '2026', 4, 6);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_04_25_165132_create_permission_tables', 2),
(5, '2026_04_26_000001_add_image_base64_to_products', 3),
(6, '2026_04_26_105217_deduplicate_customers_add_usage_to_products', 4),
(7, '2026_04_27_083148_add_usage_recommendation_to_products', 5);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `method_code` varchar(30) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `requires_ref` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_code`, `method_name`, `is_active`, `requires_ref`, `sort_order`, `description`) VALUES
(1, 'CASH', 'Cash', 1, 0, 1, NULL),
(2, 'GCASH', 'GCash', 1, 1, 2, NULL),
(3, 'MAYA', 'Maya (PayMaya)', 1, 1, 3, NULL),
(4, 'CARD', 'Credit/Debit Card', 1, 1, 4, NULL),
(5, 'HMO', 'HMO / Insurance', 1, 1, 5, NULL),
(6, 'CHEQUE', 'Cheque', 1, 1, 6, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `generic_name` varchar(200) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `uom_id` int(11) DEFAULT NULL,
  `tax_rate_id` int(11) DEFAULT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `requires_rx` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `usage_recommendation` text DEFAULT NULL,
  `image_base64` longtext DEFAULT NULL COMMENT 'Base64 data URI of product image, e.g. data:image/png;base64,...',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `barcode`, `product_name`, `generic_name`, `brand`, `dosage`, `category_id`, `supplier_id`, `uom_id`, `tax_rate_id`, `cost_price`, `selling_price`, `requires_rx`, `is_active`, `stock_quantity`, `reorder_level`, `description`, `usage_recommendation`, `image_base64`, `created_at`, `updated_at`) VALUES
(1, 'BIOG-500', NULL, 'Biogesic', 'Paracetamol 500mg', 'Unilab', '500mg', 2, NULL, 1, 1, 4.00, 8.50, 0, 1, 150, 20, 'Fast-acting pain reliever and fever reducer suitable for adults and children.', NULL, '/images/products/biogesic.jpg', '2026-04-26 01:46:04', '2026-04-26 09:56:12'),
(2, 'NEOZ-CAP', NULL, 'Neozep Forte', 'Phenylephrine + Paracetamol', 'Unilab', '10mg/325mg', 2, NULL, 1, 1, 5.50, 11.00, 0, 1, 79, 15, 'Provides relief from nasal congestion, runny nose, headache, and fever.', NULL, '/images/products/neozep.jpg', '2026-04-26 01:46:04', '2026-04-26 11:49:59'),
(3, 'ALAX-FC', NULL, 'Alaxan FR', 'Ibuprofen + Paracetamol', 'Unilab', '200mg/325mg', 2, NULL, 1, 1, 7.00, 14.00, 0, 1, 59, 10, 'Dual-action pain reliever combining ibuprofen and paracetamol.', NULL, '/images/products/alaxan.jpg', '2026-04-26 01:46:04', '2026-04-26 11:44:33'),
(4, 'AMOX-500', NULL, 'Amoxicillin', 'Amoxicillin Trihydrate 500mg', 'Generics', '500mg', 1, NULL, 1, 1, 9.00, 18.00, 1, 1, 195, 30, 'Penicillin-type antibiotic for bacterial infections.', NULL, '/images/products/amoxicillin.jpg', '2026-04-26 01:46:04', '2026-04-26 11:49:59'),
(5, 'VITC-500', NULL, 'Fern-C', 'Ascorbic Acid 500mg', 'Fern-C', '500mg', 3, NULL, 1, 1, 4.75, 9.50, 0, 1, 300, 50, 'Non-acidic Vitamin C using sodium ascorbate, gentle on the stomach.', NULL, '/images/products/fernc.jpg', '2026-04-26 01:46:04', '2026-04-26 10:22:52'),
(6, 'OMEP-20', NULL, 'Omeprazole', 'Omeprazole 20mg', 'Generics', '20mg', 1, NULL, 1, 1, 8.00, 16.00, 1, 1, 0, 20, 'Proton pump inhibitor that reduces stomach acid production.', NULL, '/images/products/omeprazole.jpg', '2026-04-26 01:46:04', '2026-04-26 10:23:05'),
(7, 'CETI-10', NULL, 'Cetirizine', 'Cetirizine HCl 10mg', 'Zyrtec', '10mg', 2, NULL, 1, 1, 11.00, 22.00, 0, 1, 45, 10, 'Non-drowsy antihistamine for relief from allergy symptoms.', NULL, '/images/products/cetirizine.jpg', '2026-04-26 01:46:04', '2026-04-26 10:23:21'),
(8, 'MYRAE-400', NULL, 'Myra-E 400', 'Vitamin E 400 IU', 'Myra', '400 IU', 3, NULL, 1, 1, 14.00, 28.00, 0, 1, 80, 15, 'Natural Vitamin E that nourishes skin from within.', NULL, '/images/products/myrae.jpg', '2026-04-26 01:46:04', '2026-04-26 10:49:45'),
(9, 'BP-MON', NULL, 'BP Monitor', 'Automatic Blood Pressure Monitor', 'Omron', 'N/A', 4, NULL, 1, 1, 1200.00, 1850.00, 0, 1, 8, 3, 'Clinically validated automatic blood pressure monitor for home use.', NULL, '/images/products/bpmonitor.jpg', '2026-04-26 01:46:04', '2026-04-26 19:06:18'),
(10, 'NEUT-HB', NULL, 'Neutrogena Hydro Boost', 'Water Gel Moisturizer', 'Neutrogena', '50ml', 5, NULL, 1, 1, 280.00, 485.00, 0, 1, 22, 5, 'Lightweight, oil-free moisturizer that quenches skin and keeps it hydrated.', NULL, '/images/products/hydro.jpg', '2026-04-26 01:46:04', '2026-04-26 19:06:18'),
(11, 'CETAL-SYR', NULL, 'Cetalgin Syrup', 'Paracetamol 250mg/5mL', 'Pascual', '250mg/5mL', 2, NULL, 1, 1, 32.00, 65.00, 0, 1, 40, 10, 'Pediatric syrup for gentle fever and pain relief in children.', NULL, '/images/products/cetalgin.jpg', '2026-04-26 01:46:04', '2026-04-26 10:24:19'),
(12, 'JJ-BLOT', NULL, 'Johnson\'s Baby Lotion', 'Baby Moisturizing Lotion', 'Johnson\'s', '500ml', 6, NULL, 1, 1, 100.00, 185.00, 0, 1, 0, 5, 'Clinically proven mild and gentle on baby\'s skin.', NULL, '/images/products/johnson.jpg', '2026-04-26 01:46:04', '2026-04-26 10:24:38');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `category_code` varchar(30) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `default_tax_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `category_code`, `category_name`, `parent_id`, `default_tax_id`, `is_active`, `description`) VALUES
(1, 'RX', 'Prescription Medicines', NULL, 1, 1, NULL),
(2, 'OTC', 'Over-the-Counter Drugs', NULL, 1, 1, NULL),
(3, 'VITAMINS', 'Vitamins & Supplements', NULL, 1, 1, NULL),
(4, 'MEDICAL', 'Medical Supplies', NULL, 1, 1, NULL),
(5, 'BEAUTY', 'Beauty & Personal Care', NULL, 1, 1, NULL),
(6, 'BABY', 'Baby & Maternal Care', NULL, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-04-25 08:52:52', '2026-04-25 08:52:52'),
(2, 'cashier', 'web', '2026-04-25 08:52:52', '2026-04-25 08:52:52'),
(3, 'customer', 'web', '2026-04-25 08:52:52', '2026-04-25 08:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_code` varchar(30) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `id` int(11) NOT NULL,
  `tax_code` varchar(20) NOT NULL,
  `tax_name` varchar(100) NOT NULL,
  `rate_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `applies_to` enum('product','service','both') DEFAULT 'both',
  `is_active` tinyint(1) DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_until` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`id`, `tax_code`, `tax_name`, `rate_percentage`, `applies_to`, `is_active`, `effective_from`, `effective_until`, `description`, `created_at`) VALUES
(1, 'VAT12', 'Value Added Tax 12%', 12.00, 'both', 1, '2024-01-01', NULL, 'Standard Philippine VAT', '2026-04-18 11:51:10'),
(2, 'ZERO', 'Zero-Rated VAT', 0.00, 'both', 1, '2024-01-01', NULL, 'Zero-rated goods (e.g. basic medicines)', '2026-04-18 11:51:10'),
(3, 'EXEMPT', 'VAT Exempt', 0.00, 'product', 1, '2024-01-01', NULL, 'Exempt products (maintenance meds for seniors)', '2026-04-18 11:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `units_of_measure`
--

CREATE TABLE `units_of_measure` (
  `id` int(11) NOT NULL,
  `uom_code` varchar(20) NOT NULL,
  `uom_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units_of_measure`
--

INSERT INTO `units_of_measure` (`id`, `uom_code`, `uom_name`, `is_active`) VALUES
(1, 'PC', 'Piece', 1),
(2, 'TAB', 'Tablet', 1),
(3, 'CAP', 'Capsule', 1),
(4, 'BOX', 'Box', 1),
(5, 'BTL', 'Bottle', 1),
(6, 'AMP', 'Ampule', 1),
(7, 'SACHET', 'Sachet', 1),
(8, 'ML', 'Milliliter', 1),
(9, 'G', 'Gram', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@medicare.com', NULL, '$2y$12$rpEPIdujJHZwNIYu5jKYbOUFGJxwBi0LOTbYn4zIRW.7oKN7FQYLG', NULL, '2026-04-25 08:52:53', '2026-04-25 08:52:53'),
(2, 'Cashier User', 'cashier@medicare.com', NULL, '$2y$12$QdBCeOGBbyHwb0NkCxFTNOB9ehIc9jRZeiTRrKeQkFsNoIX96VSQ2', NULL, '2026-04-25 08:52:53', '2026-04-25 08:52:53'),
(3, 'Customer Kiosk', 'kiosk@medicare.com', NULL, '$2y$12$/eXvpqsl8CLi6FCQUfFH8ea.zJL.9HJy/MSt1rz2cApN/6AANw0gm', 'Lm1TVTSuIsTUUl9vG3clhzW4xBoW072vYZYvVqC1Q39qhQTn32WxGrdgeCh2', '2026-04-25 08:52:54', '2026-04-25 08:52:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_invoice_line_detail`
-- (See below for the actual view)
--
CREATE TABLE `v_invoice_line_detail` (
`invoice_id` int(11)
,`invoice_number` varchar(50)
,`invoice_date` datetime
,`sku` varchar(50)
,`product_name` varchar(200)
,`generic_name` varchar(200)
,`quantity` decimal(10,3)
,`uom_code` varchar(20)
,`unit_price` decimal(12,2)
,`tax_code` varchar(20)
,`tax_rate_pct` decimal(5,2)
,`line_subtotal` decimal(12,2)
,`line_tax` decimal(12,2)
,`line_discount` decimal(12,2)
,`line_total` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_invoice_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_invoice_summary` (
`id` int(11)
,`invoice_number` varchar(50)
,`invoice_date` datetime
,`customer_name` varchar(201)
,`status` enum('draft','issued','paid','voided','refunded')
,`payment_method` varchar(100)
,`payment_ref` varchar(100)
,`subtotal` decimal(12,2)
,`total_discount` decimal(12,2)
,`total_tax` decimal(12,2)
,`grand_total` decimal(12,2)
,`amount_tendered` decimal(12,2)
,`change_amount` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_invoice_line_detail`
--
DROP TABLE IF EXISTS `v_invoice_line_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_invoice_line_detail`  AS SELECT `ii`.`invoice_id` AS `invoice_id`, `i`.`invoice_number` AS `invoice_number`, `i`.`invoice_date` AS `invoice_date`, `p`.`sku` AS `sku`, `ii`.`product_name` AS `product_name`, `ii`.`generic_name` AS `generic_name`, `ii`.`quantity` AS `quantity`, `ii`.`uom_code` AS `uom_code`, `ii`.`unit_price` AS `unit_price`, `tr`.`tax_code` AS `tax_code`, `ii`.`tax_rate_pct` AS `tax_rate_pct`, `ii`.`line_subtotal` AS `line_subtotal`, `ii`.`line_tax` AS `line_tax`, `ii`.`line_discount` AS `line_discount`, `ii`.`line_total` AS `line_total` FROM (((`invoice_items` `ii` join `invoices` `i` on(`i`.`id` = `ii`.`invoice_id`)) join `products` `p` on(`p`.`id` = `ii`.`product_id`)) left join `tax_rates` `tr` on(`tr`.`id` = `ii`.`tax_rate_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_invoice_summary`
--
DROP TABLE IF EXISTS `v_invoice_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_invoice_summary`  AS SELECT `i`.`id` AS `id`, `i`.`invoice_number` AS `invoice_number`, `i`.`invoice_date` AS `invoice_date`, ifnull(concat(`c`.`first_name`,' ',`c`.`last_name`),'Walk-in Customer') AS `customer_name`, `i`.`status` AS `status`, `pm`.`method_name` AS `payment_method`, `i`.`payment_ref` AS `payment_ref`, `i`.`subtotal` AS `subtotal`, `i`.`total_discount` AS `total_discount`, `i`.`total_tax` AS `total_tax`, `i`.`grand_total` AS `grand_total`, `i`.`amount_tendered` AS `amount_tendered`, `i`.`change_amount` AS `change_amount` FROM ((`invoices` `i` left join `customers` `c` on(`c`.`id` = `i`.`customer_id`)) left join `payment_methods` `pm` on(`pm`.`id` = `i`.`payment_method_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_audit_changed_at` (`changed_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `discount_types`
--
ALTER TABLE `discount_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discount_code` (`discount_code`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `idx_invoices_date` (`invoice_date`),
  ADD KEY `idx_invoices_status` (`status`),
  ADD KEY `idx_invoices_customer` (`customer_id`);

--
-- Indexes for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `discount_type_id` (`discount_type_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tax_rate_id` (`tax_rate_id`),
  ADD KEY `idx_items_invoice` (`invoice_id`),
  ADD KEY `idx_items_product` (`product_id`);

--
-- Indexes for table `invoice_sequence`
--
ALTER TABLE `invoice_sequence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_year_month` (`sequence_year`,`sequence_month`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `method_code` (`method_code`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `uom_id` (`uom_id`),
  ADD KEY `tax_rate_id` (`tax_rate_id`),
  ADD KEY `idx_products_sku` (`sku`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch_product` (`product_id`),
  ADD KEY `idx_batch_expiry` (`expiry_date`),
  ADD KEY `idx_batch_active` (`is_active`),
  ADD KEY `product_batches_ibfk_2` (`supplier_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `default_tax_id` (`default_tax_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tax_code` (`tax_code`);

--
-- Indexes for table `units_of_measure`
--
ALTER TABLE `units_of_measure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uom_code` (`uom_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `discount_types`
--
ALTER TABLE `discount_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoice_sequence`
--
ALTER TABLE `invoice_sequence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `units_of_measure`
--
ALTER TABLE `units_of_measure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Constraints for table `invoice_discounts`
--
ALTER TABLE `invoice_discounts`
  ADD CONSTRAINT `invoice_discounts_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_discounts_ibfk_2` FOREIGN KEY (`discount_type_id`) REFERENCES `discount_types` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `invoice_items_ibfk_3` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`);

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`uom_id`) REFERENCES `units_of_measure` (`id`),
  ADD CONSTRAINT `products_ibfk_4` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`);

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_batches_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`),
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`default_tax_id`) REFERENCES `tax_rates` (`id`);

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
