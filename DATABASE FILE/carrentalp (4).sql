-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 16 août 2025 à 03:06
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `carrentalp`
--

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `return_date` date NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `car_id`, `pickup_date`, `return_date`, `pickup_location`, `notes`, `total_amount`, `booking_date`, `status`) VALUES
(1, 2, 5, '2025-08-16', '2025-08-27', 'bizerte', 'd', 990.00, '2025-08-16 01:02:21', 'pending');

-- --------------------------------------------------------

--
-- Structure de la table `booking_documents`
--

CREATE TABLE `booking_documents` (
  `document_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `booking_documents`
--

INSERT INTO `booking_documents` (`document_id`, `booking_id`, `document_name`, `document_path`, `uploaded_at`) VALUES
(1, 1, 'cin', 'uploads/booking_documents/booking_1_689fd93128824.jpg', '2025-08-16 01:04:49');

-- --------------------------------------------------------

--
-- Structure de la table `cars`
--

CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL,
  `car_name` varchar(255) NOT NULL,
  `car_type` varchar(100) NOT NULL,
  `fuel_type` varchar(100) NOT NULL,
  `car_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `car_availability` enum('yes','no') DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cars`
--

INSERT INTO `cars` (`car_id`, `car_name`, `car_type`, `fuel_type`, `car_image`, `price`, `car_availability`) VALUES
(5, 'Mercedes C-Class', 'mercedes', 'Diesel', 'assets/img/cars/689fd7e9f3159.jpg', 90.00, 'yes');

-- --------------------------------------------------------

--
-- Structure de la table `car_documents`
--

CREATE TABLE `car_documents` (
  `document_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `vignette_date` date DEFAULT NULL,
  `technical_check_date` date DEFAULT NULL,
  `insurance_date` date DEFAULT NULL,
  `oil_change_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `car_documents`
--

INSERT INTO `car_documents` (`document_id`, `car_id`, `vignette_date`, `technical_check_date`, `insurance_date`, `oil_change_date`) VALUES
(5, 5, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `car_images`
--

CREATE TABLE `car_images` (
  `image_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `car_images`
--

INSERT INTO `car_images` (`image_id`, `car_id`, `image_path`) VALUES
(39, 5, 'assets/img/cars/689fd7e9f3159.jpg'),
(40, 5, 'assets/img/cars/689fd7e9f32cc.jpg'),
(41, 5, 'assets/img/cars/689fd7e9f33ec.jpg'),
(42, 5, 'assets/img/cars/689fd7e9f3565.jpg'),
(43, 5, 'assets/img/cars/689fd7e9f3682.jpg'),
(44, 5, 'assets/img/cars/689fd7e9f377c.jpg'),
(45, 5, 'assets/img/cars/689fd7e9f3869.jpg'),
(46, 5, 'assets/img/cars/689fd7e9f395d.jpg'),
(47, 5, 'assets/img/cars/689fd7e9f3a52.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `car_maintenance`
--

CREATE TABLE `car_maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `invoice_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enquiries`
--

INSERT INTO `enquiries` (`id`, `name`, `email`, `subject`, `message`, `phone`, `created_at`) VALUES
(1, 'user', 'user@gmail.com', 'Demande d\'informations pour l\'enregistrement d\'un domaine .tn et hébergement d\'un site web Laravel', 'feffre', '12345678', '2025-08-16 01:05:22');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','guest') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`user_id`, `username`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'firas', 'firas baklouti', 'firasbaklouti39@gmail.com', 'firasbaklouti39@gmai', '$2y$10$4A5QCUGpddLTWgBY2Z9rMOtxYPTw7TKvoDRBCnXTdsnamVe3KvgFW', 'admin', '2025-08-15 02:15:57'),
(2, 'user', 'user', 'user@gmail.com', '12345678', '$2y$10$pQGVXj.y9JR8PhEJmQ1xTewSUssvxr12/WSzs/8YMYwxfCBMiJqy.', 'user', '2025-08-16 01:00:37'),
(3, 'guest', 'guest', 'guest@gmail.com', '53515766', '$2y$10$xhDZ.xKjuq8h.1Ow/bjS0.rEWhKraeqaFtIio8IzcXvsyASkLRxYi', 'guest', '2025-08-16 01:01:42');

-- --------------------------------------------------------

--
-- Structure de la table `user_documents`
--

CREATE TABLE `user_documents` (
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `booking_documents`
--
ALTER TABLE `booking_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fk_booking_documents_booking` (`booking_id`);

--
-- Index pour la table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`car_id`);

--
-- Index pour la table `car_documents`
--
ALTER TABLE `car_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `car_images`
--
ALTER TABLE `car_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `car_maintenance`
--
ALTER TABLE `car_maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Index pour la table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_documents`
--
ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `booking_documents`
--
ALTER TABLE `booking_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `cars`
--
ALTER TABLE `cars`
  MODIFY `car_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `car_documents`
--
ALTER TABLE `car_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `car_images`
--
ALTER TABLE `car_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `car_maintenance`
--
ALTER TABLE `car_maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `user_documents`
--
ALTER TABLE `user_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `booking_documents`
--
ALTER TABLE `booking_documents`
  ADD CONSTRAINT `fk_booking_documents_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `car_documents`
--
ALTER TABLE `car_documents`
  ADD CONSTRAINT `car_documents_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `car_images`
--
ALTER TABLE `car_images`
  ADD CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `car_maintenance`
--
ALTER TABLE `car_maintenance`
  ADD CONSTRAINT `car_maintenance_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_documents`
--
ALTER TABLE `user_documents`
  ADD CONSTRAINT `user_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
