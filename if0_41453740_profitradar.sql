-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql207.infinityfree.com
-- Generation Time: May 10, 2026 at 12:56 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41453740_profitradar`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity` varchar(80) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `entity`, `entity_id`, `description`, `old_data`, `new_data`, `created_at`) VALUES
(1, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-10 05:50:21'),
(2, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-10 15:46:03'),
(3, 1, 'create', 'products', 1, 'Product created', NULL, '{\"code\":\"P0001\",\"name\":\"Hasmin Blue - 25 kg\",\"category\":\"Rice Wholesale\",\"category_id\":1,\"supplier_id\":1,\"location_id\":null,\"cost_price\":950,\"sell_price\":1000,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\"}', '2026-05-10 15:48:37'),
(4, 1, 'create', 'locations', 1, 'Location created', NULL, '{\"name\":\"Iloilo City\",\"description\":\"\"}', '2026-05-10 15:49:03'),
(5, 1, 'update', 'products', 1, 'Product updated', '{\"code\":\"P0001\",\"name\":\"Hasmin Blue - 25 kg\",\"category\":\"Rice Wholesale\",\"category_id\":1,\"supplier_id\":1,\"location_id\":null,\"cost_price\":\"950.00\",\"sell_price\":\"1000.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\",\"id\":1,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"0.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:48:37\",\"updated_at\":null,\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0001\",\"name\":\"Hasmin Blue - 25 kg\",\"category\":\"Rice Wholesale\",\"category_id\":1,\"supplier_id\":1,\"location_id\":1,\"cost_price\":950,\"sell_price\":1000,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\"}', '2026-05-10 15:49:09'),
(6, 1, 'create', 'products', 2, 'Product created', NULL, '{\"code\":\"P0002\",\"name\":\"Hasmin Blue\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":40,\"sell_price\":50,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\"}', '2026-05-10 15:50:30'),
(7, 1, 'update', 'products', 1, 'Product updated', '{\"code\":\"P0001\",\"name\":\"Hasmin Blue - 25 kg\",\"category\":\"Rice Wholesale\",\"category_id\":1,\"supplier_id\":1,\"location_id\":1,\"cost_price\":\"950.00\",\"sell_price\":\"1000.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\",\"id\":1,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"0.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:48:37\",\"updated_at\":\"2026-05-10 23:49:09\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0001\",\"name\":\"Hasmin Blue - 25 kg\",\"category\":\"Rice Wholesale\",\"category_id\":1,\"supplier_id\":1,\"location_id\":1,\"cost_price\":950,\"sell_price\":1000,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\"}', '2026-05-10 15:50:46'),
(8, 1, 'create', 'products', 3, 'Product created', NULL, '{\"code\":\"P0003\",\"name\":\"Nutri Chunks\",\"category\":\"Dog Food Retail\",\"category_id\":3,\"supplier_id\":2,\"location_id\":1,\"cost_price\":32,\"sell_price\":38,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\"}', '2026-05-10 15:52:27'),
(9, 1, 'create', 'products', 4, 'Product created', NULL, '{\"code\":\"P0004\",\"name\":\"Top Breed Adult\",\"category\":\"Dog Food Wholesale\",\"category_id\":4,\"supplier_id\":3,\"location_id\":1,\"cost_price\":1400,\"sell_price\":1500,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\"}', '2026-05-10 15:54:09'),
(10, 1, 'create', 'products', 5, 'Product created', NULL, '{\"code\":\"P0005\",\"name\":\"Top Breed Adult - 1 Kg\",\"category\":\"\",\"category_id\":3,\"supplier_id\":3,\"location_id\":1,\"cost_price\":80,\"sell_price\":85,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\"}', '2026-05-10 15:54:58'),
(11, 1, 'create', 'transactions', 1, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":100,\"unit_price\":950,\"expiry_date\":\"2027-05-10\"},{\"product_id\":2,\"qty\":100,\"unit_price\":40,\"expiry_date\":\"2027-05-10\"},{\"product_id\":3,\"qty\":100,\"unit_price\":32,\"expiry_date\":\"2027-05-10\"},{\"product_id\":4,\"qty\":100,\"unit_price\":1400,\"expiry_date\":\"2027-05-10\"},{\"product_id\":5,\"qty\":100,\"unit_price\":80,\"expiry_date\":\"2027-05-10\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-10 15:58:28'),
(12, 1, 'create', 'transactions', 2, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":5,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-10 15:59:56'),
(13, 1, 'create', 'transactions', 3, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":10,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":4,\"qty\":10,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-10 16:02:22'),
(14, 1, 'create', 'transactions', 4, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":15,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-10 16:02:33'),
(15, 1, 'update', 'products', 2, 'Product updated', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":\"40.00\",\"sell_price\":\"50.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\",\"id\":2,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"90.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:50:30\",\"updated_at\":\"2026-05-11 00:02:22\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue - 1kg\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":40,\"sell_price\":50,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\"}', '2026-05-10 16:03:12'),
(16, 1, 'update', 'products', 2, 'Product updated', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue - 1kg\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":\"40.00\",\"sell_price\":\"50.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\",\"id\":2,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"90.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:50:30\",\"updated_at\":\"2026-05-11 00:03:12\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue - 1 Kg\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":40,\"sell_price\":50,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\"}', '2026-05-10 16:03:26'),
(17, 1, 'update', 'products', 4, 'Product updated', '{\"code\":\"P0004\",\"name\":\"Top Breed Adult\",\"category\":\"Dog Food Wholesale\",\"category_id\":4,\"supplier_id\":3,\"location_id\":1,\"cost_price\":\"1400.00\",\"sell_price\":\"1500.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\",\"id\":4,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"90.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:54:09\",\"updated_at\":\"2026-05-11 00:02:22\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0004\",\"name\":\"Top Breed Adult - 25 kg\",\"category\":\"Dog Food Wholesale\",\"category_id\":4,\"supplier_id\":3,\"location_id\":1,\"cost_price\":1400,\"sell_price\":1500,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"sack\"}', '2026-05-10 16:03:39'),
(18, 1, 'update', 'products', 5, 'Product updated', '{\"code\":\"P0005\",\"name\":\"Top Breed Adult - 1 Kg\",\"category\":\"\",\"category_id\":3,\"supplier_id\":3,\"location_id\":1,\"cost_price\":\"80.00\",\"sell_price\":\"85.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\",\"id\":5,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"100.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:54:58\",\"updated_at\":\"2026-05-10 23:58:28\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0005\",\"name\":\"Top Breed Adult - 1 kg\",\"category\":\"\",\"category_id\":3,\"supplier_id\":3,\"location_id\":1,\"cost_price\":80,\"sell_price\":85,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\"}', '2026-05-10 16:03:47'),
(19, 1, 'update', 'products', 2, 'Product updated', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue - 1 Kg\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":\"40.00\",\"sell_price\":\"50.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\",\"id\":2,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"90.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:50:30\",\"updated_at\":\"2026-05-11 00:03:26\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0002\",\"name\":\"Hasmin Blue - 1 kg\",\"category\":\"Rice Retail\",\"category_id\":2,\"supplier_id\":1,\"location_id\":1,\"cost_price\":40,\"sell_price\":50,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"pc\"}', '2026-05-10 16:03:56'),
(20, 1, 'update', 'products', 3, 'Product updated', '{\"code\":\"P0003\",\"name\":\"Nutri Chunks\",\"category\":\"Dog Food Retail\",\"category_id\":3,\"supplier_id\":2,\"location_id\":1,\"cost_price\":\"32.00\",\"sell_price\":\"38.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\",\"id\":3,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"85.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:52:27\",\"updated_at\":\"2026-05-11 00:02:33\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0003\",\"name\":\"Nutri Chunks - 1 kg\",\"category\":\"Dog Food Retail\",\"category_id\":3,\"supplier_id\":2,\"location_id\":1,\"cost_price\":32,\"sell_price\":38,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\"}', '2026-05-10 16:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `user_id`) VALUES
(1, 'Rice Wholesale', NULL, '2026-05-10 15:48:37', 0),
(2, 'Rice Retail', NULL, '2026-05-10 15:50:30', 0),
(3, 'Dog Food Retail', NULL, '2026-05-10 15:52:27', 0),
(4, 'Dog Food Wholesale', NULL, '2026-05-10 15:54:09', 0);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `damaged_items`
--

CREATE TABLE `damaged_items` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `damaged_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `delivery_number` varchar(50) DEFAULT NULL,
  `transaction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `delivery_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivered_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_items`
--

CREATE TABLE `delivery_items` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_item_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('purchase','sale','return','adjustment','transfer','damage','reserve','release') NOT NULL,
  `quantity` int(11) NOT NULL,
  `ref_type` varchar(40) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock_after` decimal(12,3) DEFAULT NULL,
  `source` varchar(30) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `product_id`, `movement_type`, `quantity`, `ref_type`, `ref_id`, `note`, `created_by`, `created_at`, `stock_after`, `source`, `user_id`) VALUES
(0, 1, 'purchase', 100, 'transaction', 1, 'Supplier purchase', NULL, '2026-05-10 15:58:28', '100.000', 'Purchase', 0),
(0, 2, 'purchase', 100, 'transaction', 1, 'Supplier purchase', NULL, '2026-05-10 15:58:28', '100.000', 'Purchase', 0),
(0, 3, 'purchase', 100, 'transaction', 1, 'Supplier purchase', NULL, '2026-05-10 15:58:28', '100.000', 'Purchase', 0),
(0, 4, 'purchase', 100, 'transaction', 1, 'Supplier purchase', NULL, '2026-05-10 15:58:28', '100.000', 'Purchase', 0),
(0, 5, 'purchase', 100, 'transaction', 1, 'Supplier purchase', NULL, '2026-05-10 15:58:28', '100.000', 'Purchase', 0),
(0, 1, 'sale', -5, 'transaction', 2, 'POS sale', NULL, '2026-05-10 15:59:56', '95.000', 'POS', 0),
(0, 2, 'sale', -10, 'transaction', 3, 'POS sale', NULL, '2026-05-10 16:02:22', '90.000', 'POS', 0),
(0, 4, 'sale', -10, 'transaction', 3, 'POS sale', NULL, '2026-05-10 16:02:22', '90.000', 'POS', 0),
(0, 3, 'sale', -15, 'transaction', 4, 'POS sale', NULL, '2026-05-10 16:02:33', '85.000', 'POS', 0);

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Iloilo City', '', '2026-05-10 15:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `code` varchar(64) NOT NULL,
  `name` varchar(200) NOT NULL,
  `product_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `category` varchar(120) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT 0.00,
  `sell_price` decimal(12,2) DEFAULT 0.00,
  `promo_discount_type` enum('percent','amount') DEFAULT NULL,
  `promo_discount_value` decimal(10,2) DEFAULT NULL,
  `sold_by` enum('each','weight') NOT NULL DEFAULT 'each',
  `unit` varchar(20) NOT NULL DEFAULT 'pc',
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `barcode` varchar(128) DEFAULT NULL,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `stock_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `reserved_qty` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `retail_stock` decimal(12,3) NOT NULL DEFAULT 0.000,
  `wholesale_stock` decimal(12,3) NOT NULL DEFAULT 0.000,
  `sale_type` enum('retail','wholesale') NOT NULL DEFAULT 'retail',
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `product_type`, `category`, `category_id`, `location_id`, `supplier_id`, `location`, `cost_price`, `sell_price`, `promo_discount_type`, `promo_discount_value`, `sold_by`, `unit`, `wholesale_price`, `barcode`, `low_stock_threshold`, `stock_qty`, `reserved_qty`, `is_active`, `created_at`, `updated_at`, `retail_stock`, `wholesale_stock`, `sale_type`, `user_id`) VALUES
(1, 'P0001', 'Hasmin Blue - 25 kg', 'retail', 'Rice Wholesale', 1, 1, 1, NULL, '950.00', '1000.00', NULL, NULL, 'each', 'sack', '0.00', '', NULL, '95.000', 0, 1, '2026-05-10 15:48:37', '2026-05-10 15:59:56', '0.000', '0.000', 'retail', 1),
(2, 'P0002', 'Hasmin Blue - 1 kg', 'retail', 'Rice Retail', 2, 1, 1, NULL, '40.00', '50.00', NULL, NULL, 'each', 'pc', '0.00', '', NULL, '90.000', 0, 1, '2026-05-10 15:50:30', '2026-05-10 16:03:56', '0.000', '0.000', 'retail', 1),
(3, 'P0003', 'Nutri Chunks - 1 kg', 'retail', 'Dog Food Retail', 3, 1, 2, NULL, '32.00', '38.00', NULL, NULL, 'each', 'bag', '0.00', '', NULL, '85.000', 0, 1, '2026-05-10 15:52:27', '2026-05-10 16:04:12', '0.000', '0.000', 'retail', 1),
(4, 'P0004', 'Top Breed Adult - 25 kg', 'retail', 'Dog Food Wholesale', 4, 1, 3, NULL, '1400.00', '1500.00', NULL, NULL, 'each', 'sack', '0.00', '', NULL, '90.000', 0, 1, '2026-05-10 15:54:09', '2026-05-10 16:03:39', '0.000', '0.000', 'retail', 1),
(5, 'P0005', 'Top Breed Adult - 1 kg', 'retail', '', 3, 1, 3, NULL, '80.00', '85.00', NULL, NULL, 'each', 'bag', '0.00', '', NULL, '100.000', 0, 1, '2026-05-10 15:54:58', '2026-05-10 16:03:47', '0.000', '0.000', 'retail', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_item_id` int(11) DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `remaining_qty` decimal(12,3) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','damaged') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `product_id`, `transaction_item_id`, `batch_no`, `quantity`, `remaining_qty`, `cost_price`, `expiry_date`, `status`, `created_at`) VALUES
(0, 1, 0, 'P0001-20260510', '100.000', '60.000', '950.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 2, 0, 'P0002-20260510', '100.000', '60.000', '40.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 3, 0, 'P0003-20260510', '100.000', '60.000', '32.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 4, 0, 'P0004-20260510', '100.000', '60.000', '1400.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 5, 0, 'P0005-20260510', '100.000', '60.000', '80.00', '2027-05-10', 'active', '2026-05-10 15:58:28');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('draft','ordered','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `received_qty` decimal(12,3) NOT NULL DEFAULT 0.000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `reserved_by` varchar(100) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('reserved','completed','cancelled') DEFAULT 'reserved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_name` varchar(120) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `contact` varchar(160) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact`, `phone`, `email`, `address`, `user_id`) VALUES
(1, 'Alot Rice Center', 'John Pillena', '099686553160', 'Atcenter@gmail.com', NULL, 1),
(2, 'Ajara Marketing', 'Jojo Basbano', '09394127891', 'ajaramarketing@yahoo.com', NULL, 1),
(3, 'Falcor Marketing', 'Crista Panillo', '09171689997', 'falcormarketing@gmail.com', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `type` enum('purchase','sale','refund','return','transfer_in','transfer_out','adjustment') NOT NULL,
  `ref_no` varchar(64) DEFAULT NULL,
  `date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sale_mode` enum('retail','wholesale') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `extra_discount_type` enum('percent','amount') DEFAULT NULL,
  `extra_discount_value` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `refers_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `type`, `ref_no`, `date`, `user_id`, `supplier_id`, `customer_id`, `sale_mode`, `notes`, `extra_discount_type`, `extra_discount_value`, `created_at`, `refers_to`) VALUES
(1, 'purchase', 'P_20260510_235828', '2026-05-10', 1, NULL, NULL, NULL, '', NULL, NULL, '2026-05-10 15:58:28', NULL),
(2, 'sale', 'S_20260510_235956', '2026-05-10', 1, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-10 15:59:56', NULL),
(3, 'sale', 'S_20260511_000222', '2026-05-10', 1, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-10 16:02:22', NULL),
(4, 'sale', 'S_20260511_000233', '2026-05-11', 1, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-10 16:02:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cost_at_sale` decimal(10,2) DEFAULT NULL,
  `price_tier` enum('retail','wholesale') DEFAULT NULL,
  `discount_type` enum('percent','amount') DEFAULT NULL,
  `discount_value` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `qty`, `unit_price`, `cost_at_sale`, `price_tier`, `discount_type`, `discount_value`) VALUES
(0, 1, 1, '100.000', '950.00', NULL, NULL, NULL, NULL),
(0, 1, 2, '100.000', '40.00', NULL, NULL, NULL, NULL),
(0, 1, 3, '100.000', '32.00', NULL, NULL, NULL, NULL),
(0, 1, 4, '100.000', '1400.00', NULL, NULL, NULL, NULL),
(0, 1, 5, '100.000', '80.00', NULL, NULL, NULL, NULL),
(0, 2, 1, '5.000', '1000.00', '950.00', 'retail', NULL, NULL),
(0, 3, 2, '10.000', '50.00', '40.00', 'retail', NULL, NULL),
(0, 3, 4, '10.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(0, 4, 3, '15.000', '38.00', '32.00', 'retail', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff','auditor') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`, `is_active`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$m5ESQVckWq6JJW1ruYgtBeuIEsMewOUZBJOqFvzLmfZrLpibK8Ooa', 'admin', '2025-08-28 07:25:26', 1),
(2, 'Staff User', 'staff@example.com', '$2y$10$kYTgvtefy1cxg6FZ4cGyJOcp11ndb5LNv5PBmidz9NXhYXfTCZAcu', 'staff', '2025-08-29 15:43:52', 1),
(3, 'Auditor User', 'auditor@example.com', '$2y$10$B8dR7TlJsxgJqUgnN.2wt.hYr65a31yGh6G3gvrXUV5pnRe1vWbRi', 'auditor', '2025-08-29 15:43:52', 1),
(4, 'Admin2', 'admin@profitradar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9sZ0j1Pp7hR9...', 'admin', '2026-04-10 03:07:29', 1),
(5, 'Admin4', 'admin4@profitradar.com', '$2y$10$MTXhOyGjEoaaJ6GwHlQNYusBfqWGWaxwUzgR5j9xYoH5Fj6JsUan2', 'admin', '2026-04-10 03:09:53', 1),
(6, 'User', 'admin@store.com', '$2y$10$L5dUQRiyxvY6LJNy0vk0M.1qzCVhX0puZ6KvMCgJi13hHOBbS5g1y', 'admin', '2026-04-10 03:14:47', 1),
(7, 'example', 'example@gmail.com', '$2y$10$B8NTzJyuwXUl6wQxI6hkmeNH7XklUOg4co9NxFmKTMJ4qM1bQTMf2', 'staff', '2026-04-12 14:50:36', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_user` (`user_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
