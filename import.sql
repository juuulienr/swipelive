-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 15 oct. 2021 à 19:00
-- Version du serveur :  5.7.32
-- Version de PHP : 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données : `wizzlive`
--

-- --------------------------------------------------------

--
-- Structure de la table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `category`
--

INSERT INTO `category` (`id`, `name`, `picture`) VALUES
(1, 'Soins & Beauté', '5f35c803603d997aed6d06ca_icon-bath-beauty.svg'),
(2, 'Bijoux & Sacs', NULL),
(3, 'Jouets', NULL),
(4, 'Accessoires', NULL),
(5, 'Mode Homme', NULL),
(6, 'Mode Femme', NULL),
(7, 'Enfants & Bébés', NULL),
(8, 'Sport & Fitness', '5f35c8029bbed6c643548f70_icon-sports-outdoors.svg'),
(9, 'Electronique', NULL),
(10, 'Décoration', NULL),
(11, 'Autres', NULL);

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
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int(11) NOT NULL,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clip`
--

INSERT INTO `clip` (`id`, `vendor_id`, `live_id`, `start`, `end`, `duration`, `filename`, `product_id`, `thumbnail`) VALUES
(5, 1, 1, 0, 8, 8, '1c8311f0-13bd-bec5-3073-757cfd-9c0ab3b07e54591904a0ac447321ee79.mp4', 2, 'thumbnail.png'),
(6, 1, 1, 9, 18, 8, '1c8311f0-13bd-bec5-3073-757cfd-3c115b544603538f2d66f13aa1bf029e.mp4', 1, 'thumbnail2.png');

-- --------------------------------------------------------

--
-- Structure de la table `follow`
--

CREATE TABLE `follow` (
  `id` int(11) NOT NULL,
  `following_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Structure de la table `live_product`
--

CREATE TABLE `live_product` (
  `live_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Structure de la table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `online` tinyint(1) NOT NULL,
  `price` double NOT NULL,
  `compare_at_price` double DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `tracking` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `product`
--

INSERT INTO `product` (`id`, `category_id`, `vendor_id`, `name`, `description`, `online`, `price`, `compare_at_price`, `quantity`, `tracking`) VALUES
(1, 8, 1, 'Tapis de yoga', 'Un tapis léger et au toucher velours, vous pouvez emmener ce tapis dans tous vos cours de yoga\r\n\r\nLa douceur de ce tapis vous invite à un rendez-vous régulier avec votre pratique. Léger, il vous accompagne de la maison au studio. Ses marquages discrets vous guident vers une meilleure posture.', 1, 25, 30, 50, 1),
(2, 1, 1, 'Figue de Barbarie', 'Laissez-vous envoûter par les pouvoirs revitalisants et protecteurs de l’huile de Figue de Barbarie ! \r\n\r\nLa gamme à l’huile de Figue de Barbarie de Youarda.C est un véritable concentré de bienfaits pour vos cheveux, elle nourrit votre cuir chevelu en profondeur, favorise la micro-circulation et hydrate le cheveu.\r\n\r\n', 1, 39.9, 49.9, 23, 1);

-- --------------------------------------------------------

--
-- Structure de la table `upload`
--

CREATE TABLE `upload` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `upload`
--

INSERT INTO `upload` (`id`, `product_id`, `filename`, `created_at`) VALUES
(1, 1, '5f35c803603d997aed6d06ca.jpeg', '2021-10-15 09:34:34'),
(2, 1, 'pe35c803603d997aed6d236ca.jpeg', '2021-10-15 09:36:18'),
(3, 2, 'untitled-project-copy-copy-copy_2x_6_2000x.png', '2021-10-15 11:30:22'),
(4, 2, 'kit-soins-figue-de-barbarie-1_2000x.jpeg', '2021-10-15 11:30:25');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `push_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO `vendor` (`id`, `email`, `hash`, `push_token`, `created_at`, `company`, `firstname`, `lastname`, `summary`, `picture`, `facebook`, `instagram`, `snapchat`, `pinterest`) VALUES
(1, 'julienreignierr@gmail.com', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-06 11:11:06', 'Julien SAS', 'Julien', 'REIGNIER', 'Boutique de jeux vidéos et accessoires', '1a058cfb9ea95f0acd4fedd647d5bb40.jpg', 'juuulienr', 'juuulienr', 'juuulienr', 'juuulienr'),
(2, 'c.cheklat@yahoo.fr', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-13 13:53:21', 'CHEMS SAS', 'Chems eddine', 'Cheklat', 'Boutique dans la vente de cigarette electronique et cbd', 'ad5c0d1afe40f225779afda26ff9d4a6.jpg', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clip`
--
ALTER TABLE `clip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_AD201467F603EE73` (`vendor_id`),
  ADD KEY `IDX_AD2014671DEBA901` (`live_id`),
  ADD KEY `IDX_AD2014674584665A` (`product_id`);

--
-- Index pour la table `follow`
--
ALTER TABLE `follow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_683444701816E3A3` (`following_id`),
  ADD KEY `IDX_68344470F603EE73` (`vendor_id`),
  ADD KEY `IDX_68344470A76ED395` (`user_id`);

--
-- Index pour la table `live`
--
ALTER TABLE `live`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_530F2CAFF603EE73` (`vendor_id`);

--
-- Index pour la table `live_product`
--
ALTER TABLE `live_product`
  ADD PRIMARY KEY (`live_id`,`product_id`),
  ADD KEY `IDX_529744D61DEBA901` (`live_id`),
  ADD KEY `IDX_529744D64584665A` (`product_id`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B6BD307F1DEBA901` (`live_id`),
  ADD KEY `IDX_B6BD307FA76ED395` (`user_id`),
  ADD KEY `IDX_B6BD307FF603EE73` (`vendor_id`);

--
-- Index pour la table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D34A04AD12469DE2` (`category_id`),
  ADD KEY `IDX_D34A04ADF603EE73` (`vendor_id`);

--
-- Index pour la table `upload`
--
ALTER TABLE `upload`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_17BDE61F4584665A` (`product_id`);

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
-- AUTO_INCREMENT pour la table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `clip`
--
ALTER TABLE `clip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `follow`
--
ALTER TABLE `follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT pour la table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `upload`
--
ALTER TABLE `upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `FK_AD2014674584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `FK_AD201467F603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `follow`
--
ALTER TABLE `follow`
  ADD CONSTRAINT `FK_683444701816E3A3` FOREIGN KEY (`following_id`) REFERENCES `vendor` (`id`),
  ADD CONSTRAINT `FK_68344470A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_68344470F603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `live`
--
ALTER TABLE `live`
  ADD CONSTRAINT `FK_530F2CAFF603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `live_product`
--
ALTER TABLE `live_product`
  ADD CONSTRAINT `FK_529744D61DEBA901` FOREIGN KEY (`live_id`) REFERENCES `live` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_529744D64584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `FK_B6BD307F1DEBA901` FOREIGN KEY (`live_id`) REFERENCES `live` (`id`),
  ADD CONSTRAINT `FK_B6BD307FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_B6BD307FF603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `FK_D34A04AD12469DE2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `FK_D34A04ADF603EE73` FOREIGN KEY (`vendor_id`) REFERENCES `vendor` (`id`);

--
-- Contraintes pour la table `upload`
--
ALTER TABLE `upload`
  ADD CONSTRAINT `FK_17BDE61F4584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`);
