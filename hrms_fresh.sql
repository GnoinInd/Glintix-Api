-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2024 at 04:50 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hrms_fresh`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `project_leader` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2016_06_01_000001_create_oauth_auth_codes_table', 1),
(4, '2016_06_01_000002_create_oauth_access_tokens_table', 1),
(5, '2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
(6, '2016_06_01_000004_create_oauth_clients_table', 1),
(7, '2016_06_01_000005_create_oauth_personal_access_clients_table', 1),
(8, '2019_08_19_000000_create_failed_jobs_table', 1),
(9, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(10, '2023_07_25_121147_create_clients_table', 2),
(11, '2023_12_22_115919_create_super_users_table', 3),
(12, '2023_12_26_065008_create_super_users_table', 4),
(13, '2023_12_27_114156_create_user_otps_table', 4);

-- --------------------------------------------------------

--
-- Table structure for table `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('09d42465b01c1410a283e80d8cc977ed51a6f8ea41eeb5ae5854e9108c4e7d92eb7f1db7b6ac2d5c', 1, 1, 'token_key', '[]', 0, '2023-07-24 05:45:21', '2023-07-24 05:45:21', '2024-07-24 11:15:21'),
('13e07e8080aa559223bf8a324968963bc0354fca89904ef000dcea6330b3d3923fa63dc8ab777174', 3, 1, 'token_key', '[]', 0, '2023-07-25 03:06:05', '2023-07-25 03:06:05', '2024-07-25 08:36:05'),
('1eefb84d159f4f7d5685407f031a5d37dbff7ece34a22a284e168038db2eb6d82c17eed21a2c83e2', 3, 1, 'token_key', '[]', 0, '2023-07-25 02:07:39', '2023-07-25 02:07:39', '2024-07-25 07:37:39'),
('3e0162e0c64b7d4788540f18719162f29722a5b30191dbd75c37705e6e824fab3ec4f508bf9fcded', 1, 1, 'access_token', '[]', 0, '2024-01-02 08:41:52', '2024-01-02 08:41:52', '2025-01-02 14:11:52'),
('49879584ad3e1c2210fbf446c260b238cc96bae978091d1562e3b206f75738cc9b553b3f32974acd', 2, 1, 'token_key', '[]', 0, '2023-07-24 04:22:13', '2023-07-24 04:22:14', '2024-07-24 09:52:13'),
('4c92c807fef339806db1a4a60652c3bc8a929df45bd6d7c062526c88fd8c89a2e2dd44440f9efcc7', 6, 1, 'token_key', '[]', 0, '2023-07-24 23:24:27', '2023-07-24 23:24:27', '2024-07-25 04:54:27'),
('5794baab7c4462dc1a4eac04d7726cb328d446b96fd60efa596a84471f79122d166286e96d60679d', 3, 1, 'token_key', '[]', 0, '2023-07-25 01:04:46', '2023-07-25 01:04:47', '2024-07-25 06:34:46'),
('6cbde40c7b0c2d044c09d311e608d0e79db0b4d209c38fb7ab245122bddbb550d33bbb92775b29f4', 6, 1, 'token_key', '[]', 0, '2023-07-24 23:25:05', '2023-07-24 23:25:05', '2024-07-25 04:55:05'),
('84012d36fade7024fe049e9ac33d7ab87e517623aaa601c8ef98df2578c03d2d3ecd87206b87f44f', 3, 1, 'token_key', '[]', 0, '2023-07-25 03:47:20', '2023-07-25 03:47:20', '2024-07-25 09:17:20'),
('896236d2dbd8c5a0fa40bcbd0e62fbe45c0df718890c2002f91037963652b35f044b0c9f01125d83', 4, 1, 'token_key', '[]', 0, '2023-07-24 05:47:41', '2023-07-24 05:47:41', '2024-07-24 11:17:41'),
('acfdaa467544a180e68ab9e46a1971f2a604ed77b2ba9f028f679945e06ca48935b1124c55a82827', 2, 1, 'token_key', '[]', 0, '2023-07-24 05:54:50', '2023-07-24 05:54:50', '2024-07-24 11:24:50'),
('c51f7ade9a2f9d5ecb386bba3af33c7a87ea2253c61568bf238823e109984a53de414fb6a3057bfa', 3, 1, 'token_key', '[]', 0, '2023-07-24 05:01:39', '2023-07-24 05:01:39', '2024-07-24 10:31:39'),
('de31e3ea8b33b07098953b53a1df4d69c29b55f743098039c9b99d97f57736a21be7476d82eea5de', 1, 1, 'token_key', '[]', 0, '2023-07-24 03:54:48', '2023-07-24 03:54:48', '2024-07-24 09:24:48'),
('e327b291a4aa839449318ed2482d9a16a8a122f93b05c87b9567c13dfc6b70ecdf6f0d94ef33ab98', 3, 1, 'token_key', '[]', 0, '2023-07-25 00:28:28', '2023-07-25 00:28:29', '2024-07-25 05:58:28'),
('fba6cf50307795f725ca99d367ee43f5bb50e5e0d687032c3a5e4ed0b3956e58c793c5fcc7b3dbb9', 6, 1, 'token_key', '[]', 0, '2023-07-24 23:23:32', '2023-07-24 23:23:33', '2024-07-25 04:53:32'),
('fe3930eafca18f3b4c634be0bc81a584e1abe5c2f0544d9eb20c928760fed7d352c20470a5916b47', 1, 1, 'token_key', '[]', 0, '2023-07-24 05:49:41', '2023-07-24 05:49:41', '2024-07-24 11:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `user_id`, `name`, `secret`, `provider`, `redirect`, `personal_access_client`, `password_client`, `revoked`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Laravel Personal Access Client', 'McGM6NsisoNsBLpj3sGEzoKAQ8wuOxl0JP4GO6qz', NULL, 'http://localhost', 1, 0, 0, '2023-07-24 02:42:01', '2023-07-24 02:42:01'),
(2, NULL, 'Laravel Password Grant Client', 'aHCiEeSygPNuLpLM7D0eYDOSVWIVQcpMiV4ye9fj', 'users', 'http://localhost', 0, 1, 0, '2023-07-24 02:42:01', '2023-07-24 02:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_personal_access_clients`
--

CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oauth_personal_access_clients`
--

INSERT INTO `oauth_personal_access_clients` (`id`, `client_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2023-07-24 02:42:01', '2023-07-24 02:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('gemsfiem@gmail.com', 'kGIC9eH6blyy2eAORQoYiLHczQkrZi', '2023-08-14 03:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\SuperUser', 1, 'access_token', '759bcefe0ebee7063090cb917c4ad9e69f64dc1447f3dba2ace1e8a71f111c53', '[\"*\"]', '2024-01-03 05:58:38', '2024-01-03 05:56:39', '2024-01-03 05:58:38'),
(2, 'App\\Models\\SuperUser', 1, 'auth-token', '39975ce1b834879e318ed30e6c45f3291f7740a9efcce80c3d37c57efffbe87b', '[\"custom-scope\"]', NULL, '2024-01-04 06:11:33', '2024-01-04 06:11:33'),
(3, 'App\\Models\\SuperUser', 1, 'auth-token', '585274de990151d6054c59d42b78e686c49a2011faea691ddfff83fc589789ef', '[\"custom-scope\"]', NULL, '2024-01-04 06:14:00', '2024-01-04 06:14:00'),
(4, 'App\\Models\\SuperUser', 1, 'auth-token', 'be32104aaafb1c65038779ea4627f9133684b19bd4dae5d5e81a7bf2f157cb35', '[\"custom-scope\"]', NULL, '2024-01-04 06:15:19', '2024-01-04 06:15:19'),
(5, 'App\\Models\\SuperUser', 1, 'access-token', 'e169ad0d6241cf8425161bbf161a782c45a6c8b44e45559e65304eb3e47e1958', '[\"*\"]', NULL, '2024-01-05 02:33:41', '2024-01-05 02:33:41'),
(6, 'App\\Models\\SuperUser', 1, 'access-token', 'ad7b743503f78db4c6cac74b1034ccdff2d3593469dadf6d61e1e9de78956499', '[\"*\"]', '2024-01-05 02:52:11', '2024-01-05 02:34:48', '2024-01-05 02:52:11'),
(8, 'App\\Models\\SuperUser', 1, 'access-token', 'c71d29e545a7f9385239bad86eca0514225975cdb75a3ba283c97b8d0a016072', '[\"*\"]', NULL, '2024-01-05 02:55:12', '2024-01-05 02:55:12'),
(10, 'App\\Models\\SuperUser', 1, 'access-token', '59f51fdf7fe9571aaee59856127f1e804a2fbd02bc17cd94c0b8366b88bb9155', '[\"*\"]', '2024-01-05 04:21:36', '2024-01-05 04:18:53', '2024-01-05 04:21:36');

-- --------------------------------------------------------

--
-- Table structure for table `super_users`
--

CREATE TABLE `super_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` enum('root','super user') NOT NULL DEFAULT 'root',
  `time_expire` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_users`
--

INSERT INTO `super_users` (`id`, `name`, `username`, `password`, `email`, `phone`, `role`, `time_expire`, `created_at`, `updated_at`) VALUES
(1, 'Gnoin Pvt.Ltd', 'gnoin', '$2y$10$pDeHauc1Gdq5W44lpBmL9O048HuntawFFm1vA3rvdXux26arJCdau', 'gemsfiem@gmail.com', '+917699456004', 'root', '2024-01-05 04:29:52', '2023-12-27 06:22:15', '2024-01-05 04:28:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `dbName` varchar(255) DEFAULT NULL,
  `role` enum('Super Admin','Admin','User') NOT NULL DEFAULT 'Super Admin',
  `status` enum('Inactive','Active') NOT NULL DEFAULT 'Active',
  `total` varchar(255) DEFAULT NULL,
  `company_code` varchar(255) DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `expire_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `contact_person`, `address`, `country`, `state`, `postal_code`, `mobile_number`, `fax`, `website_url`, `company_logo`, `email`, `email_verified_at`, `username`, `password`, `dbName`, `role`, `status`, `total`, `company_code`, `otp`, `expire_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Golam Gous', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'golam@gmail.com', NULL, 'abcdef', '$2y$10$GtoTPgK9gN8.6gRoP/jbIuWlmZMkPxYxTdagfFv3QlTkUVmf/Pvre', '', '', '', NULL, NULL, NULL, NULL, NULL, '2023-07-24 03:54:47', '2023-08-11 04:53:10'),
(35, 'Tcs software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tcs@gmail.com', NULL, NULL, '$2y$10$34C.WbOvbRwvbVwcUkLeyubcOiV1jkEykqKDjM49Mcn46Cjq7L1He', 'tcs_CDTC31X7', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-07-27 04:52:26', '2023-07-27 04:52:26'),
(36, 'Redcat', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'red@gmail.com', NULL, NULL, '$2y$10$oapXkj1.90dIforPaLxkPOAeRgZA0q1WbeK6CUBBJvx2YcvEXuUmG', 'redcat_ZPXHgnj0', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-07-27 05:47:06', '2023-07-27 05:47:06'),
(37, 'cognigent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cogni@gmail.com', NULL, NULL, '$2y$10$5edsZLJxqa6AjjwMpBrpAuFLAbLHtSBng8e/K41k1LztONr4dwjWK', 'cts_4OGhvx0g', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-07-28 05:24:22', '2023-07-28 05:24:22'),
(54, 'Gnoin pvt.ltd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gnon@gmail.com', NULL, NULL, '$2y$10$vn7rKVUSuLIylU2QTxaAqeUWLRBY2pecgho.4XcWsauZUXbK5v2Xi', 'gnoin', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-01 06:27:09', '2023-08-01 06:27:09'),
(63, 'Tcs Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tcs123@gmail.com', NULL, 'tcsemployee', '$2y$10$Zusnrcf40WxOGAw47tJPEOxSEU7C6kIOzzIbWk7CzcZh9Uk6m1Xla', 'tcs_1234', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-02 06:41:04', '2023-08-02 06:41:04'),
(66, 'wipro Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'wipro123@gmail.com', NULL, 'wiproemployee', '$2y$10$HAA2LUVfVucOIaZWT4bc3.Von/8EdeGoyavc/W4bahEYXvNjY6wLC', 'wipro_1234', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-04 02:05:02', '2023-08-04 02:05:02'),
(78, 'infosys Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info123@gmail.com', NULL, 'infoemployee', '$2y$10$kutsOAXZ4k7aCePOIyKX4ei3Ac2kN97nFM0cj0Mkp4oMMNwhZpMCG', 'infosys_1234', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-04 04:49:10', '2023-08-04 04:49:10'),
(91, 'accenture Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'accenture@gmail.com', NULL, 'accemployee', '$2y$10$WiYv9Z6kO.OwMOhpQrKKQemj3ISEfGvCEhT6dmwQStguMlExYlHXm', 'accenture_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-07 02:11:35', '2023-08-07 02:11:35'),
(92, 'RedhatSoftware', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'redhat@gmail.com', NULL, 'redhatemp', '$2y$10$oqMJ6aGEzScRqGCuYXuU5.VBr9ZTGF/xN1aZyFBIyarEHFqapJeBW', 'redhat_1234', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-08 01:35:55', '2023-08-08 01:35:55'),
(93, 'Softcode Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'soft@gmail.com', NULL, 'softcode', '$2y$10$7ie7dWLqHazZU5PdQhtFlOnLbiDUF9DSYYuoEgTMP7FC5Ru7Y9hxO', 'softcode_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-08 23:58:51', '2023-08-08 23:58:51'),
(94, 'Softcode Software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'soft123@gmail.com', NULL, 'softcode', '$2y$10$E45CIq/Hdp33Vxvt24WBQegSXIDcyUvHczmDNhseWYKtbjdJKTqC6', 'softcode_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-09 00:02:22', '2023-08-09 00:02:22'),
(95, 'it worldSoftware', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'it@gmail.com', NULL, 'itworld', '$2y$10$QY2J0TCHjc7RE69fNWUxre1InULrF68580jLoUhepGa3NNldxNvdK', 'Itworld_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-09 00:08:31', '2023-08-09 00:08:31'),
(96, 'infinity software', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gemsfiem@gmail.com', NULL, 'infinityemp', '$2y$10$blgi4IDRGEVo8gD5azNI1OHha3l0g7Yh.jud0ygx8MqQpBrvCQhQO', 'Infinity_1234', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-08-10 05:36:11', '2023-08-14 01:34:36'),
(100, 'goodrej Company', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'goodrej@gmail.com', NULL, 'goodrej', '$2y$10$tADrnYe29w1vkZi6G4by2e/oUiIIKmfH/.llZCOL/E17nK6P.XJoO', 'goodrej_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-10-31 08:17:34', '2023-10-31 08:17:34'),
(101, 'dearCompany', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dear@gmail.com', NULL, 'dear', '$2y$10$kg2UDabLY1Oia6tUZSgucuiNb62MC1amGaPfZ/tZsaGZ3HKDdEQEq', 'dear', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-11-18 04:11:36', '2023-11-18 04:11:36'),
(119, 'boat company', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'boat@gmail.com', NULL, 'boat', '$2y$10$hcGHUgkDocOofGLDs35lm.CCfxsP.qzIV4nFYzajdxnFU3IdXhQd6', 'boat_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-12-04 23:59:49', '2023-12-04 23:59:49'),
(124, 'maruti company', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'maruti@gmail.com', NULL, 'maruti', '$2y$10$1cHDY2ftJbVKfI7XeC7LHexX9IyHEATnPjHNChaOV4Rhd3ac/OzVO', 'maruti_123', 'Super Admin', 'Active', '50', NULL, NULL, NULL, NULL, '2023-12-05 00:45:42', '2023-12-05 00:45:42'),
(137, 'birla company', '7867564534', 'kolkata-100', 'india', 'west-bengal', '700100', '6765432123', 'fax100', 'www.birla.com', NULL, 'birla@gmail.com', NULL, 'birla', '$2y$10$Zqa9fpta8LjR9yELFyKUcuvN6EPA8G1TzjFSLhBu/ZmMoZahTGjZy', 'birla_123', 'Super Admin', 'Active', '25', NULL, NULL, NULL, NULL, '2023-12-24 11:02:10', '2023-12-24 11:02:10'),
(138, 'godrej', '7867564534', 'kolkata-100', 'india', 'west-bengal', '700100', '6765432123', 'fax100', 'www.birla.com', NULL, 'godrej@gmail.com', NULL, 'godrej', '$2y$10$RGeSVWeRneY06IcvhPMfG.8.T7j6yQQEMbB39qXB3/LOuuOUeFJHG', 'godrej_123', 'Super Admin', 'Active', '25', NULL, NULL, NULL, NULL, '2023-12-24 11:21:12', '2023-12-24 11:21:12'),
(139, 'zoho company', '7867564534', 'kolkata-100', 'india', 'west-bengal', '700100', '6765432123', 'fax100', 'www.zoho.com', NULL, 'zoho@gmail.com', NULL, 'zoho', '$2y$10$6cGnH.F7gHazS/ch6KGTruVPo66D2sUG/1Y1MxdcdOPYD5iq2Fv8G', 'zoho_123', 'Super Admin', 'Active', '25', NULL, NULL, NULL, NULL, '2023-12-26 01:28:57', '2023-12-26 01:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_otps`
--

CREATE TABLE `user_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `otp` varchar(255) NOT NULL,
  `expire_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`);

--
-- Indexes for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `super_users`
--
ALTER TABLE `super_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `super_users_email_unique` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_otps`
--
ALTER TABLE `user_otps`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `super_users`
--
ALTER TABLE `super_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `user_otps`
--
ALTER TABLE `user_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
