-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 11 avr. 2026 à 17:34
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projet_pfa`
--

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `password` varchar(70) NOT NULL,
  `email` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `password`, `email`, `name`) VALUES
(1, '$2y$10$E5Ti7MaW3dkfkMm0TGQ8yuPzlRA9ml3sJZgA0p9iDDw0LxWEJYew.', 'ayoub@gmail.com', 'Ayoub Ramchani'),
(2, '$2y$10$3jK2vhQ06UgJHFYDSYqVoeTaCW6JTzR1EK..RKzt.mWfx2xAoaXjC', 'akram@gmail.com', 'Akram Bel Hadj Yahia'),
(3, '$2y$10$Tjyfm21Mtg.4NMjxfxTvZOHy1noKm3Uae3Mzcxmkm.LAKzerHXbRW', 'seif@gmail.com', 'seif Handous'),
(4, '$2y$10$ZvQZaoMWKti45PuusLahwOn7mghrc4V7FeeYo4d2xjXjHfLig/RLm', 'rayen@gmail.com', 'rayen'),
(5, '$2y$10$6lrKFmvPyJ6YeEv9RtWuD.FNN8yq57hXucJKlfzY7WNYwPO1jSzma', 'aa@gmail.com', 'aa');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
