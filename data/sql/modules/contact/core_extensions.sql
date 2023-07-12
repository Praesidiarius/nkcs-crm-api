--
-- contact module core extensions - must be done AFTER the contact module sql
--

--
-- contact address
--
CREATE TABLE `contact_address`
(
  `id`         int(11) NOT NULL,
  `contact_id` int(11)                                 DEFAULT NULL,
  `street`     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip`        varchar(10) COLLATE utf8mb4_unicode_ci  DEFAULT NULL,
  `city`       varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country`    varchar(2) COLLATE utf8mb4_unicode_ci   DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `contact_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_97614E00E7A1254A` (`contact_id`);

ALTER TABLE `contact_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact_address`
  ADD CONSTRAINT `FK_97614E00E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`);

--
-- contact salution
--
CREATE TABLE `contact_salution`
(
  `id`   int(11)                                NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO `contact_salution` (`id`, `name`)
VALUES (1, 'contact.salution.mr'),
       (2, 'contact.salution.mrs');

ALTER TABLE `contact_salution`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contact_salution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `contact`
  ADD CONSTRAINT `FK_4C62E6382C2D130` FOREIGN KEY (`salution_id`) REFERENCES `contact_salution` (`id`);

--
-- enable contact document type
--
INSERT INTO `document_type` (`id`, `name`, `identifier`)
VALUES (NULL, 'document.type.contact', 'contact');