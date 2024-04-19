--
-- item module core extensions - must be done AFTER the item module sql
--

--
-- item units
--
CREATE TABLE `item_unit`
(
  `id`   int(11)                                NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO `item_unit` (`id`, `name`, `type`)
VALUES (1, 'item.unit.piece', NULL),
       (2, 'item.unit.hour', NULL);

ALTER TABLE `item_unit`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `item_unit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;

ALTER TABLE `item`
  ADD CONSTRAINT `FK_1F1B251EF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `item_unit` (`id`);

--
-- item types
--
CREATE TABLE `item_type`
(
  `id`   int(11)                                NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO `item_type` (`id`, `name`, `type`)
VALUES (1, 'item.type.default', 'default'),
       (2, 'item.type.giftcard', 'giftcard');

ALTER TABLE `item_type`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `item_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;

ALTER TABLE `item`
  ADD CONSTRAINT `FK_1F1B251EF8BD600D` FOREIGN KEY (`type_id`) REFERENCES `item_type` (`id`);

--
-- enable item document type
--
INSERT INTO `document_type` (`id`, `name`, `identifier`)
VALUES (NULL, 'document.type.item', 'item');

COMMIT;