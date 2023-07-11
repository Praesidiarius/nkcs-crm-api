SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `license` (
                         `id` int(11) NOT NULL,
                         `contact_id` int(11) NOT NULL,
                         `holder` varchar(100) NOT NULL,
                         `date_created` datetime NOT NULL,
                         `date_start` datetime NOT NULL,
                         `date_valid` datetime DEFAULT NULL,
                         `url_api` varchar(255) NOT NULL,
                         `url_client` varchar(255) NOT NULL,
                         `comment` varchar(255) DEFAULT NULL,
                         `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `license_product` (
                                 `id` int(11) NOT NULL,
                                 `item_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `license_purchase` (
                                  `id` int(11) NOT NULL,
                                  `contact_id` int(11) NOT NULL,
                                  `product_id` int(11) NOT NULL,
                                  `holder` varchar(100) NOT NULL,
                                  `date_created` datetime NOT NULL,
                                  `date_completed` datetime DEFAULT NULL,
                                  `hash` varchar(255) NOT NULL,
                                  `checkout_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `license`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5768F419E7A1254A` (`contact_id`),
  ADD KEY `IDX_5768F4194584665A` (`product_id`);

ALTER TABLE `license_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_3A534FF6126F525E` (`item_id`);

ALTER TABLE `license_purchase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_9D47400CE7A1254A` (`contact_id`),
  ADD KEY `IDX_9D47400C4584665A` (`product_id`);


ALTER TABLE `license`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `license_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `license_purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
