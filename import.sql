-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 14 déc. 2021 à 11:24
-- Version du serveur :  5.7.32
-- Version de PHP : 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données : `swipelive`
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
(1, 'Soins & Beauté', 'soins-beaute.svg'),
(2, 'Bijoux', 'bijoux.svg'),
(4, 'Accessoires', 'accessoires.svg'),
(5, 'Mode Homme', 'mode-homme.svg'),
(6, 'Mode Femme', 'mode-femme.svg'),
(7, 'Enfants & Bébés', 'enfants-bebes.svg'),
(8, 'Sport', 'sports.svg'),
(9, 'Electronique', 'electroniques.svg'),
(10, 'Décoration', 'decorations.svg'),
(11, 'Autres', 'autres.svg'),
(12, 'Alimentation', 'alimentation.svg');

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
(5, 1, 32, 0, 8, 8, '1c8311f0-13bd-bec5-3073-757cfd-9c0ab3b07e54591904a0ac447321ee79.mp4', 2, 'thumbnail.jpg'),
(6, 1, 32, 9, 18, 8, '1c8311f0-13bd-bec5-3073-757cfd-3c115b544603538f2d66f13aa1bf029e.mp4', 1, 'thumbnail2.jpg'),
(7, 2, 36, 0, 34, 34, '1c8311f0-13bd-bec5-3073-757cfd-20d6bc697bd6327518e8ae4ada50d44f.mp4', 10, 'c8a24b07-df04-4919-a3a2-748221b55dcc.jpg'),
(8, 2, 38, 0, 9, 9, '1c8311f0-13bd-bec5-3073-757cfd-a2369850f1cff9187ead37995bc8668d.mp4', 9, '4653f0ac-5042-4c11-992f-c664dd200c05.jpg');

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

--
-- Déchargement des données de la table `follow`
--

INSERT INTO `follow` (`id`, `following_id`, `vendor_id`, `user_id`) VALUES
(1, 1, 2, NULL),
(7, 2, 1, NULL),
(20, 2, 3, NULL),
(24, 1, 3, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `live`
--

CREATE TABLE `live` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `broadcast_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display` int(11) DEFAULT NULL,
  `resource_uri` longtext COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `viewers` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `live`
--

INSERT INTO `live` (`id`, `vendor_id`, `broadcast_id`, `status`, `channel`, `event`, `display`, `resource_uri`, `thumbnail`, `created_at`, `viewers`) VALUES
(32, 1, '3c4f77dc-71ee-4190-b2af-afc6edfb54c6', 2, 'channel32', 'event32', 1, 'https://cdn.bambuser.net/broadcasts/5ba0bee3-94e8-4120-a804-3a1ecbfef417?da_signature_method=HMAC-SHA256&da_id=9e1b1e83-657d-7c83-b8e7-0b782ac9543a&da_timestamp=1635328744&da_static=1&da_ttl=0&da_signature=cc1a826dbcb71986ef8c6c613d9b1d2680682514be15a58ffbaba02b6278778e', 'https://preview.bambuser.io/live/eyJyZXNvdXJjZVVyaSI6Imh0dHBzOlwvXC9jZG4uYmFtYnVzZXIubmV0XC9icm9hZGNhc3RzXC8yMmIzODg2ZC01YzYxLTRhNDAtYTc5NS0xZjZlYjhhMWNiNmEifQ==/preview.jpg', '2021-10-26 16:31:42', NULL),
(35, 1, 'fff9859f-5da3-42fc-b941-0bb1484dd88f', 2, 'channel35', 'event35', 1, 'https://cdn.bambuser.net/broadcasts/fff9859f-5da3-42fc-b941-0bb1484dd88f?da_signature_method=HMAC-SHA256&da_id=9e1b1e83-657d-7c83-b8e7-0b782ac9543a&da_timestamp=1635328744&da_static=1&da_ttl=0&da_signature=66aa05df995765d594455a05448651979a642ae088b330205eba292a8b002e3a', 'https://preview.bambuser.io/live/eyJyZXNvdXJjZVVyaSI6Imh0dHBzOlwvXC9jZG4uYmFtYnVzZXIubmV0XC9icm9hZGNhc3RzXC8yMmIzODg2ZC01YzYxLTRhNDAtYTc5NS0xZjZlYjhhMWNiNmEifQ==/preview.jpg', '2021-10-26 16:31:37', NULL),
(36, 2, 'c8a24b07-df04-4919-a3a2-542693b55dccv', 2, 'channel36', 'event36', 1, 'https://cdn.bambuser.net/broadcasts/c8a24b07-df04-4919-a3a2-542693b55dcc?da_signature_method=HMAC-SHA256&da_id=9e1b1e83-657d-7c83-b8e7-0b782ac9543a&da_timestamp=1636630747&da_static=1&da_ttl=0&da_signature=b299b180652570f451efb273ad09fe930ae3e0b365b58d9560ed9c7f408b83d4', 'https://preview.bambuser.io/live/eyJyZXNvdXJjZVVyaSI6Imh0dHBzOlwvXC9jZG4uYmFtYnVzZXIubmV0XC9icm9hZGNhc3RzXC9jOGEyNGIwNy1kZjA0LTQ5MTktYTNhMi01NDI2OTNiNTVkY2MifQ==/preview.jpg', '2021-11-01 16:31:40', 1),
(38, 2, '4653f0ac-5042-4c11-992f-3393f7200c05', 2, 'channel123', 'event123', 1, 'https://cdn.bambuser.net/broadcasts/4653f0ac-5042-4c11-992f-3393f7200c05?da_signature_method=HMAC-SHA256&da_id=9e1b1e83-657d-7c83-b8e7-0b782ac9543a&da_timestamp=1636713299&da_static=1&da_ttl=0&da_signature=7c81f82a6b73506fa9f4cffa2c0a4173c317f9b21777882c90cc2c0a4de11c6f', 'https://preview.bambuser.io/live/eyJyZXNvdXJjZVVyaSI6Imh0dHBzOlwvXC9jZG4uYmFtYnVzZXIubmV0XC9icm9hZGNhc3RzXC80NjUzZjBhYy01MDQyLTRjMTEtOTkyZi0zMzkzZjcyMDBjMDUifQ==/preview.jpg', '2021-11-12 11:34:51', 0),
(39, 2, NULL, 0, NULL, NULL, 1, NULL, NULL, '2021-12-07 12:26:25', 0);

-- --------------------------------------------------------

--
-- Structure de la table `live_products`
--

CREATE TABLE `live_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `live_id` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `live_products`
--

INSERT INTO `live_products` (`id`, `product_id`, `live_id`, `priority`) VALUES
(64, 8, 32, 1),
(67, 1, 35, 1),
(68, 2, 36, 1),
(73, 9, 38, 1),
(74, 9, 39, 1),
(75, 10, 39, 2);

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
  `created_at` datetime NOT NULL,
  `content` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `message`
--

INSERT INTO `message` (`id`, `live_id`, `user_id`, `vendor_id`, `type`, `created_at`, `content`, `time`) VALUES
(14, 36, NULL, 2, 0, '2021-10-26 11:44:10', 'j\'adore', NULL),
(23, 36, NULL, 2, 0, '2021-11-04 15:00:15', 'au top', NULL),
(51, 36, NULL, 2, 0, '2021-11-04 15:00:15', 'ok', NULL),
(52, 36, NULL, 2, 0, '2021-11-04 15:00:15', 'yes', NULL),
(53, 36, NULL, 2, 0, '2021-11-16 11:32:27', 'test', NULL),
(54, 36, NULL, 2, 0, '2021-11-16 11:46:53', 'hello', NULL),
(56, 36, NULL, 2, 0, '2021-11-16 11:47:14', 'why not', NULL),
(57, 36, NULL, 2, 0, '2021-11-16 12:11:24', 'hey reis', NULL),
(58, 38, NULL, 2, 0, '2021-11-16 18:00:07', 'fgfggfgfg', NULL),
(59, 38, NULL, 2, 0, '2021-11-16 18:02:17', 'hello', NULL),
(60, 38, NULL, 2, 0, '2021-11-16 18:05:33', 'fdfdfdfdfdfdfdfd', NULL),
(61, 38, NULL, 2, 0, '2021-11-17 10:12:04', 'dsdsdsds', NULL),
(62, 38, NULL, 2, 0, '2021-11-17 10:18:39', 'dsdsdsdsds', NULL),
(63, 38, NULL, 2, 0, '2021-11-17 10:21:41', 'test 2', NULL),
(64, 38, NULL, 2, 0, '2021-11-17 10:24:31', 'hello test', NULL),
(65, 38, NULL, 2, 0, '2021-11-17 10:26:40', 'julien test ', NULL),
(66, 38, NULL, 2, 0, '2021-11-17 10:26:50', 'voila ', NULL),
(67, 38, NULL, 2, 0, '2021-11-17 10:27:22', 'trop bien j\'adore', NULL),
(68, 38, NULL, 2, 0, '2021-11-17 11:30:54', 'hello', NULL),
(69, 38, NULL, 2, 0, '2021-11-17 13:12:55', 'fdfdfd', NULL),
(70, 38, NULL, 2, 0, '2021-11-17 17:30:56', 'hello', NULL),
(71, 36, NULL, 1, 0, '2021-11-18 11:27:08', 'heyyy', NULL),
(72, 36, NULL, 1, 0, '2021-11-18 11:34:45', 'au top', NULL),
(73, 32, NULL, 2, 0, '2021-12-07 15:16:06', 'fddfdfdf', NULL),
(74, 32, NULL, 2, 0, '2021-12-07 15:16:20', 'hello', NULL),
(75, 36, NULL, 2, 0, '2021-12-07 15:16:25', 'dfdfdfd', NULL),
(76, 36, NULL, 2, 0, '2021-12-07 15:16:29', 'fdfdfdfdfd', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `online` tinyint(1) NOT NULL,
  `price` double NOT NULL,
  `compare_at_price` double DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `tracking` tinyint(1) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `product`
--

INSERT INTO `product` (`id`, `category_id`, `vendor_id`, `description`, `online`, `price`, `compare_at_price`, `quantity`, `tracking`, `title`) VALUES
(1, 8, 1, 'Un tapis léger et au toucher velours, vous pouvez emmener ce tapis dans tous vos cours de yoga\r\n\r\nLa douceur de ce tapis vous invite à un rendez-vous régulier avec votre pratique. Léger, il vous accompagne de la maison au studio. Ses marquages discrets vous guident vers une meilleure posture.', 1, 25, 30, 50, 1, 'Tapis de yoga'),
(2, 1, 1, 'Laissez-vous envoûter par les pouvoirs revitalisants et protecteurs de l’huile de Figue de Barbarie ! \r\n\r\nLa gamme à l’huile de Figue de Barbarie de Youarda.C est un véritable concentré de bienfaits pour vos cheveux, elle nourrit votre cuir chevelu en profondeur, favorise la micro-circulation et hydrate le cheveu.\r\n\r\n', 1, 39.9, 49.9, 23, 1, 'Gamme Ricin'),
(8, 6, 1, 'Test', 1, 79.99, 99.99, 10, 1, 'Veste sandro'),
(9, 9, 2, 'Rehaussez vos séances de jeu avec des chargements à la vitesse de l’éclair grâce au disque SSD ultrarapide, une meilleure immersion grâce au retour haptique, aux gâchettes adaptatives et à la technologie audio 3D, et l’incroyable catalogue de jeux PlayStation® nouvelle génération.\r\n\r\n', 1, 500, 600, 100, 1, 'Playstation 5'),
(10, 9, 2, 'Les invitations sont lancées ! Réunissez famille et amis pour vous affronter sur le jeu de plateau le plus déjanté avec Mario Party Superstars dès le 29 octobre sur Nintendo Switch.\r\n\r\nMario, Peach et tous leurs amis sont prêts à faire la fête avec vous sur 5 plateaux issus des versions Nintendo 64. Au début de chaque tour, lancez votre dé pour avancer. Votre but : réunir le plus de pièces possible pour acheter des étoiles et gagner la partie ! Comment ? Affrontez-vous autour de mini-jeux à la fin de chaque tour pour tenter de ramasser le plus de pièces possible. Courses, jeux d’agilité ou de réflexe… 100 mini-jeux issus de toute la série vont vous permettre de défier vos proches.\r\n\r\nAttention sur votre chemin… chaque plateau comporte son lot de surprises et les renversements de situation sont de la partie. Gare aux Roi Boo dans le Pays de l’horreur. Il peut vous voler vos étoiles à tout moment et sans prévenir ! Sur l’Île tropicale de Yoshi, Bowser peut remplacer Toadette n’importe quand et l’étoile peut vous passer sous le nez.\r\n\r\n', 1, 49, NULL, 1000, 1, 'Mario party superstars');

-- --------------------------------------------------------

--
-- Structure de la table `upload`
--

CREATE TABLE `upload` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `upload`
--

INSERT INTO `upload` (`id`, `product_id`, `filename`, `created_at`) VALUES
(2, 1, 'pe35c803603d997aed6d236ca.jpeg', '2021-10-15 09:36:18'),
(3, 2, 'untitled-project-copy-copy-copy_2x_6_2000x.png', '2021-10-15 11:30:22'),
(4, 2, 'kit-soins-figue-de-barbarie-1_2000x.jpeg', '2021-10-15 11:30:25'),
(5, 8, '76234632ec0531e3e927584f48d00446.jpeg', '2021-10-15 09:36:18'),
(6, 9, '105642.jpeg', '2021-10-28 11:45:15'),
(7, 10, '113085.jpeg', '2021-10-28 11:48:45');

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
(1, 'julienreignierr@gmail.com', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-06 11:11:06', 'Julien SAS', 'Julien', 'REIGNIER', 'Boutique de vêtements et accessoires', '1a058cfb9ea95f0acd4fedd647d5bb40.jpg', 'juuulienr', 'juuulienr', 'juuulienr', 'juuulienr'),
(2, 'c.cheklat@yahoo.fr', '$2y$13$3kA7Taz29ojRAQ3Fc48w0uGJGi6/r70zH5v1sP4euOsJ6VWNQyQP2', NULL, '2021-10-13 13:53:21', 'CHEMS SAS', 'Chems eddine', 'Cheklat', 'Boutique de jeux vidéos et accessoires', 'ad5c0d1afe40f225779afda26ff9d4a6.jpg', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe', 'kabyleluxe'),
(3, 'test@gmail.com', '$2y$13$xEmLZF.C88YEphFvv6lUJuUT3PU3A4TXRqIp5QRXNGd7ofTy4hTpm', NULL, '2021-12-10 15:15:29', 'TEST SAS', 'test', 'test', 'Je suis un compte test pour voir si ça marche', NULL, '', '', '', '');

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
-- Index pour la table `live_products`
--
ALTER TABLE `live_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_74EC2FAE4584665A` (`product_id`),
  ADD KEY `IDX_74EC2FAE1DEBA901` (`live_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `clip`
--
ALTER TABLE `clip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `follow`
--
ALTER TABLE `follow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `live`
--
ALTER TABLE `live`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pour la table `live_products`
--
ALTER TABLE `live_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT pour la table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `upload`
--
ALTER TABLE `upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Contraintes pour la table `live_products`
--
ALTER TABLE `live_products`
  ADD CONSTRAINT `FK_74EC2FAE1DEBA901` FOREIGN KEY (`live_id`) REFERENCES `live` (`id`),
  ADD CONSTRAINT `FK_74EC2FAE4584665A` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`);

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
