-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql207.infinityfree.com
-- Generation Time: May 18, 2026 at 11:56 PM
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
(20, 1, 'update', 'products', 3, 'Product updated', '{\"code\":\"P0003\",\"name\":\"Nutri Chunks\",\"category\":\"Dog Food Retail\",\"category_id\":3,\"supplier_id\":2,\"location_id\":1,\"cost_price\":\"32.00\",\"sell_price\":\"38.00\",\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\",\"id\":3,\"product_type\":\"retail\",\"location\":null,\"promo_discount_type\":null,\"promo_discount_value\":null,\"wholesale_price\":\"0.00\",\"stock_qty\":\"85.000\",\"reserved_qty\":0,\"is_active\":1,\"created_at\":\"2026-05-10 23:52:27\",\"updated_at\":\"2026-05-11 00:02:33\",\"retail_stock\":\"0.000\",\"wholesale_stock\":\"0.000\",\"sale_type\":\"retail\",\"user_id\":1}', '{\"code\":\"P0003\",\"name\":\"Nutri Chunks - 1 kg\",\"category\":\"Dog Food Retail\",\"category_id\":3,\"supplier_id\":2,\"location_id\":1,\"cost_price\":32,\"sell_price\":38,\"barcode\":\"\",\"low_stock_threshold\":null,\"sold_by\":\"each\",\"unit\":\"bag\"}', '2026-05-10 16:04:12'),
(21, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-10 17:05:07'),
(22, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-10 17:05:21'),
(23, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-13 12:00:18'),
(24, 6, 'create', 'transactions', 5, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":1,\"unit_price\":40,\"expiry_date\":\"2026-05-13\"},{\"product_id\":2,\"qty\":1,\"unit_price\":40,\"expiry_date\":\"2026-05-13\"},{\"product_id\":2,\"qty\":1,\"unit_price\":40,\"expiry_date\":\"2026-05-13\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-13 12:01:38'),
(25, 6, 'create', 'transactions', 6, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":1,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-13 12:04:52'),
(26, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-13 12:10:47'),
(27, 6, 'create', 'transactions', 7, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":15,\"unit_price\":950,\"expiry_date\":\"2027-05-13\"},{\"product_id\":4,\"qty\":51,\"unit_price\":1400,\"expiry_date\":\"2027-05-13\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-13 12:12:11'),
(28, 6, 'create', 'transactions', 8, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":2,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":\"amount\",\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-13 12:19:23'),
(29, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-13 17:31:06'),
(30, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-14 10:16:30'),
(31, 6, 'create', 'transactions', 9, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":3,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-14 10:17:17'),
(32, 6, 'create', 'transactions', 10, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":2,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-14 10:18:33'),
(33, 6, 'create', 'transactions', 11, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":1,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-14 23:59:20'),
(34, 6, 'create', 'transactions', 12, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":1,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-14 23:59:34'),
(35, 6, 'create', 'transactions', 13, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":2,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-15 00:06:18'),
(36, 6, 'logout', 'users', 6, 'User logged out', NULL, NULL, '2026-05-15 08:23:49'),
(37, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-15 08:24:08'),
(38, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-15 11:39:52'),
(39, 6, 'create', 'transactions', 14, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":3,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-15 11:40:54'),
(40, 6, 'create', 'transactions', 15, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":2,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-15 11:42:27'),
(41, 6, 'create', 'transactions', 16, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":3,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 02:21:37'),
(42, 6, 'create', 'transactions', 17, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":6,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":3,\"qty\":1,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 02:22:47'),
(43, 6, 'create', 'transactions', 18, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":6,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 02:24:18'),
(44, 6, 'create', 'transactions', 19, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":1,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 02:24:50'),
(45, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-16 10:40:53'),
(46, 6, 'create', 'transactions', 20, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":6,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 10:41:32'),
(47, 6, 'create', 'transactions', 21, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":5,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 10:42:32'),
(48, 6, 'create', 'transactions', 22, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":4,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":5,\"qty\":1,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 10:43:05'),
(49, 6, 'create', 'transactions', 23, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":4,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-16 10:43:29'),
(50, 6, 'logout', 'users', 6, 'User logged out', NULL, NULL, '2026-05-16 10:44:01'),
(51, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-17 01:50:51'),
(52, 6, 'create', 'transactions', 24, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":3,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 01:51:28'),
(53, 6, 'create', 'transactions', 25, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":4,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 01:52:06'),
(54, 6, 'create', 'transactions', 26, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":2,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 01:52:43'),
(55, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-17 08:44:20'),
(56, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-17 08:50:42'),
(57, 2, 'login', 'users', 2, 'User logged in', NULL, NULL, '2026-05-17 08:50:51'),
(58, 2, 'logout', 'users', 2, 'User logged out', NULL, NULL, '2026-05-17 08:50:58'),
(59, 2, 'login', 'users', 2, 'User logged in', NULL, NULL, '2026-05-17 12:56:38'),
(60, 2, 'create', 'transactions', 27, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":10,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":1,\"qty\":3,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":3,\"qty\":8,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":4,\"qty\":5,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":5,\"qty\":12,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 13:01:57'),
(61, 2, 'create', 'transactions', 28, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":1,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":1,\"qty\":1,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":3,\"qty\":1,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":5,\"qty\":1,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":4,\"qty\":1,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:04:44'),
(62, 2, 'create', 'transactions', 29, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":2,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:46:04'),
(63, 2, 'create', 'transactions', 30, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":10,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:49:22'),
(64, 2, 'create', 'transactions', 31, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":2,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:49:31'),
(65, 2, 'create', 'transactions', 32, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":10,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:52:11'),
(66, 2, 'create', 'transactions', 33, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":2,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 14:58:29'),
(67, 2, 'create', 'transactions', 34, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":1,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:03:11'),
(68, 2, 'create', 'transactions', 35, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":3,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:03:56'),
(69, 2, 'create', 'transactions', 36, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":4,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:10:07'),
(70, 2, 'create', 'transactions', 37, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":1,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:16:57'),
(71, 2, 'create', 'transactions', 38, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":5,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:17:39'),
(72, 2, 'create', 'transactions', 39, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":2,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:19:55'),
(73, 2, 'create', 'transactions', 40, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":2,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:22:12'),
(74, 2, 'create', 'transactions', 41, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":3,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:24:28'),
(75, 2, 'create', 'transactions', 42, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":2,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:31:59'),
(76, 2, 'create', 'transactions', 43, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":5,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:36:55'),
(77, 2, 'create', 'transactions', 44, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":3,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-17 15:47:20'),
(78, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-18 03:04:44'),
(79, 6, 'create', 'transactions', 45, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":5,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 03:05:10'),
(80, 6, 'create', 'transactions', 46, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":5,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":4,\"qty\":2,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 03:05:45'),
(81, 6, 'create', 'transactions', 47, 'sale recorded', NULL, '{\"items\":[{\"product_id\":3,\"qty\":2,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 03:06:20'),
(82, 6, 'create', 'transactions', 48, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":6,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 03:07:18'),
(83, 6, 'create', 'transactions', 49, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":201,\"unit_price\":1400,\"expiry_date\":\"2027-05-18\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-18 03:10:49'),
(84, 6, 'create', 'transactions', 50, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":15,\"unit_price\":950,\"expiry_date\":\"2027-05-18\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-18 03:11:31'),
(85, 6, 'create', 'transactions', 51, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":6,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 03:13:01'),
(86, 6, 'create', 'transactions', 52, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":3,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 05:11:35'),
(87, 6, 'create', 'transactions', 53, 'sale recorded', NULL, '{\"items\":[{\"product_id\":1,\"qty\":5,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 05:12:01'),
(88, 6, 'create', 'transactions', 54, 'sale recorded', NULL, '{\"items\":[{\"product_id\":5,\"qty\":5,\"unit_price\":85,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 05:12:29'),
(89, 6, 'create', 'transactions', 55, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":3,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"},{\"product_id\":1,\"qty\":2,\"unit_price\":1000,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 10:57:29'),
(90, 6, 'create', 'transactions', 56, 'sale recorded', NULL, '{\"items\":[{\"product_id\":4,\"qty\":3,\"unit_price\":1500,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 10:58:24'),
(91, 6, 'create', 'transactions', 57, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":5,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null,\"price_tier\":\"retail\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 10:58:51'),
(92, 2, 'login', 'users', 2, 'User logged in', NULL, NULL, '2026-05-18 13:33:13'),
(93, 2, 'logout', 'users', 2, 'User logged out', NULL, NULL, '2026-05-18 13:33:22'),
(94, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-18 13:33:27'),
(95, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-18 13:37:47'),
(96, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-18 13:44:07'),
(97, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-18 13:44:09'),
(98, 10, 'login', 'users', 10, 'User logged in', NULL, NULL, '2026-05-18 13:47:38'),
(99, 10, 'logout', 'users', 10, 'User logged out', NULL, NULL, '2026-05-18 13:47:41'),
(100, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-18 13:57:44'),
(101, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":50,\"delta\":1,\"after\":51}]}', '2026-05-18 15:15:44'),
(102, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":51,\"delta\":1,\"after\":52}]}', '2026-05-18 15:19:50'),
(103, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":52,\"delta\":1,\"after\":53}]}', '2026-05-18 15:26:42'),
(104, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":53,\"delta\":2,\"after\":55}]}', '2026-05-18 15:29:05'),
(105, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":55,\"delta\":5,\"after\":60}]}', '2026-05-18 15:46:02'),
(106, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":3,\"before\":49,\"delta\":5,\"after\":54}]}', '2026-05-18 15:46:36'),
(107, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":1,\"before\":87,\"delta\":3,\"after\":90}]}', '2026-05-18 15:50:59'),
(108, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":3,\"before\":54,\"delta\":1,\"after\":55}]}', '2026-05-18 15:57:22'),
(109, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":60,\"delta\":1,\"after\":61}]}', '2026-05-18 15:57:59'),
(110, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":61,\"delta\":1,\"after\":62}]}', '2026-05-18 16:03:57'),
(111, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":62,\"delta\":1,\"after\":63}]}', '2026-05-18 16:04:20'),
(112, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":63,\"delta\":1,\"after\":64}]}', '2026-05-18 16:07:08'),
(113, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":64,\"delta\":1,\"after\":65}]}', '2026-05-18 16:12:20'),
(114, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":65,\"delta\":1,\"after\":66}]}', '2026-05-18 16:18:25'),
(115, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":66,\"delta\":1,\"after\":67}]}', '2026-05-18 16:24:11'),
(116, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":67,\"delta\":1,\"after\":68}]}', '2026-05-18 16:27:48'),
(117, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":68,\"delta\":2,\"after\":70}]}', '2026-05-18 16:31:14'),
(118, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":70,\"delta\":1,\"after\":71}]}', '2026-05-18 16:33:23'),
(119, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":71,\"delta\":1,\"after\":72}]}', '2026-05-18 16:40:20'),
(120, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":72,\"delta\":1,\"after\":73}]}', '2026-05-18 16:43:35'),
(121, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":73,\"delta\":1,\"after\":74}]}', '2026-05-18 16:47:27'),
(122, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":74,\"delta\":1,\"after\":75}]}', '2026-05-18 16:48:57'),
(123, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":75,\"delta\":1,\"after\":76}]}', '2026-05-18 16:52:20'),
(124, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":76,\"delta\":1,\"after\":77}]}', '2026-05-18 16:53:07'),
(125, 1, 'stock_adjust', '', NULL, 'Other', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Other\",\"items\":[{\"product_id\":2,\"before\":77,\"delta\":1,\"after\":78}]}', '2026-05-18 16:53:23'),
(126, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":78,\"delta\":1,\"after\":79}]}', '2026-05-18 16:54:39'),
(127, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":79,\"delta\":1,\"after\":80}]}', '2026-05-18 16:56:25'),
(128, 1, 'stock_adjust', '', NULL, 'Recount Correction', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Recount Correction\",\"items\":[{\"product_id\":2,\"before\":80,\"delta\":1,\"after\":81}]}', '2026-05-18 16:58:57'),
(129, 1, 'stock_adjust', '', NULL, 'Other', NULL, '{\"type\":\"stock_adjust\",\"reason\":\"Other\",\"items\":[{\"product_id\":2,\"before\":81,\"delta\":1,\"after\":82}]}', '2026-05-18 16:59:25'),
(130, 1, 'create', 'transactions', 93, 'purchase recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":1,\"unit_price\":40,\"expiry_date\":\"2027-05-19\"}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":null}', '2026-05-18 17:01:29'),
(131, 1, 'create', 'transactions', 94, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":3,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-18 17:03:35'),
(132, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-18 17:11:12'),
(133, 1, 'login', 'users', 1, 'User logged in', NULL, NULL, '2026-05-18 17:11:18'),
(134, 1, 'logout', 'users', 1, 'User logged out', NULL, NULL, '2026-05-18 17:11:20'),
(135, 2, 'login', 'users', 2, 'User logged in', NULL, NULL, '2026-05-18 17:11:34'),
(136, 2, 'logout', 'users', 2, 'User logged out', NULL, NULL, '2026-05-18 17:11:41'),
(137, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-19 03:53:42'),
(138, 6, 'login', 'users', 6, 'User logged in', NULL, NULL, '2026-05-19 03:54:09'),
(139, 6, 'create', 'transactions', 95, 'sale recorded', NULL, '{\"items\":[{\"product_id\":2,\"qty\":7,\"unit_price\":50,\"discount_type\":\"\",\"discount_value\":null},{\"product_id\":3,\"qty\":2,\"unit_price\":38,\"discount_type\":\"\",\"discount_value\":null}],\"extra_discount_type\":null,\"extra_discount_value\":null,\"sale_mode\":\"retail\"}', '2026-05-19 03:55:38');

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
  `status` enum('partial','delivered','cancelled') DEFAULT 'partial',
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivered_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `delivery_number`, `transaction_id`, `customer_id`, `delivery_date`, `status`, `tracking_number`, `delivered_by`, `notes`, `created_at`) VALUES
(23, 'DEL-20260517-234756', 44, 1, '2026-05-17 23:47:55', 'delivered', NULL, NULL, NULL, '2026-05-17 23:47:55');

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

--
-- Dumping data for table `delivery_items`
--

INSERT INTO `delivery_items` (`id`, `delivery_id`, `product_id`, `quantity`, `transaction_item_id`) VALUES
(0, 12, 1, 2, 54),
(0, 13, 1, 1, 55),
(0, 14, 3, 3, 56),
(0, 15, 3, 2, 56),
(0, 16, 3, 1, 57),
(0, 17, 3, 1, 58),
(0, 18, 5, 1, 59),
(0, 19, 5, 1, 60),
(0, 20, 5, 1, 60),
(0, 21, 2, 3, 61),
(0, 22, 2, 2, 61),
(0, 23, 3, 1, 62),
(0, 23, 3, 2, 62);

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
(0, 3, 'sale', -15, 'transaction', 4, 'POS sale', NULL, '2026-05-10 16:02:33', '85.000', 'POS', 0),
(0, 2, 'purchase', 1, 'transaction', 5, 'Supplier purchase', NULL, '2026-05-13 12:01:38', '91.000', 'Purchase', 0),
(0, 2, 'purchase', 1, 'transaction', 5, 'Supplier purchase', NULL, '2026-05-13 12:01:38', '92.000', 'Purchase', 0),
(0, 2, 'purchase', 1, 'transaction', 5, 'Supplier purchase', NULL, '2026-05-13 12:01:38', '93.000', 'Purchase', 0),
(0, 2, 'sale', -1, 'transaction', 6, 'POS sale', NULL, '2026-05-13 12:04:52', '92.000', 'POS', 0),
(0, 1, 'purchase', 15, 'transaction', 7, 'Supplier purchase', NULL, '2026-05-13 12:12:11', '110.000', 'Purchase', 0),
(0, 4, 'purchase', 51, 'transaction', 7, 'Supplier purchase', NULL, '2026-05-13 12:12:11', '141.000', 'Purchase', 0),
(0, 2, 'sale', -2, 'transaction', 8, 'POS sale', NULL, '2026-05-13 12:19:23', '90.000', 'POS', 0),
(0, 2, 'sale', -3, 'transaction', 9, 'POS sale', NULL, '2026-05-14 10:17:17', '87.000', 'POS', 0),
(0, 5, 'sale', -2, 'transaction', 10, 'POS sale', NULL, '2026-05-14 10:18:33', '98.000', 'POS', 0),
(0, 1, 'sale', -1, 'transaction', 11, 'POS sale', NULL, '2026-05-14 23:59:20', '109.000', 'POS', 0),
(0, 4, 'sale', -1, 'transaction', 12, 'POS sale', NULL, '2026-05-14 23:59:34', '140.000', 'POS', 0),
(0, 1, 'sale', -2, 'transaction', 13, 'POS sale', NULL, '2026-05-15 00:06:18', '107.000', 'POS', 0),
(0, 2, 'sale', -3, 'transaction', 14, 'POS sale', NULL, '2026-05-15 11:40:54', '84.000', 'POS', 0),
(0, 5, 'sale', -2, 'transaction', 15, 'POS sale', NULL, '2026-05-15 11:42:27', '96.000', 'POS', 0),
(0, 2, 'sale', -3, 'transaction', 16, 'POS sale', NULL, '2026-05-16 02:21:37', '81.000', 'POS', 0),
(0, 5, 'sale', -6, 'transaction', 17, 'POS sale', NULL, '2026-05-16 02:22:47', '90.000', 'POS', 0),
(0, 3, 'sale', -1, 'transaction', 17, 'POS sale', NULL, '2026-05-16 02:22:47', '84.000', 'POS', 0),
(0, 1, 'sale', -6, 'transaction', 18, 'POS sale', NULL, '2026-05-16 02:24:18', '101.000', 'POS', 0),
(0, 4, 'sale', -1, 'transaction', 19, 'POS sale', NULL, '2026-05-16 02:24:50', '139.000', 'POS', 0),
(0, 1, 'sale', -6, 'transaction', 20, 'POS sale', NULL, '2026-05-16 10:41:32', '95.000', 'POS', 0),
(0, 4, 'sale', -5, 'transaction', 21, 'POS sale', NULL, '2026-05-16 10:42:32', '134.000', 'POS', 0),
(0, 3, 'sale', -4, 'transaction', 22, 'POS sale', NULL, '2026-05-16 10:43:05', '80.000', 'POS', 0),
(0, 5, 'sale', -1, 'transaction', 22, 'POS sale', NULL, '2026-05-16 10:43:05', '89.000', 'POS', 0),
(0, 2, 'sale', -4, 'transaction', 23, 'POS sale', NULL, '2026-05-16 10:43:29', '77.000', 'POS', 0),
(0, 3, 'sale', -3, 'transaction', 24, 'POS sale', NULL, '2026-05-17 01:51:28', '77.000', 'POS', 0),
(0, 2, 'sale', -4, 'transaction', 25, 'POS sale', NULL, '2026-05-17 01:52:06', '73.000', 'POS', 0),
(0, 5, 'sale', -2, 'transaction', 26, 'POS sale', NULL, '2026-05-17 01:52:43', '87.000', 'POS', 0),
(0, 2, 'sale', -10, 'transaction', 27, 'POS sale', NULL, '2026-05-17 13:01:57', '63.000', 'POS', 0),
(0, 1, 'sale', -3, 'transaction', 27, 'POS sale', NULL, '2026-05-17 13:01:57', '92.000', 'POS', 0),
(0, 3, 'sale', -8, 'transaction', 27, 'POS sale', NULL, '2026-05-17 13:01:57', '69.000', 'POS', 0),
(0, 4, 'sale', -5, 'transaction', 27, 'POS sale', NULL, '2026-05-17 13:01:57', '129.000', 'POS', 0),
(0, 5, 'sale', -12, 'transaction', 27, 'POS sale', NULL, '2026-05-17 13:01:57', '75.000', 'POS', 0),
(0, 2, 'sale', -1, 'transaction', 28, 'POS sale', NULL, '2026-05-17 14:04:44', '62.000', 'POS', 0),
(0, 1, 'sale', -1, 'transaction', 28, 'POS sale', NULL, '2026-05-17 14:04:44', '91.000', 'POS', 0),
(0, 3, 'sale', -1, 'transaction', 28, 'POS sale', NULL, '2026-05-17 14:04:44', '68.000', 'POS', 0),
(0, 5, 'sale', -1, 'transaction', 28, 'POS sale', NULL, '2026-05-17 14:04:44', '74.000', 'POS', 0),
(0, 4, 'sale', -1, 'transaction', 28, 'POS sale', NULL, '2026-05-17 14:04:44', '128.000', 'POS', 0),
(0, 1, 'sale', -2, 'transaction', 29, 'POS sale', NULL, '2026-05-17 14:46:04', '89.000', 'POS', 0),
(0, 4, 'sale', -10, 'transaction', 30, 'POS sale', NULL, '2026-05-17 14:49:22', '118.000', 'POS', 0),
(0, 4, 'sale', -2, 'transaction', 31, 'POS sale', NULL, '2026-05-17 14:49:31', '116.000', 'POS', 0),
(0, 4, 'sale', -10, 'transaction', 32, 'POS sale', NULL, '2026-05-17 14:52:11', '106.000', 'POS', 0),
(0, 2, 'sale', -2, 'transaction', 33, 'POS sale', NULL, '2026-05-17 14:58:29', '60.000', 'POS', 0),
(0, 5, 'sale', -1, 'transaction', 34, 'POS sale', NULL, '2026-05-17 15:03:11', '73.000', 'POS', 0),
(0, 5, 'sale', -3, 'transaction', 35, 'POS sale', NULL, '2026-05-17 15:03:56', '70.000', 'POS', 0),
(0, 1, 'sale', -4, 'transaction', 36, 'POS sale', NULL, '2026-05-17 15:10:07', '85.000', 'POS', 0),
(0, 1, 'sale', -1, 'transaction', 37, 'POS sale', NULL, '2026-05-17 15:16:57', '84.000', 'POS', 0),
(0, 3, 'sale', -5, 'transaction', 38, 'POS sale', NULL, '2026-05-17 15:17:39', '63.000', 'POS', 0),
(0, 3, 'sale', -2, 'transaction', 39, 'POS sale', NULL, '2026-05-17 15:19:55', '61.000', 'POS', 0),
(0, 3, 'sale', -2, 'transaction', 40, 'POS sale', NULL, '2026-05-17 15:22:12', '59.000', 'POS', 0),
(0, 5, 'sale', -3, 'transaction', 41, 'POS sale', NULL, '2026-05-17 15:24:28', '67.000', 'POS', 0),
(0, 5, 'sale', -2, 'transaction', 42, 'POS sale', NULL, '2026-05-17 15:31:59', '65.000', 'POS', 0),
(0, 2, 'sale', -5, 'transaction', 43, 'POS sale', NULL, '2026-05-17 15:36:55', '55.000', 'POS', 0),
(0, 3, 'sale', -3, 'transaction', 44, 'POS sale', NULL, '2026-05-17 15:47:20', '56.000', 'POS', 0),
(0, 1, 'sale', -5, 'transaction', 45, 'POS sale', NULL, '2026-05-18 03:05:10', '79.000', 'POS', 0),
(0, 3, 'sale', -5, 'transaction', 46, 'POS sale', NULL, '2026-05-18 03:05:45', '51.000', 'POS', 0),
(0, 4, 'sale', -2, 'transaction', 46, 'POS sale', NULL, '2026-05-18 03:05:45', '104.000', 'POS', 0),
(0, 3, 'sale', -2, 'transaction', 47, 'POS sale', NULL, '2026-05-18 03:06:20', '49.000', 'POS', 0),
(0, 2, 'sale', -6, 'transaction', 48, 'POS sale', NULL, '2026-05-18 03:07:18', '49.000', 'POS', 0),
(0, 4, 'purchase', 201, 'transaction', 49, 'Supplier purchase', NULL, '2026-05-18 03:10:49', '305.000', 'Purchase', 0),
(0, 1, 'purchase', 15, 'transaction', 50, 'Supplier purchase', NULL, '2026-05-18 03:11:31', '94.000', 'Purchase', 0),
(0, 4, 'sale', -6, 'transaction', 51, 'POS sale', NULL, '2026-05-18 03:13:01', '299.000', 'POS', 0),
(0, 4, 'sale', -3, 'transaction', 52, 'POS sale', NULL, '2026-05-18 05:11:35', '296.000', 'POS', 0),
(0, 1, 'sale', -5, 'transaction', 53, 'POS sale', NULL, '2026-05-18 05:12:01', '89.000', 'POS', 0),
(0, 5, 'sale', -5, 'transaction', 54, 'POS sale', NULL, '2026-05-18 05:12:29', '60.000', 'POS', 0),
(0, 4, 'sale', -3, 'transaction', 55, 'POS sale', NULL, '2026-05-18 10:57:29', '293.000', 'POS', 0),
(0, 1, 'sale', -2, 'transaction', 55, 'POS sale', NULL, '2026-05-18 10:57:29', '87.000', 'POS', 0),
(0, 4, 'sale', -3, 'transaction', 56, 'POS sale', NULL, '2026-05-18 10:58:24', '290.000', 'POS', 0),
(0, 2, 'sale', -5, 'transaction', 57, 'POS sale', NULL, '2026-05-18 10:58:51', '44.000', 'POS', 0),
(0, 2, 'purchase', 1, 'transaction', 93, 'Supplier purchase', NULL, '2026-05-18 17:01:29', '83.000', 'Purchase', 0),
(0, 2, 'sale', -3, 'transaction', 94, 'POS sale', NULL, '2026-05-18 17:03:35', '80.000', 'POS', 0),
(0, 2, 'sale', -7, 'transaction', 95, 'POS sale', NULL, '2026-05-19 03:55:38', '73.000', 'POS', 0),
(0, 3, 'sale', -2, 'transaction', 95, 'POS sale', NULL, '2026-05-19 03:55:38', '53.000', 'POS', 0);

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
(1, 'P0001', 'Hasmin Blue - 25 kg', 'retail', 'Rice Wholesale', 1, 1, 1, NULL, '950.00', '1000.00', NULL, NULL, 'each', 'sack', '0.00', '', NULL, '90.000', 0, 1, '2026-05-10 15:48:37', '2026-05-18 10:57:29', '0.000', '0.000', 'retail', 1),
(2, 'P0002', 'Hasmin Blue - 1 kg', 'retail', 'Rice Retail', 2, 1, 1, NULL, '40.00', '50.00', NULL, NULL, 'each', 'pc', '0.00', '', NULL, '73.000', 0, 1, '2026-05-10 15:50:30', '2026-05-19 03:55:38', '0.000', '0.000', 'retail', 1),
(3, 'P0003', 'Nutri Chunks - 1 kg', 'retail', 'Dog Food Retail', 3, 1, 2, NULL, '32.00', '38.00', NULL, NULL, 'each', 'bag', '0.00', '', NULL, '53.000', 0, 1, '2026-05-10 15:52:27', '2026-05-19 03:55:38', '0.000', '0.000', 'retail', 1),
(4, 'P0004', 'Top Breed Adult - 25 kg', 'retail', 'Dog Food Wholesale', 4, 1, 3, NULL, '1400.00', '1500.00', NULL, NULL, 'each', 'sack', '0.00', '', NULL, '290.000', 0, 1, '2026-05-10 15:54:09', '2026-05-18 10:58:24', '0.000', '0.000', 'retail', 1),
(5, 'P0005', 'Top Breed Adult - 1 kg', 'retail', '', 3, 1, 3, NULL, '80.00', '85.00', NULL, NULL, 'each', 'bag', '0.00', '', NULL, '60.000', 0, 1, '2026-05-10 15:54:58', '2026-05-18 05:12:29', '0.000', '0.000', 'retail', 1);

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
(0, 1, 0, 'P0001-20260510', '100.000', '157.000', '950.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 2, 0, 'P0002-20260510', '100.000', '157.000', '40.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 3, 0, 'P0003-20260510', '100.000', '157.000', '32.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 4, 0, 'P0004-20260510', '100.000', '157.000', '1400.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 5, 0, 'P0005-20260510', '100.000', '157.000', '80.00', '2027-05-10', 'active', '2026-05-10 15:58:28'),
(0, 2, 0, 'P0002-20260513', '1.000', '157.000', '40.00', '2026-05-13', 'active', '2026-05-13 12:01:38'),
(0, 2, 0, 'P0002-20260513', '1.000', '157.000', '40.00', '2026-05-13', 'active', '2026-05-13 12:01:38'),
(0, 2, 0, 'P0002-20260513', '1.000', '157.000', '40.00', '2026-05-13', 'active', '2026-05-13 12:01:38'),
(0, 1, 0, 'P0001-20260513', '15.000', '157.000', '950.00', '2027-05-13', 'active', '2026-05-13 12:12:11'),
(0, 4, 0, 'P0004-20260513', '51.000', '157.000', '1400.00', '2027-05-13', 'active', '2026-05-13 12:12:11'),
(0, 4, 68, 'P0004-20260518', '201.000', '157.000', '1400.00', '2027-05-18', 'active', '2026-05-18 03:10:49'),
(0, 1, 69, 'P0001-20260518', '15.000', '157.000', '950.00', '2027-05-18', 'active', '2026-05-18 03:11:31'),
(0, 2, 113, 'P0002-20260519', '1.000', '157.000', '40.00', '2027-05-19', 'active', '2026-05-18 17:01:29');

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
  `type` enum('purchase','sale','refund','return','transfer','adjust') NOT NULL,
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
(4, 'sale', 'S_20260511_000233', '2026-05-11', 1, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-10 16:02:33', NULL),
(5, 'purchase', 'P_20260513_200140', '2026-05-13', 6, NULL, NULL, NULL, '', NULL, NULL, '2026-05-13 12:01:38', NULL),
(6, 'sale', 'S_20260513_200454', '2026-05-13', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-13 12:04:52', NULL),
(7, 'purchase', 'P_20260513_201213', '2026-05-13', 6, NULL, NULL, NULL, '', NULL, NULL, '2026-05-13 12:12:11', NULL),
(8, 'sale', 'S_20260513_201925', '2026-05-13', 6, NULL, NULL, 'retail', '', 'amount', NULL, '2026-05-13 12:19:23', NULL),
(9, 'sale', 'S_20260514_181718', '2026-05-14', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-14 10:17:17', NULL),
(10, 'sale', 'S_20260514_181834', '2026-05-14', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-14 10:18:33', NULL),
(11, 'sale', 'S_20260515_075920', '2026-05-15', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-14 23:59:20', NULL),
(12, 'sale', 'S_20260515_075934', '2026-05-15', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-14 23:59:34', NULL),
(13, 'sale', 'S_20260515_080618', '2026-05-15', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-15 00:06:18', NULL),
(14, 'sale', 'S_20260515_194054', '2026-05-15', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-15 11:40:54', NULL),
(15, 'sale', 'S_20260515_194227', '2026-05-15', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-15 11:42:27', NULL),
(16, 'sale', 'S_20260516_102137', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 02:21:37', NULL),
(17, 'sale', 'S_20260516_102247', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 02:22:47', NULL),
(18, 'sale', 'S_20260516_102418', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 02:24:18', NULL),
(19, 'sale', 'S_20260516_102451', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 02:24:50', NULL),
(20, 'sale', 'S_20260516_184133', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 10:41:32', NULL),
(21, 'sale', 'S_20260516_184233', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 10:42:32', NULL),
(22, 'sale', 'S_20260516_184305', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 10:43:05', NULL),
(23, 'sale', 'S_20260516_184329', '2026-05-16', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-16 10:43:29', NULL),
(24, 'sale', 'S_20260517_095128', '2026-05-17', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 01:51:28', NULL),
(25, 'sale', 'S_20260517_095206', '2026-05-17', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 01:52:06', NULL),
(26, 'sale', 'S_20260517_095242', '2026-05-17', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 01:52:43', NULL),
(27, 'sale', 'S_20260517_210157', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 13:01:57', NULL),
(28, 'sale', 'S_20260517_220444', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:04:44', NULL),
(29, 'sale', 'S_20260517_224604', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:46:04', NULL),
(30, 'sale', 'S_20260517_224922', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:49:22', NULL),
(31, 'sale', 'S_20260517_224931', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:49:31', NULL),
(32, 'sale', 'S_20260517_225211', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:52:11', NULL),
(33, 'sale', 'S_20260517_225829', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 14:58:29', NULL),
(34, 'sale', 'S_20260517_230312', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:03:11', NULL),
(35, 'sale', 'S_20260517_230357', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:03:56', NULL),
(36, 'sale', 'S_20260517_231008', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:10:07', NULL),
(37, 'sale', 'S_20260517_231658', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:16:57', NULL),
(38, 'sale', 'S_20260517_231740', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:17:39', NULL),
(39, 'sale', 'S_20260517_231955', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:19:55', NULL),
(40, 'sale', 'S_20260517_232212', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:22:12', NULL),
(41, 'sale', 'S_20260517_232428', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:24:28', NULL),
(42, 'sale', 'S_20260517_233159', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:31:59', NULL),
(43, 'sale', 'S_20260517_233655', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:36:55', NULL),
(44, 'sale', 'S_20260517_234720', '2026-05-17', 2, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-17 15:47:20', NULL),
(45, 'sale', 'S_20260518_110510', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 03:05:10', NULL),
(46, 'sale', 'S_20260518_110545', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 03:05:45', NULL),
(47, 'sale', 'S_20260518_110620', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 03:06:20', NULL),
(48, 'sale', 'S_20260518_110717', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 03:07:18', NULL),
(49, 'purchase', 'P_20260518_111048', '2026-05-18', 6, NULL, NULL, NULL, '', NULL, NULL, '2026-05-18 03:10:49', NULL),
(50, 'purchase', 'P_20260518_111130', '2026-05-18', 6, NULL, NULL, NULL, '', NULL, NULL, '2026-05-18 03:11:31', NULL),
(51, 'sale', 'S_20260518_111301', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 03:13:01', NULL),
(52, 'sale', 'S_20260518_131135', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 05:11:35', NULL),
(53, 'sale', 'S_20260518_131201', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 05:12:01', NULL),
(54, 'sale', 'S_20260518_131228', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 05:12:29', NULL),
(55, 'sale', 'S_20260518_185729', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 10:57:29', NULL),
(56, 'sale', 'S_20260518_185823', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 10:58:24', NULL),
(57, 'sale', 'S_20260518_185850', '2026-05-18', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 10:58:51', NULL),
(91, 'adjust', 'ADJ_20260519_005857', '2026-05-19', 1, NULL, NULL, NULL, 'Recount Correction', NULL, NULL, '2026-05-18 16:58:57', NULL),
(92, 'adjust', 'ADJ_20260519_005925', '2026-05-19', 1, NULL, NULL, NULL, 'Other', NULL, NULL, '2026-05-18 16:59:25', NULL),
(93, 'purchase', 'P_20260519_010129', '2026-05-19', 1, NULL, NULL, NULL, '', NULL, NULL, '2026-05-18 17:01:29', NULL),
(94, 'sale', 'S_20260519_010335', '2026-05-19', 1, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-18 17:03:35', NULL),
(95, 'sale', 'S_20260519_115539', '2026-05-19', 6, NULL, NULL, 'retail', '', NULL, NULL, '2026-05-19 03:55:38', NULL);

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
(1, 1, 1, '100.000', '950.00', NULL, NULL, NULL, NULL),
(2, 1, 2, '100.000', '40.00', NULL, NULL, NULL, NULL),
(3, 1, 3, '100.000', '32.00', NULL, NULL, NULL, NULL),
(4, 1, 4, '100.000', '1400.00', NULL, NULL, NULL, NULL),
(5, 1, 5, '100.000', '80.00', NULL, NULL, NULL, NULL),
(6, 2, 1, '5.000', '1000.00', '950.00', 'retail', NULL, NULL),
(7, 3, 2, '10.000', '50.00', '40.00', 'retail', NULL, NULL),
(8, 3, 4, '10.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(9, 4, 3, '15.000', '38.00', '32.00', 'retail', NULL, NULL),
(10, 5, 2, '1.000', '40.00', NULL, NULL, NULL, NULL),
(11, 5, 2, '1.000', '40.00', NULL, NULL, NULL, NULL),
(12, 5, 2, '1.000', '40.00', NULL, NULL, NULL, NULL),
(13, 6, 2, '1.000', '50.00', '40.00', 'retail', NULL, NULL),
(14, 7, 1, '15.000', '950.00', NULL, NULL, NULL, NULL),
(15, 7, 4, '51.000', '1400.00', NULL, NULL, NULL, NULL),
(16, 8, 2, '2.000', '50.00', '40.00', 'retail', NULL, NULL),
(17, 9, 2, '3.000', '50.00', '40.00', 'retail', NULL, NULL),
(18, 10, 5, '2.000', '85.00', '80.00', 'retail', NULL, NULL),
(19, 11, 1, '1.000', '1000.00', '950.00', 'retail', NULL, NULL),
(20, 12, 4, '1.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(21, 13, 1, '2.000', '1000.00', '950.00', 'retail', NULL, NULL),
(22, 14, 2, '3.000', '50.00', '40.00', 'retail', NULL, NULL),
(23, 15, 5, '2.000', '85.00', '80.00', 'retail', NULL, NULL),
(24, 16, 2, '3.000', '50.00', '40.00', 'retail', NULL, NULL),
(25, 17, 5, '6.000', '85.00', '80.00', 'retail', NULL, NULL),
(26, 17, 3, '1.000', '38.00', '32.00', 'retail', NULL, NULL),
(27, 18, 1, '6.000', '1000.00', '950.00', 'retail', NULL, NULL),
(28, 19, 4, '1.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(29, 20, 1, '6.000', '1000.00', '950.00', 'retail', NULL, NULL),
(30, 21, 4, '5.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(31, 22, 3, '4.000', '38.00', '32.00', 'retail', NULL, NULL),
(32, 22, 5, '1.000', '85.00', '80.00', 'retail', NULL, NULL),
(33, 23, 2, '4.000', '50.00', '40.00', 'retail', NULL, NULL),
(34, 24, 3, '3.000', '38.00', '32.00', 'retail', NULL, NULL),
(35, 25, 2, '4.000', '50.00', '40.00', 'retail', NULL, NULL),
(36, 26, 5, '2.000', '85.00', '80.00', 'retail', NULL, NULL),
(37, 27, 2, '10.000', '50.00', '40.00', 'retail', NULL, NULL),
(38, 27, 1, '3.000', '1000.00', '950.00', 'retail', NULL, NULL),
(39, 27, 3, '8.000', '38.00', '32.00', 'retail', NULL, NULL),
(40, 27, 4, '5.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(41, 27, 5, '12.000', '85.00', '80.00', 'retail', NULL, NULL),
(42, 28, 2, '1.000', '50.00', '40.00', 'retail', NULL, NULL),
(43, 28, 1, '1.000', '1000.00', '950.00', 'retail', NULL, NULL),
(44, 28, 3, '1.000', '38.00', '32.00', 'retail', NULL, NULL),
(45, 28, 5, '1.000', '85.00', '80.00', 'retail', NULL, NULL),
(46, 28, 4, '1.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(47, 29, 1, '2.000', '1000.00', '950.00', 'retail', NULL, NULL),
(50, 32, 4, '10.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(51, 33, 2, '2.000', '50.00', '40.00', 'retail', NULL, NULL),
(52, 34, 5, '1.000', '85.00', '80.00', 'retail', NULL, NULL),
(53, 35, 5, '3.000', '85.00', '80.00', 'retail', NULL, NULL),
(54, 36, 1, '4.000', '1000.00', '950.00', 'retail', NULL, NULL),
(55, 37, 1, '1.000', '1000.00', '950.00', 'retail', NULL, NULL),
(56, 38, 3, '5.000', '38.00', '32.00', 'retail', NULL, NULL),
(57, 39, 3, '2.000', '38.00', '32.00', 'retail', NULL, NULL),
(58, 40, 3, '2.000', '38.00', '32.00', 'retail', NULL, NULL),
(59, 41, 5, '3.000', '85.00', '80.00', 'retail', NULL, NULL),
(60, 42, 5, '2.000', '85.00', '80.00', 'retail', NULL, NULL),
(61, 43, 2, '5.000', '50.00', '40.00', 'retail', NULL, NULL),
(62, 44, 3, '3.000', '38.00', '32.00', 'retail', NULL, NULL),
(63, 45, 1, '5.000', '1000.00', '950.00', 'retail', NULL, NULL),
(64, 46, 3, '5.000', '38.00', '32.00', 'retail', NULL, NULL),
(65, 46, 4, '2.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(66, 47, 3, '2.000', '38.00', '32.00', 'retail', NULL, NULL),
(67, 48, 2, '6.000', '50.00', '40.00', 'retail', NULL, NULL),
(68, 49, 4, '201.000', '1400.00', NULL, NULL, NULL, NULL),
(69, 50, 1, '15.000', '950.00', NULL, NULL, NULL, NULL),
(70, 51, 4, '6.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(71, 52, 4, '3.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(72, 53, 1, '5.000', '1000.00', '950.00', 'retail', NULL, NULL),
(73, 54, 5, '5.000', '85.00', '80.00', 'retail', NULL, NULL),
(74, 55, 4, '3.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(75, 55, 1, '2.000', '1000.00', '950.00', 'retail', NULL, NULL),
(76, 56, 4, '3.000', '1500.00', '1400.00', 'retail', NULL, NULL),
(77, 57, 2, '5.000', '50.00', '40.00', 'retail', NULL, NULL),
(79, 59, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(80, 60, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(81, 61, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(82, 62, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(83, 63, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(84, 64, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(88, 68, 2, '5.000', '0.00', NULL, NULL, NULL, NULL),
(89, 69, 3, '5.000', '0.00', NULL, NULL, NULL, NULL),
(90, 70, 1, '3.000', '0.00', NULL, NULL, NULL, NULL),
(91, 71, 3, '1.000', '0.00', NULL, NULL, NULL, NULL),
(92, 72, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(93, 73, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(94, 74, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(95, 75, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(96, 76, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(97, 77, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(98, 78, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(99, 79, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(100, 80, 2, '2.000', '0.00', NULL, NULL, NULL, NULL),
(101, 81, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(102, 82, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(103, 83, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(104, 84, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(105, 85, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(106, 86, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(107, 87, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(108, 88, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(109, 89, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(110, 90, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(111, 91, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(112, 92, 2, '1.000', '0.00', NULL, NULL, NULL, NULL),
(113, 93, 2, '1.000', '40.00', NULL, NULL, NULL, NULL),
(114, 94, 2, '3.000', '50.00', '40.00', NULL, NULL, NULL),
(115, 95, 2, '7.000', '50.00', '40.00', NULL, NULL, NULL),
(116, 95, 3, '2.000', '38.00', '32.00', NULL, NULL, NULL);

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
(2, 'User', 'user@example.com', '$2y$10$FrC93o/8fat2MOvAeua/c.v66j7bvGujnnhwNN7ic3oeD9Po4.rQO', 'staff', '2025-08-29 15:43:52', 1),
(3, 'Auditor User', 'auditor@example.com', '$2y$10$B8dR7TlJsxgJqUgnN.2wt.hYr65a31yGh6G3gvrXUV5pnRe1vWbRi', 'auditor', '2025-08-29 15:43:52', 1),
(6, 'Admin', 'admin@store.com', '$2y$10$L5dUQRiyxvY6LJNy0vk0M.1qzCVhX0puZ6KvMCgJi13hHOBbS5g1y', 'admin', '2026-04-10 03:14:47', 1);

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
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
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
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
