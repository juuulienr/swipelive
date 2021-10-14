-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : jeu. 14 oct. 2021 à 12:10
-- Version du serveur :  5.7.32
-- Version de PHP : 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données : `wizzlive`
--

-- --------------------------------------------------------

--
-- Structure de la table `clip`
--

CREATE TABLE `clip` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `live_id` int(11) NOT NULL,
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clip`
--

INSERT INTO `clip` (`id`, `vendor_id`, `live_id`, `start`, `end`, `duration`, `filename`) VALUES
(1, 1, 1, 0, 8, 8, '1c8311f0-13bd-bec5-3073-757cfd-9c0ab3b07e54591904a0ac447321ee79.mp4'),
(2, 1, 1, 9, 18, 8, '1c8311f0-13bd-bec5-3073-757cfd-3c115b544603538f2d66f13aa1bf029e.mp4');

-- --------------------------------------------------------

--
-- Structure de la table `live`
--

CREATE TABLE `live` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `broadcast_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `views` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `live`
--

INSERT INTO `live` (`id`, `vendor_id`, `broadcast_id`, `views`) VALUES
(1, 1, '937f149b-a835-4497-9398-b62e15553698', 5);

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `id` int(11) NOT NULL,
  `live_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `message`
--

INSERT INTO `message` (`id`, `live_id`, `user_id`, `vendor_id`, `type`, `created_at`, `content`) VALUES
(1, 1, NULL, 1, 1, '2021-10-06 12:25:10', 'bienvenue'),
(2, 1, NULL, 2, 1, '2021-10-06 13:25:10', 'excellent');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `push_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `hash`, `push_token`, `created_at`) VALUES
(1, 'julienreignierr@gmail.com', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-06 09:46:46');

-- --------------------------------------------------------

--
-- Structure de la table `vendor`
--

CREATE TABLE `vendor` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `push_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `followers` int(11) NOT NULL,
  `following` int(11) NOT NULL,
  `summary` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapchat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pinterest` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vendor`
--

INSERT INTO `vendor` (`id`, `email`, `hash`, `push_token`, `created_at`, `company`, `firstname`, `lastname`, `followers`, `following`, `summary`, `picture`, `facebook`, `instagram`, `snapchat`, `pinterest`) VALUES
(1, 'julienreignierr@gmail.com', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-06 11:11:06', 'Julien SAS', 'Julien', 'Reignier', 65, 15, 'Boutique de vente de jeux vidéos', '1a058cfb9ea95f0acd4fedd647d5bb40.jpg', 'juuulienr', 'juuulienr', 'juuulienr', 'juuulienr'),
(2, 'c.cheklat@yahoo.fr', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-13 13:53:21', 'CHEMS SAS', 'Chems eddine', 'Cheklat', 649, 143, 'Boutique dans la vente de cigarette electronique', 'ad5c0d1afe40f225779afda26ff9d4a6.jpg', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe'),
(7, 'testtest@gmail.com', '$2y$13$HWbgaM1dVM2VNueW.weO1OEs0hJRbFu5jiTPKs9nueuPTRvPYsEG.', NULL, '2021-10-14 10:59:22', 'test@gmail.com', 'test', 'test', 0, 0, 'test@gmail.com', NULL, '', '', '', '');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clip`
--
ALTER TABLE `clip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_AD201467F603EE73` (`vendor_id`),
  ADD KEY `IDX_AD2014671DEBA901` (`live_id`);

--
-- Index pour la table `live`
--
ALTER TABLE `live`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_530F2CAFF603EE73` (`vendor_id`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B6BD307F1DEBA901` (`live_id`),
  ADD KEY `IDX_B6BD307FA76ED395` (`user_id`),
  ADD KEY `IDX_B6BD307FF603EE73` (`vendor_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clip`
--
ALTER TABLE `clip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `live`
--
ALTER TABLE `live`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `clip`
--
ALTER TABLE `clip`
  ADD CONSTRAINT `FK_AD2014671DEBA901` FOREIGN KEY (`live_id`) REFERENCES `live` (`id`),
  ADD CONSTRAINT `FK_AD201467F603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `live`
--
ALTER TABLE `live`
  ADD CONSTRAINT `FK_530F2CAFF603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `FK_B6BD307F1DEBA901` FOREIGN KEY (`live_id`) REFERENCES `live` (`id`),
  ADD CONSTRAINT `FK_B6BD307FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_B6BD307FF603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);
