-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 11:11 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `cart_id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `product_image_url` varchar(10000) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `available_qty`, `category_id`, `subcategory_id`, `product_image_url`, `description`, `created_at`) VALUES
(1, 'Comic book1', 399.00, 34, 2, 7, 'https://m.media-amazon.com/images/I/91PuGJyevfL._UF1000,1000_QL80_.jpg', 'sdflkds sdklfds sdfldns', '2025-11-09 02:42:48'),
(2, 'Tyson chicken wings', 5.00, 100, 3, 9, 'https://i5.walmartimages.com/seo/Tyson-Chicken-Wing-Sections-3-5-lb_f455396e-111f-4507-8e9c-1ee51751b0eb.936c2636e1f0ecb6814f60c13ef281f4.jpeg?odnHeight=573&odnWidth=573&odnBg=FFFFFF', 'kdshufkjdsnf', '2025-11-10 19:08:33'),
(3, 'Dell Inspiron 15', 599.99, 30, 1, 5, 'https://cdn.pixabay.com/photo/2017/01/06/00/45/computer-1956711_1280.png', '15-inch laptop with Intel i5 processor', '2025-11-13 21:36:05'),
(4, 'HP Pavilion 14', 649.00, 25, 1, 5, 'https://hp.widen.net/content/dw962glwuu/webp/dw962glwuu.png?w=573&h=430&dpi=72&color=ffffff00', 'Lightweight laptop for students', '2025-11-13 21:36:05'),
(5, 'Lenovo ThinkPad E14', 799.00, 20, 1, 5, 'https://cdn.cs.1worldsync.com/06/07/0607868a-6611-40fe-b9bf-404f0a4d61a5.jpg', 'Business-class durable laptop', '2025-11-13 21:36:05'),
(6, 'Apple MacBook Air M1', 999.00, 15, 1, 5, 'https://encrypted-tbn3.gstatic.com/shopping?q=tbn:ANd9GcQmmMhctKb8h1t6DF0iB7gnoid1knSv1fQyJipmF-T4hMNLg5gfnwMWstZJqhrUq1_aBnsFJutTeYNnp0p0WjD456NI8R0G', 'Ultra thin, M1 chip, long battery life', '2025-11-13 21:36:05'),
(7, 'Acer Aspire 5', 549.00, 40, 1, 5, 'https://encrypted-tbn0.gstatic.com/shopping?q=tbn:ANd9GcRhFgZEfW_a9gaviyQuC9C8G3iVhVip1aFkHoUeM6q1Me_S7JMxZO0ee6uHsRSDYh8ddawHBjsSkk2vOWGhULuzz7DwXemb', 'Affordable laptop with great performance', '2025-11-13 21:36:05'),
(8, 'One Piece Vol. 1', 9.99, 80, 2, 7, 'https://www.google.com/aclk?sa=L&ai=DChsSEwjphrPij_CQAxWwOAgFHXUuCmIYACICCAEQCRoCbWQ&ae=2&co=1&ase=2&gclid=CjwKCAiAoNbIBhB5EiwAZFbYGCsxcsmqfoj-KD-HkAq24CFZqC8A9S0l8rYxkRrAFupvwIgYenXlUxoCIHwQAvD_BwE&cid=CAAS3QHkaA5wBvH4LoskgxlbJaDkzNSu7XBUyYgZMNqaM_MIP30658cEpLD-niFeTxwBQhInlJVDA0_H28S73o4fdkA5WOYOBwTHMpArqBOQn65UDSDZZsGvae91aQsiUm146zGfaZQ2ROu3-Q7gMdyCBTSdUMMwl2MUyu5yCwbLfQVgkW4O-tVmRSFYeYHYNZjwJ4YUOgW64qHTTWhhmUYQzqkwPj8ZIyps8qqIpfu-S3g-nOyx6LBtgGhEthL0fdXnkOfz5tNG7NPhEBWvcqz6JauIILsxGgZFt6dkjAsFtw&cce=2&category=acrcp_v1_71&sig=AOD64_2UbBH2QGeHSUfGE4jErcY6VuS6bw&ctype=5&q=&nis=4&ved=2ahUKEwjajqzij_CQAxWhg4kEHbTWCSEQ9aACKAB6BAgqEDk&adurl=', 'First volume of One Piece manga', '2025-11-13 21:36:16'),
(9, 'Naruto Vol. 5', 10.99, 60, 2, 7, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRnRm939bISfvOnmpgl9S_tZ0rkCVYlOb0xw8sb-KkuclTUjwHVjufCF7wXC7tnaf217pkCkr83jK2Ln6M_bOskmniYUPNKKTdR50L7qjHP6A&s=10', 'Naruto Shippuden manga volume', '2025-11-13 21:36:16'),
(10, 'Spider-Man: Homecoming Comic', 12.99, 50, 2, 7, 'https://m.media-amazon.com/images/I/71rpf3HuQBL._AC_UF894,1000_QL80_.jpg', 'Marvel comic book', '2025-11-13 21:36:16'),
(11, 'Batman: The Killing Joke', 14.50, 40, 2, 7, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQO8jJWkiro5qLMJY0vVXx54fDuTMp9xm0um61_ln8iwyGUdrLiEhOVqzqv1qOpquSJt0Tgbg&s=10', 'Classic Batman graphic novel', '2025-11-13 21:36:16'),
(12, 'Attack on Titan Vol. 1', 11.50, 70, 2, 7, 'https://encrypted-tbn2.gstatic.com/shopping?q=tbn:ANd9GcTmW_yIBaXGX0QtN0jnJ-yimr4ycnJPtF2KEcQ3fzJgOO1JidgLMTBo5_MZXdjo3TawrJQ_ZfSfnXYmwg9BV8BSvStYkoazGPI-5t7334VX-vZqj_ahcGaw&usqp=CAc', 'Post-apocalyptic manga series', '2025-11-13 21:36:16'),
(13, 'Fresh Whole Chicken', 12.99, 50, 3, 9, 'https://i5.walmartimages.com/seo/Tyson-All-Natural-Fresh-Premium-Young-Whole-Chicken-5-0-6-5-lb_07e57e29-621b-4cdf-b85f-e0b3a1bda568.bed88e28d244302e68761094dd38ce01.jpeg', 'Fresh farm whole chicken', '2025-11-13 21:36:26'),
(14, 'Chicken Breast (Boneless)', 6.99, 100, 3, 9, 'https://eadn-wc02-16196691.nxedge.io/wp-content/uploads/2020/10/Chicken-Breast-BonelessSkinless-Antibiotic-Free.jpg', 'Lean boneless chicken breast', '2025-11-13 21:36:26'),
(15, 'Chicken Drumsticks', 4.50, 120, 3, 9, 'https://www.kroger.com/product/images/thumbnail/back/0024071850000', 'Tender chicken drumsticks', '2025-11-13 21:36:26'),
(16, 'Frozen Chicken Wings', 8.25, 90, 3, 9, 'https://greenerpastureschicken.com/cdn/shop/files/iStock-825461086_337ab434-6940-455a-ab67-7b107d62be8c.jpg?v=1708194604&width=800', 'Frozen wings for BBQ', '2025-11-13 21:36:26'),
(17, 'Chicken Thighs', 5.80, 110, 3, 9, 'https://shirttailcreekfarm.com/cdn/shop/files/Chicken_Leg_Quarter.jpg?v=1747457446&width=1800', 'Juicy chicken thighs', '2025-11-13 21:36:26');

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
(5, 1, 'Laptop', 'All brand and no brand laptop is here ', '2025-11-09 02:29:43'),
(7, 2, 'Comic', 'Comic books', '2025-11-09 02:41:31'),
(9, 3, 'Chicken', 'All Chicken wings', '2025-11-10 19:07:21');

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
(1, 'Abir', 'sahaabir917@gmail.com', '5732004860', '65 N Hanover St', '$2y$10$aStlSHR15VYBRbs1o4CjCeBZM3R7YlbP801oM0WMW1IEa/Z0nZ3jG', 1, '2025-11-09 02:15:12'),
(2, 'Tahmid', 'tahmid@gmail.com', '5743434938', 'dskjfsd', '$2y$10$Ic4yFMrQySPn8S0gYvCfbuQksojVLgscgSgL2tG3BsMwGkiuWonZq', 1, '2025-11-10 19:11:08'),
(3, 'Xiao Pu', 'xpu1s@semo.edu', '111111111111111111', '121', '$2y$10$sN1GyXsyvSI6xhDzYAIdg.LDt1K4BW24te1Fh/l4LdnJM2PHYXx/W', 1, '2025-11-13 21:30:43');

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
  ADD KEY `fk_orders_cart` (`cart_id`),
  ADD KEY `fk_orders_payment` (`payment_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deal_products`
--
ALTER TABLE `deal_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trackers`
--
ALTER TABLE `trackers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `fk_orders_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON UPDATE CASCADE;

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
