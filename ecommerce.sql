-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 09:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('selected','not-selected') NOT NULL DEFAULT 'selected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Electronics', 'All Electronics Items', '2025-11-09 02:28:46'),
(2, 'Books', 'All section of books', '2025-11-09 02:41:16'),
(3, 'Glocery', 'All gloceries', '2025-11-10 19:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `deals`
--

CREATE TABLE `deals` (
  `id` int(10) UNSIGNED NOT NULL,
  `deal_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deals`
--

INSERT INTO `deals` (`id`, `deal_name`, `created_at`) VALUES
(1, 'Winter sale\r\n', '2025-11-17 05:35:29'),
(2, 'Summer sale', '2025-11-17 19:16:45');

-- --------------------------------------------------------

--
-- Table structure for table `deal_products`
--

CREATE TABLE `deal_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `deal_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `available_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `offered_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `deal_products`
--
DELIMITER $$
CREATE TRIGGER `trg_deal_products_reserve_stock` AFTER INSERT ON `deal_products` FOR EACH ROW BEGIN
    -- When a deal_product is created, remove its quantity from the original product stock
    UPDATE products
    SET available_qty = GREATEST(0, available_qty - NEW.available_quantity)
    WHERE id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `payment_id`) VALUES
(1, 1, '2025-11-16 23:38:23', 1),
(2, 1, '2025-11-16 23:54:20', 2),
(3, 1, '2025-11-17 00:18:34', 3),
(11, 1, '2025-11-17 00:26:06', 11),
(12, 1, '2025-11-17 01:42:05', 12);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `deal_id` int(10) UNSIGNED DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','confirmed','shipped','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `order_details`
--
DELIMITER $$
CREATE TRIGGER `trg_order_details_deal_deduct` AFTER INSERT ON `order_details` FOR EACH ROW BEGIN
    -- If this order line is for a deal (no direct product, only deal_id)
    IF NEW.deal_id IS NOT NULL THEN
        UPDATE deal_products
        SET available_quantity = GREATEST(0, available_quantity - NEW.quantity)
        WHERE deal_id = NEW.deal_id;
        -- NOTE: We do not touch products here, because stock was already
        -- reserved in products when deal_products was created.
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_order_details_product_deduct` AFTER INSERT ON `order_details` FOR EACH ROW BEGIN
    -- If this order line is a regular product purchase (not a deal)
    IF NEW.product_id IS NOT NULL AND NEW.deal_id IS NULL THEN
        UPDATE products
        SET available_qty = GREATEST(0, available_qty - NEW.quantity)
        WHERE id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `card_number` varchar(25) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `card_holder_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `card_number`, `amount`, `card_holder_name`, `created_at`) VALUES
(1, '3458', 20.00, 'Abir', '2025-11-17 05:37:55'),
(2, '498249298234239', 20.00, 'Abir', '2025-11-17 05:54:20'),
(3, '3248', 5.00, 'sdfkdshf', '2025-11-17 06:18:34'),
(11, '43985', 20.00, 'dasd', '2025-11-17 06:26:06'),
(12, '8583', 20.00, 'sjdnvk', '2025-11-17 07:42:05');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_qty` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `category_id` int(10) UNSIGNED NOT NULL,
  `subcategory_id` int(10) UNSIGNED NOT NULL,
  `product_image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `available_qty`, `category_id`, `subcategory_id`, `product_image_url`, `description`, `created_at`) VALUES
(125, 'Intel Core i9-14900K', 599.99, 12, 1, 1, NULL, 'Flagship 14th Gen Intel Core i9 desktop processor with 24 cores and 32 threads for extreme performance.', '2025-11-12 16:00:00'),
(126, 'AMD Ryzen 9 7950X3D', 699.00, 8, 1, 1, NULL, 'Top-tier AMD Ryzen 7000 series CPU with 3D V-Cache for gaming and productivity.', '2025-11-12 16:00:00'),
(127, 'Intel Core i7-13700K', 419.50, 15, 1, 1, NULL, '13th Gen Intel Core i7 processor featuring hybrid architecture with 16 cores.', '2025-11-12 16:00:00'),
(128, 'AMD Ryzen 7 7800X3D', 449.99, 10, 1, 1, NULL, 'High-performance 8-core AMD CPU optimized for gaming with 3D cache technology.', '2025-11-12 16:00:00'),
(129, 'Intel Core i5-13600K', 319.00, 18, 1, 1, NULL, 'Mid-range Intel CPU offering 14 cores for strong gaming and multitasking.', '2025-11-12 16:00:00'),
(130, 'AMD Ryzen 5 7600', 229.95, 9, 1, 1, NULL, 'Affordable 6-core AMD processor with Zen 4 architecture for smooth desktop performance.', '2025-11-12 16:00:00'),
(131, 'Intel Core i3-13100', 139.50, 17, 1, 1, NULL, 'Entry-level 13th Gen Intel Core i3 quad-core processor for everyday computing.', '2025-11-12 16:00:00'),
(132, 'AMD Ryzen 9 5900X', 359.00, 11, 1, 1, NULL, '12-core AMD CPU based on Zen 3 architecture delivering powerful performance for creators.', '2025-11-12 16:00:00'),
(133, 'Intel Core i5-12400F', 179.00, 16, 1, 1, NULL, 'Budget-friendly 12th Gen Intel CPU with 6 cores and no integrated graphics.', '2025-11-12 16:00:00'),
(134, 'AMD Ryzen 7 5700G', 249.99, 14, 1, 1, NULL, '8-core AMD APU with integrated Radeon graphics suitable for compact systems.', '2025-11-12 16:00:00'),
(135, 'Intel Xeon W9-3495X', 5999.00, 5, 1, 1, NULL, 'Workstation-grade Intel Xeon CPU with 56 cores for demanding server and workstation tasks.', '2025-11-12 16:00:00'),
(136, 'Apple M2 Pro', 1299.00, 7, 1, 1, NULL, 'Apple silicon chip for MacBook Pro featuring 12-core CPU and advanced neural engine.', '2025-11-12 16:00:00'),
(137, 'Intel Core i9-12900KS', 579.00, 10, 1, 1, NULL, '12th Gen Intel processor with 16 cores and enhanced turbo performance for enthusiasts.', '2025-11-12 16:00:00'),
(138, 'AMD Threadripper PRO 5995WX', 6499.99, 6, 1, 1, NULL, 'High-end workstation processor with 64 cores designed for 3D rendering and simulation.', '2025-11-12 16:00:00'),
(139, 'Intel Core i7-12700F', 309.99, 13, 1, 1, NULL, 'Powerful 12-core CPU without integrated GPU, ideal for discrete GPU setups.', '2025-11-12 16:00:00'),
(140, 'AMD Ryzen 5 5600X', 199.00, 20, 1, 1, NULL, 'Popular 6-core processor for gaming and everyday desktop performance.', '2025-11-12 16:00:00'),
(141, 'Intel Pentium Gold G7400', 89.99, 19, 1, 1, NULL, 'Dual-core entry-level CPU ideal for office and basic computing systems.', '2025-11-12 16:00:00'),
(142, 'AMD Athlon 3000G', 59.00, 15, 1, 1, NULL, 'Budget-friendly dual-core processor with integrated Radeon graphics.', '2025-11-12 16:00:00'),
(143, 'Intel Core i9-9900K', 289.00, 9, 1, 1, NULL, '8-core 9th Gen Intel CPU known for gaming performance and overclocking.', '2025-11-12 16:00:00'),
(144, 'AMD Ryzen 3 4100', 99.99, 18, 1, 1, NULL, 'Quad-core entry-level processor suitable for basic systems and budget gaming.', '2025-11-12 16:00:00'),
(145, 'ASUS ROG Strix Z790-E Gaming WiFi', 369.99, 12, 1, 2, NULL, 'High-end ATX motherboard with Intel Z790 chipset, PCIe 5.0, and WiFi 6E.', '2025-11-12 16:00:00'),
(146, 'MSI B650M PRO-VDH WIFI', 159.99, 8, 1, 2, NULL, 'Reliable Micro-ATX motherboard supporting AMD Ryzen 7000 series with built-in WiFi.', '2025-11-12 16:00:00'),
(147, 'Gigabyte X670 AORUS Elite AX', 249.50, 15, 1, 2, NULL, 'AMD X670 motherboard designed for Ryzen 7000 CPUs with DDR5 and USB-C support.', '2025-11-12 16:00:00'),
(148, 'ASRock B550M Steel Legend', 134.90, 18, 1, 2, NULL, 'Durable B550 motherboard featuring strong VRMs and dual M.2 slots.', '2025-11-12 16:00:00'),
(149, 'NZXT N7 Z790', 299.00, 9, 1, 2, NULL, 'Stylish motherboard for Intel 13th Gen CPUs, offering smart layout and RGB integration.', '2025-11-12 16:00:00'),
(150, 'ASUS PRIME H610M-A D4', 119.99, 14, 1, 2, NULL, 'Entry-level motherboard supporting Intel 12th Gen CPUs with DDR4 memory.', '2025-11-12 16:00:00'),
(151, 'Gigabyte B550 AORUS PRO AC', 189.75, 11, 1, 2, NULL, 'Mid-range AMD B550 motherboard with built-in WiFi and high-speed storage options.', '2025-11-12 16:00:00'),
(152, 'MSI MAG Z690 TOMAHAWK WIFI DDR4', 229.99, 7, 1, 2, NULL, 'Intel Z690 motherboard offering premium power delivery and dual M.2 slots.', '2025-11-12 16:00:00'),
(153, 'ASRock Z790 PG Riptide', 209.49, 6, 1, 2, NULL, 'Performance gaming motherboard for Intel 12th/13th Gen processors with robust VRM design.', '2025-11-12 16:00:00'),
(154, 'Biostar B760MX2-E PRO', 104.89, 17, 1, 2, NULL, 'Compact Micro-ATX motherboard for Intel CPUs with solid performance for budget builds.', '2025-11-12 16:00:00'),
(155, 'NVIDIA GeForce RTX 4090 Founders Edition', 2499.99, 8, 1, 3, NULL, 'Flagship GPU delivering extreme gaming and AI rendering performance with 24GB GDDR6X memory.', '2025-11-12 16:00:00'),
(156, 'AMD Radeon RX 7900 XTX', 1899.00, 10, 1, 3, NULL, 'High-end graphics card featuring RDNA 3 architecture and 24GB GDDR6 for exceptional 4K gaming.', '2025-11-12 16:00:00'),
(157, 'ASUS ROG Strix RTX 4080 Super OC Edition', 1799.99, 7, 1, 3, NULL, 'Factory-overclocked RTX 4080 with triple-fan cooling and customizable RGB lighting.', '2025-11-12 16:00:00'),
(158, 'Gigabyte AORUS GeForce RTX 4070 Ti Master', 1099.50, 12, 1, 3, NULL, 'Powerful mid-high tier GPU offering smooth 1440p gaming and strong ray-tracing performance.', '2025-11-12 16:00:00'),
(159, 'MSI Ventus 3X RTX 4070', 949.00, 9, 1, 3, NULL, 'Efficient and quiet graphics card built for gamers seeking balance between performance and cooling.', '2025-11-12 16:00:00'),
(160, 'ZOTAC Gaming GeForce RTX 4060 Twin Edge', 549.99, 15, 1, 3, NULL, 'Compact and energy-efficient card suitable for small builds with 8GB GDDR6 memory.', '2025-11-12 16:00:00'),
(161, 'Sapphire Nitro+ Radeon RX 7800 XT', 849.00, 11, 1, 3, NULL, 'Premium RX 7800 XT variant offering enhanced cooling and aesthetic RGB design.', '2025-11-12 16:00:00'),
(162, 'PowerColor Red Devil RX 7700 XT', 749.00, 13, 1, 3, NULL, 'Aggressively tuned GPU for gamers seeking performance and thermal stability.', '2025-11-12 16:00:00'),
(163, 'PNY GeForce RTX 4060 Ti 8GB Verto', 599.99, 6, 1, 3, NULL, 'Reliable and compact card offering DLSS 3 and efficient 1080p to 1440p gaming.', '2025-11-12 16:00:00'),
(164, 'Intel Arc A770 Limited Edition', 499.99, 17, 1, 3, NULL, 'Intel’s enthusiast-class GPU designed for modern gaming and content creation workloads.', '2025-11-12 16:00:00'),
(165, 'Corsair Vengeance LPX 16GB DDR4 3200MHz', 64.99, 10, 1, 4, NULL, 'High-performance DDR4 memory designed for gaming and multitasking with superior heat spreaders.', '2025-11-12 16:00:00'),
(166, 'G.Skill Trident Z RGB 32GB DDR4 3600MHz', 124.50, 8, 1, 4, NULL, 'Stylish RGB lighting and fast clock speed make it perfect for high-end builds and content creation.', '2025-11-12 16:00:00'),
(167, 'Kingston Fury Beast 8GB DDR5 5600MHz', 49.95, 15, 1, 4, NULL, 'Next-generation DDR5 performance offering faster speeds and improved energy efficiency.', '2025-11-12 16:00:00'),
(168, 'Crucial 16GB DDR4 2666MHz Laptop RAM', 52.00, 18, 1, 4, NULL, 'Reliable memory upgrade for laptops, ensuring smoother multitasking and faster application loads.', '2025-11-12 16:00:00'),
(169, 'TeamGroup T-Force Delta RGB 64GB DDR5 6000MHz', 219.99, 7, 1, 4, NULL, 'Premium DDR5 kit offering ultra-fast speeds and dynamic RGB effects for performance PCs.', '2025-11-12 16:00:00'),
(170, 'Patriot Viper Steel 32GB DDR4 4000MHz', 109.90, 6, 1, 4, NULL, 'High-frequency RAM optimized for overclocking and professional workstation builds.', '2025-11-12 16:00:00'),
(171, 'ADATA XPG Lancer 16GB DDR5 6000MHz', 85.75, 12, 1, 4, NULL, 'Modern DDR5 RAM module built for gaming and heavy-duty processing workloads.', '2025-11-12 16:00:00'),
(172, 'Samsung Original 8GB DDR4 3200MHz', 37.49, 20, 1, 4, NULL, 'Trusted OEM memory module used in laptops and desktops for reliable everyday performance.', '2025-11-12 16:00:00'),
(173, 'Samsung 970 EVO Plus 1TB', 129.99, 12, 1, 5, NULL, 'High-speed NVMe M.2 SSD ideal for gaming and professional workloads.', '2025-11-13 16:00:00'),
(174, 'WD Blue 2TB SATA SSD', 159.49, 7, 1, 5, NULL, 'Reliable SATA SSD for everyday computing and large storage needs.', '2025-11-13 16:00:00'),
(175, 'Crucial MX500 1TB', 114.75, 15, 1, 5, NULL, 'Durable SSD with excellent performance for desktops and laptops.', '2025-11-13 16:00:00'),
(176, 'Kingston NV1 500GB', 49.99, 18, 1, 5, NULL, 'Affordable NVMe SSD with solid performance for entry-level systems.', '2025-11-13 16:00:00'),
(177, 'Samsung 980 Pro 2TB', 349.99, 5, 1, 5, NULL, 'Professional NVMe SSD with extreme speed for gaming and content creation.', '2025-11-13 16:00:00'),
(178, 'Sabrent Rocket Q 1TB', 99.95, 10, 1, 5, NULL, 'High-capacity QLC NVMe SSD for cost-effective performance.', '2025-11-13 16:00:00'),
(179, 'Seagate FireCuda 530 2TB', 419.50, 8, 1, 5, NULL, 'Top-tier NVMe SSD designed for gamers and high-performance computing.', '2025-11-13 16:00:00'),
(180, 'Seagate Barracuda 2TB', 59.99, 12, 1, 21, NULL, 'Reliable 2TB HDD for desktop storage with 7200 RPM speed.', '2025-11-13 16:00:00'),
(181, 'Western Digital Blue 1TB', 49.50, 8, 1, 21, NULL, '1TB HDD designed for everyday computing and storage expansion.', '2025-11-13 16:00:00'),
(182, 'Toshiba P300 3TB', 79.99, 15, 1, 21, NULL, 'High-capacity 3TB HDD suitable for gaming and multimedia storage.', '2025-11-13 16:00:00'),
(183, 'HGST Deskstar 4TB', 109.99, 7, 1, 21, NULL, 'Enterprise-grade 4TB hard drive with high reliability and performance.', '2025-11-13 16:00:00'),
(184, 'Seagate IronWolf 6TB', 159.00, 10, 1, 21, NULL, 'NAS-optimized HDD ideal for small office and home server setups.', '2025-11-13 16:00:00'),
(185, 'Western Digital Black 2TB', 89.99, 5, 1, 21, NULL, 'High-performance HDD for gaming and heavy workloads.', '2025-11-13 16:00:00'),
(186, 'Toshiba X300 5TB', 129.99, 9, 1, 21, NULL, '5TB HDD with excellent speed and durability for creative professionals.', '2025-11-13 16:00:00'),
(187, 'Corsair RM750x', 129.99, 12, 1, 6, NULL, '750W 80+ Gold fully modular power supply with ultra-quiet operation.', '2025-11-13 16:00:00'),
(188, 'EVGA SuperNOVA 650 G5', 109.50, 8, 1, 6, NULL, 'Reliable 650W 80+ Gold modular PSU with high efficiency and durability.', '2025-11-13 16:00:00'),
(189, 'Seasonic Focus GX-850', 159.99, 15, 1, 6, NULL, '850W 80+ Gold power supply with fully modular cables and compact design.', '2025-11-13 16:00:00'),
(190, 'Cooler Master MWE Gold 650', 94.75, 10, 1, 6, NULL, 'Efficient 650W PSU with 80+ Gold certification and silent fan design.', '2025-11-13 16:00:00'),
(191, 'Thermaltake Toughpower GF1 750W', 139.99, 7, 1, 6, NULL, 'High-performance 750W power supply with 80+ Gold rating and modular cables.', '2025-11-13 16:00:00'),
(192, 'NZXT C750', 124.99, 20, 1, 6, NULL, '750W fully modular PSU with high efficiency and compact form factor.', '2025-11-13 16:00:00'),
(193, 'Antec Earthwatts Gold Pro 650', 102.50, 9, 1, 6, NULL, '650W 80+ Gold certified power supply with excellent stability and low noise.', '2025-11-13 16:00:00'),
(194, 'Corsair Mid-Tower Case', 129.99, 12, 1, 7, NULL, 'Sleek mid-tower with excellent airflow, tempered glass, and RGB lighting.', '2025-11-12 16:00:00'),
(195, 'NZXT H510 Elite', 189.50, 8, 1, 7, NULL, 'Premium compact ATX case featuring dual tempered glass panels and cable management.', '2025-11-12 16:00:00'),
(196, 'Cooler Master MasterBox', 99.75, 15, 1, 7, NULL, 'Affordable case with mesh front panel, optimized cooling, and easy build access.', '2025-11-12 16:00:00'),
(197, 'Fractal Design Meshify C', 139.00, 10, 1, 7, NULL, 'High-airflow compact case with sleek design and excellent cooling options.', '2025-11-12 16:00:00'),
(198, 'Phanteks Eclipse P400A', 109.95, 18, 1, 7, NULL, 'Mid-tower case with full mesh front panel and versatile component support.', '2025-11-12 16:00:00'),
(199, 'Lian Li PC-O11 Dynamic', 169.99, 7, 1, 7, NULL, 'Stylish case with tempered glass, dual-chamber layout, and water-cooling support.', '2025-11-12 16:00:00'),
(200, 'Be Quiet! Pure Base 500DX', 119.50, 14, 1, 7, NULL, 'Quiet, high-performance case with mesh front, RGB, and outstanding airflow.', '2025-11-12 16:00:00'),
(201, 'Arctic Freezer 34 eSports Duo', 59.99, 12, 1, 8, NULL, 'High-performance air cooler with dual fans for efficient CPU cooling.', '2025-11-13 16:00:00'),
(202, 'Noctua NH-D15', 99.99, 7, 1, 8, NULL, 'Premium dual-tower CPU cooler known for quiet operation and excellent thermal performance.', '2025-11-13 16:00:00'),
(203, 'Corsair iCUE H100i RGB Pro XT', 129.50, 9, 1, 8, NULL, 'All-in-one liquid cooler with customizable RGB lighting and powerful cooling.', '2025-11-13 16:00:00'),
(204, 'Cooler Master Hyper 212 Black Edition', 44.99, 15, 1, 8, NULL, 'Reliable budget-friendly CPU cooler with optimized airflow design.', '2025-11-13 16:00:00'),
(205, 'be quiet! Dark Rock Pro 4', 89.95, 6, 1, 8, NULL, 'High-end silent CPU cooler with dual heatsinks for extreme cooling efficiency.', '2025-11-13 16:00:00'),
(206, 'NZXT Kraken X63', 149.99, 8, 1, 8, NULL, '360mm liquid cooler featuring RGB lighting and quiet pump design.', '2025-11-13 16:00:00'),
(207, 'Thermaltake Floe Riing RGB 360', 139.99, 5, 1, 8, NULL, 'Liquid cooling system with customizable RGB fans and high thermal performance.', '2025-11-13 16:00:00'),
(208, 'Deepcool GAMMAXX 400 V2', 34.50, 20, 1, 8, NULL, 'Affordable CPU cooler with 4 heatpipes and improved cooling efficiency.', '2025-11-13 16:00:00'),
(209, 'ARCTIC Liquid Freezer II 280', 119.99, 10, 1, 8, NULL, 'Efficient AIO liquid cooler with integrated pump and low noise operation.', '2025-11-13 16:00:00'),
(210, 'Scythe Mugen 5 Rev.B', 49.99, 13, 1, 8, NULL, 'Compact tower cooler with excellent thermal performance and quiet operation.', '2025-11-13 16:00:00'),
(211, 'UltraSharp 27-inch 4K Monitor', 499.99, 12, 1, 9, NULL, '27-inch 4K monitor with IPS panel, HDR support, and ultra-thin bezels.', '2025-11-13 16:00:00'),
(212, 'Gaming Pro 32-inch Curved Monitor', 599.50, 8, 1, 9, NULL, '32-inch curved gaming monitor with 165Hz refresh rate and 1ms response time.', '2025-11-13 16:00:00'),
(213, 'Professional Designer 24-inch Monitor', 349.75, 15, 1, 9, NULL, '24-inch monitor with color accuracy, ideal for graphic design and photography.', '2025-11-13 16:00:00'),
(214, 'Budget 22-inch LED Monitor', 129.99, 10, 1, 9, NULL, 'Affordable 22-inch LED monitor with Full HD resolution and energy-efficient design.', '2025-11-13 16:00:00'),
(215, 'UltraWide 34-inch Monitor', 799.00, 7, 1, 9, NULL, '34-inch ultra-wide monitor with 3440x1440 resolution and curved display for immersive experience.', '2025-11-13 16:00:00'),
(216, '4K HDR 28-inch Monitor', 559.25, 9, 1, 9, NULL, '28-inch 4K monitor with HDR10 support and wide color gamut.', '2025-11-13 16:00:00'),
(217, 'Portable 15-inch USB-C Monitor', 229.99, 14, 1, 9, NULL, 'Lightweight portable monitor with USB-C connectivity for laptops and mobile devices.', '2025-11-13 16:00:00'),
(218, 'Professional 27-inch Gaming Monitor', 479.50, 6, 1, 9, NULL, '27-inch monitor with 144Hz refresh rate, G-Sync compatibility, and HDR support.', '2025-11-13 16:00:00'),
(219, 'Touchscreen 23-inch Monitor', 399.99, 11, 1, 9, NULL, '23-inch touchscreen monitor suitable for interactive presentations and creative work.', '2025-11-13 16:00:00'),
(220, 'LED 25-inch Monitor', 279.75, 13, 1, 9, NULL, '25-inch LED monitor with adjustable stand and energy-efficient design.', '2025-11-13 16:00:00'),
(221, 'Curved 27-inch Gaming Monitor', 539.99, 5, 1, 9, NULL, '27-inch curved gaming monitor with 165Hz refresh rate and FreeSync support.', '2025-11-13 16:00:00'),
(222, 'HDR 32-inch Professional Monitor', 689.50, 9, 1, 9, NULL, '32-inch professional monitor with HDR, wide color gamut, and factory calibration.', '2025-11-13 16:00:00'),
(223, 'Mechanical Gaming Keyboard', 129.99, 15, 1, 10, NULL, 'High-quality mechanical keyboard with RGB lighting and customizable keys.', '2025-11-12 16:00:00'),
(224, 'Wireless Ergonomic Keyboard', 89.50, 12, 1, 10, NULL, 'Ergonomic wireless keyboard designed for comfort during long typing sessions.', '2025-11-12 16:00:00'),
(225, 'Compact 60% Keyboard', 79.99, 8, 1, 10, NULL, 'Compact 60% keyboard ideal for minimalistic setups and travel.', '2025-11-12 16:00:00'),
(226, 'RGB Backlit Keyboard', 99.99, 20, 1, 10, NULL, 'Keyboard with customizable RGB backlighting and anti-ghosting features.', '2025-11-12 16:00:00'),
(227, 'Mechanical Keyboard with Macro Keys', 139.49, 7, 1, 10, NULL, 'Mechanical keyboard featuring programmable macro keys for gaming and productivity.', '2025-11-12 16:00:00'),
(228, 'Wireless Bluetooth Keyboard', 74.99, 10, 1, 10, NULL, 'Slim wireless Bluetooth keyboard compatible with multiple devices.', '2025-11-12 16:00:00'),
(229, 'Multimedia Keyboard', 59.99, 18, 1, 10, NULL, 'Keyboard with dedicated multimedia keys for easy control of audio and video.', '2025-11-12 16:00:00'),
(230, 'Gaming Keyboard with Mechanical Switches', 149.00, 9, 1, 10, NULL, 'Durable gaming keyboard with high-precision mechanical switches.', '2025-11-12 16:00:00'),
(231, 'Waterproof Keyboard', 69.99, 14, 1, 10, NULL, 'Spill-resistant and waterproof keyboard suitable for home and office use.', '2025-11-12 16:00:00'),
(232, 'Tenkeyless Mechanical Keyboard', 119.50, 6, 1, 10, NULL, 'Mechanical keyboard without numeric keypad, optimized for compact workspaces.', '2025-11-12 16:00:00'),
(233, 'Backlit Office Keyboard', 49.99, 16, 1, 10, NULL, 'Simple backlit keyboard designed for office work with quiet keys.', '2025-11-12 16:00:00'),
(234, 'Ultra-thin Bluetooth Keyboard', 84.99, 11, 1, 10, NULL, 'Ultra-thin and portable Bluetooth keyboard, perfect for tablets and laptops.', '2025-11-12 16:00:00'),
(235, 'Logitech MX Master 3', 99.99, 15, 1, 11, NULL, 'Advanced wireless mouse with ergonomic design and customizable buttons for productivity.', '2025-11-12 16:00:00'),
(236, 'Razer DeathAdder V2', 69.99, 12, 1, 11, NULL, 'High-precision gaming mouse with 20,000 DPI sensor and RGB lighting.', '2025-11-12 16:00:00'),
(237, 'Corsair Dark Core RGB', 79.50, 8, 1, 11, NULL, 'Wireless gaming mouse with customizable side grips and RGB lighting zones.', '2025-11-12 16:00:00'),
(238, 'Microsoft Surface Mouse', 59.99, 10, 1, 11, NULL, 'Sleek and portable mouse compatible with Windows devices and laptops.', '2025-11-12 16:00:00'),
(239, 'Logitech G502 Hero', 89.99, 14, 1, 11, NULL, 'Popular gaming mouse with adjustable DPI, 11 programmable buttons, and RGB lighting.', '2025-11-12 16:00:00'),
(240, 'SteelSeries Rival 3', 49.99, 20, 1, 11, NULL, 'Affordable gaming mouse with TrueMove Core sensor for precision tracking.', '2025-11-12 16:00:00'),
(241, 'HP X3500 Wired Mouse', 25.75, 18, 1, 11, NULL, 'Reliable wired mouse with comfortable design and long-lasting performance.', '2025-11-12 16:00:00'),
(242, 'Apple Magic Mouse 2', 99.00, 7, 1, 11, NULL, 'Multi-touch mouse for macOS with rechargeable battery and sleek design.', '2025-11-12 16:00:00'),
(243, 'Glorious Model O', 69.99, 9, 1, 11, NULL, 'Ultralight gaming mouse with honeycomb shell and high-accuracy sensor.', '2025-11-12 16:00:00'),
(244, 'Roccat Kain 200 AIMO', 59.95, 16, 1, 11, NULL, 'Ergonomic gaming mouse with fast clicks, customizable buttons, and RGB lighting.', '2025-11-12 16:00:00'),
(245, 'Logitech Pebble M350', 29.99, 11, 1, 11, NULL, 'Compact wireless mouse with silent clicks and easy portability.', '2025-11-12 16:00:00'),
(246, 'Lenovo 300 Wireless Mouse', 24.50, 19, 1, 11, NULL, 'Affordable wireless mouse with reliable connection and long battery life.', '2025-11-12 16:00:00'),
(247, 'Windows 11 Professional', 199.99, 15, 1, 17, NULL, 'The latest Windows 11 Professional edition with enhanced security and productivity features.', '2025-11-12 16:00:00'),
(248, 'Ubuntu 24.04 LTS', 0.00, 20, 1, 17, NULL, 'Stable and free Ubuntu 24.04 Long Term Support edition, perfect for developers and servers.', '2025-11-12 16:00:00'),
(249, 'macOS Sonoma', 0.00, 10, 1, 17, NULL, 'The newest macOS Sonoma operating system for Mac devices with enhanced performance and features.', '2025-11-12 16:00:00'),
(250, 'Windows Server 2022', 499.99, 8, 1, 17, NULL, 'Powerful Windows Server 2022 edition for business and enterprise server solutions.', '2025-11-12 16:00:00'),
(251, 'Fedora Workstation 39', 0.00, 12, 1, 17, NULL, 'Cutting-edge Fedora 39 Workstation, ideal for developers and Linux enthusiasts.', '2025-11-12 16:00:00'),
(252, 'Red Hat Enterprise Linux 9', 349.99, 7, 1, 17, NULL, 'Enterprise-grade Linux operating system with official Red Hat support.', '2025-11-12 16:00:00'),
(253, 'Debian 12 Bookworm', 0.00, 18, 1, 17, NULL, 'Stable Debian 12 release, ideal for servers, desktops, and development environments.', '2025-11-12 16:00:00'),
(254, 'Windows 10 Pro', 149.99, 14, 1, 17, NULL, 'Classic Windows 10 Professional edition, widely used in offices and personal computers.', '2025-11-12 16:00:00'),
(255, 'Linux Mint 21.3', 0.00, 16, 1, 17, NULL, 'User-friendly Linux Mint 21.3, perfect for beginners and productivity users.', '2025-11-12 16:00:00'),
(256, 'Chrome OS Flex', 0.00, 9, 1, 17, NULL, 'Lightweight and secure Chrome OS Flex, suitable for repurposing old hardware.', '2025-11-12 16:00:00'),
(257, 'ProSound X1 Headset', 79.99, 12, 1, 18, NULL, 'High-fidelity gaming headset with noise-canceling microphone and RGB lighting.', '2025-11-12 16:00:00'),
(258, 'AudioMax 3000 Speakers', 149.50, 8, 1, 18, NULL, 'Powerful stereo speakers with deep bass and clear mids for immersive sound.', '2025-11-12 16:00:00'),
(259, 'CrystalClear USB Headset', 59.90, 15, 1, 18, NULL, 'Lightweight headset with USB connection, perfect for online meetings and calls.', '2025-11-12 16:00:00'),
(260, 'BassBoost Studio Headphones', 129.00, 10, 1, 18, NULL, 'Over-ear studio headphones with enhanced bass and excellent noise isolation.', '2025-11-12 16:00:00'),
(261, 'EchoSound Bluetooth Speaker', 89.75, 7, 1, 18, NULL, 'Portable Bluetooth speaker with 12-hour battery life and water-resistant design.', '2025-11-12 16:00:00'),
(262, 'GamerPro 7.1 Surround Headset', 139.99, 9, 1, 18, NULL, 'Surround sound gaming headset with virtual 7.1 channels and adjustable mic.', '2025-11-12 16:00:00'),
(263, 'MiniBeat Desktop Speakers', 49.99, 20, 1, 18, NULL, 'Compact desktop speakers delivering crisp audio for home and office.', '2025-11-12 16:00:00'),
(264, 'NoiseBlock Over-Ear Headphones', 119.00, 6, 1, 18, NULL, 'Premium noise-canceling headphones for focused work or travel.', '2025-11-12 16:00:00'),
(265, 'StereoWave Wireless Headset', 99.95, 14, 1, 18, NULL, 'Wireless headset with long-range connectivity and comfortable fit.', '2025-11-12 16:00:00'),
(266, 'BoomBox Portable Speaker', 129.99, 11, 1, 18, NULL, 'High-powered portable speaker with multi-device Bluetooth pairing.', '2025-11-12 16:00:00'),
(267, 'VoicePro Conference Headset', 89.50, 16, 1, 18, NULL, 'Optimized for conference calls with clear mic and lightweight design.', '2025-11-12 16:00:00'),
(268, 'PulseX Gaming Speakers', 109.00, 5, 1, 18, NULL, 'RGB-lit gaming speakers with rich bass and customizable sound profiles.', '2025-11-12 16:00:00'),
(269, 'Logitech HD Pro Webcam C920', 79.99, 12, 1, 19, NULL, '1080p HD webcam with excellent low-light correction and dual microphones.', '2025-11-12 16:00:00'),
(270, 'Razer Kiyo', 99.50, 8, 1, 19, NULL, 'Streaming webcam with adjustable ring light, perfect for content creators.', '2025-11-12 16:00:00'),
(271, 'Microsoft LifeCam HD-3000', 49.95, 15, 1, 19, NULL, 'Affordable HD webcam for everyday video calls and online meetings.', '2025-11-12 16:00:00'),
(272, 'Logitech StreamCam', 159.00, 10, 1, 19, NULL, 'High-frame-rate webcam optimized for streaming on multiple platforms.', '2025-11-12 16:00:00'),
(273, 'AUSDOM AF640 Full HD Webcam', 65.75, 7, 1, 19, NULL, 'Full HD webcam with wide-angle lens and noise-reducing microphone.', '2025-11-12 16:00:00'),
(274, 'Creative Live! Cam', 54.99, 20, 1, 19, NULL, 'HD webcam with auto-focus and excellent color correction.', '2025-11-12 16:00:00'),
(275, 'Logitech BRIO Ultra HD', 199.99, 6, 1, 19, NULL, '4K Ultra HD webcam with HDR, ideal for professional streaming and video calls.', '2025-11-12 16:00:00'),
(276, 'AUSDOM AF720 Full HD', 89.00, 9, 1, 19, NULL, 'Full HD webcam with adjustable viewing angle and built-in stereo microphone.', '2025-11-12 16:00:00'),
(277, 'HP HD 4310 Webcam', 44.50, 13, 1, 19, NULL, 'Reliable and compact HD webcam suitable for home and office use.', '2025-11-12 16:00:00'),
(278, 'eMeet C960 HD Webcam', 69.95, 11, 1, 19, NULL, '1080p webcam with AI-enhanced video and background noise cancellation.', '2025-11-12 16:00:00'),
(279, 'Creative Sound Blaster X AE-5 Plus', 149.99, 12, 1, 14, NULL, 'High-resolution sound card with RGB lighting and advanced audio processing.', '2025-11-12 16:00:00'),
(280, 'ASUS Xonar AE', 89.50, 8, 1, 14, NULL, 'Affordable PCIe sound card with 192kHz/24-bit audio playback and recording.', '2025-11-12 16:00:00'),
(281, 'EVGA NU Audio Card', 199.99, 5, 1, 14, NULL, 'Premium audio card for audiophiles with discrete headphone amplifier.', '2025-11-12 16:00:00'),
(282, 'Sound Blaster Audigy FX', 49.99, 15, 1, 14, NULL, 'Compact sound card offering 5.1 surround sound for gaming and movies.', '2025-11-12 16:00:00'),
(283, 'ASUS Essence STX II', 229.00, 7, 1, 14, NULL, 'High-end sound card with professional-grade audio fidelity and low-noise output.', '2025-11-12 16:00:00'),
(284, 'HT OMEGA Claro II', 129.75, 10, 1, 14, NULL, 'Versatile PCIe sound card with excellent DACs for music enthusiasts.', '2025-11-12 16:00:00'),
(285, 'Creative Sound Blaster Z', 99.99, 20, 1, 14, NULL, 'Popular gaming sound card with Scout Mode for better positional audio.', '2025-11-12 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Manager'),
(3, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `name`, `description`, `created_at`) VALUES
(1, 1, 'Processor (CPU)', 'High-performance computer processors including Intel, AMD, and Apple silicon models.', '2025-11-12 16:00:00'),
(2, 1, 'Motherboard', 'Various ATX, Micro-ATX, and Mini-ITX motherboards compatible with Intel and AMD chipsets.', '2025-11-12 16:00:00'),
(3, 1, 'Graphics Card (GPU)', 'Dedicated GPUs for gaming, rendering, and AI computation from NVIDIA and AMD.', '2025-11-12 16:00:00'),
(4, 1, 'Memory (RAM)', 'DDR4 and DDR5 memory modules in different speeds and capacities for optimal performance.', '2025-11-12 16:00:00'),
(5, 1, 'Storage (SSD)', 'Solid State Drives offering high-speed and large-capacity storage.', '2025-11-12 16:00:00'),
(6, 1, 'Power Supply Unit (PSU)', 'Reliable power supplies ranging from 450W to 1200W for desktops and workstations.', '2025-11-12 16:00:00'),
(7, 1, 'Computer Case', 'Durable cases with good airflow, RGB lighting, and cable management options.', '2025-11-12 16:00:00'),
(8, 1, 'Cooling System', 'Air and liquid cooling systems to maintain optimal CPU and GPU temperatures.', '2025-11-12 16:00:00'),
(9, 1, 'Monitor', 'High-resolution monitors including 4K, ultrawide, and gaming models with fast refresh rates.', '2025-11-12 16:00:00'),
(10, 1, 'Keyboard', 'Mechanical, membrane, and wireless keyboards designed for productivity and gaming.', '2025-11-12 16:00:00'),
(11, 1, 'Mouse', 'Ergonomic and gaming mice with precision sensors and customizable buttons.', '2025-11-12 16:00:00'),
(12, 1, 'Motherboard Accessories', 'I/O shields, cables, and adapters compatible with various motherboard configurations.', '2025-11-12 16:00:00'),
(13, 1, 'Optical Drive', 'DVD and Blu-ray drives for reading and writing optical discs.', '2025-11-12 16:00:00'),
(14, 1, 'Sound Card', 'Dedicated sound cards for high-quality audio output and recording.', '2025-11-12 16:00:00'),
(15, 1, 'Networking Equipment', 'Wi-Fi adapters, Ethernet cards, and Bluetooth modules for connectivity.', '2025-11-12 16:00:00'),
(16, 1, 'Power Cables & Adapters', 'Certified power cables, surge protectors, and adapter kits for PC builds.', '2025-11-12 16:00:00'),
(17, 1, 'Operating System', 'Genuine Windows, Linux distributions, and macOS installation media.', '2025-11-12 16:00:00'),
(18, 1, 'Speakers & Headsets', 'Audio accessories for communication, gaming, and multimedia playback.', '2025-11-12 16:00:00'),
(19, 1, 'Webcam', 'High-definition webcams suitable for streaming, video calls, and conferencing.', '2025-11-12 16:00:00'),
(20, 1, 'Uninterruptible Power Supply (UPS)', 'Backup power systems to protect computers from sudden outages and surges.', '2025-11-12 16:00:00'),
(21, 1, 'Storage (HDD)', 'Hard Disk Drives offering high-speed and large-capacity storage.', '2025-11-12 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `trackers`
--

CREATE TABLE `trackers` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `details` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role_id`, `created_at`) VALUES
(1, 'Abir', 'sahaabir917@gmail.com', '5732004860', '65 N Hanover St', '$2y$10$aStlSHR15VYBRbs1o4CjCeBZM3R7YlbP801oM0WMW1IEa/Z0nZ3jG', 3, '2025-11-09 02:15:12'),
(2, 'Tahmid', 'tahmid@gmail.com', '5743434938', 'dskjfsd', '$2y$10$Ic4yFMrQySPn8S0gYvCfbuQksojVLgscgSgL2tG3BsMwGkiuWonZq', 1, '2025-11-10 19:11:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carts_user` (`user_id`),
  ADD KEY `fk_carts_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deals`
--
ALTER TABLE `deals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deal_products`
--
ALTER TABLE `deal_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_deal_products_deal` (`deal_id`),
  ADD KEY `fk_deal_products_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_payment` (`payment_id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_details_order` (`order_id`),
  ADD KEY `fk_order_details_product` (`product_id`),
  ADD KEY `fk_order_details_deal` (`deal_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`),
  ADD KEY `fk_products_subcategory` (`subcategory_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subcategories_category` (`category_id`);

--
-- Indexes for table `trackers`
--
ALTER TABLE `trackers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trackers_order` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deal_products`
--
ALTER TABLE `deal_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `trackers`
--
ALTER TABLE `trackers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `deal_products`
--
ALTER TABLE `deal_products`
  ADD CONSTRAINT `fk_deal_products_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deal_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_details_deal` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `fk_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trackers`
--
ALTER TABLE `trackers`
  ADD CONSTRAINT `fk_trackers_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
