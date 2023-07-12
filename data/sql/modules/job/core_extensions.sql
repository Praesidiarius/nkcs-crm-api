--
-- job module core extensions - must be done AFTER the job module sql
--
CREATE TABLE `job_position`
(
  `id`      int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `job_id`  int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `price`   double                                  DEFAULT NULL,
  `comment` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount`  double                                  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_type`
(
  `id`         int(11) NOT NULL,
  `type_key`   varchar(50) COLLATE utf8mb4_unicode_ci  NOT NULL,
  `type_value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `job_type` (`id`, `type_key`, `type_value`)
VALUES (1, 'job', 'Job'),
       (2, 'offer', 'Offer');

ALTER TABLE `job_position`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_216B418E126F525E` (`item_id`),
  ADD KEY `IDX_216B418EBE04EA9` (`job_id`),
  ADD KEY `IDX_216B418EF8BD700D` (`unit_id`);

ALTER TABLE `job_type`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `job_position`
  MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `job_type`
  MODIFY `id` int (11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `job_position`
  ADD CONSTRAINT `FK_216B418E126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  ADD CONSTRAINT `FK_216B418EBE04EA9` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`),
  ADD CONSTRAINT `FK_216B418EF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `item_unit` (`id`);

ALTER TABLE `job`
  ADD CONSTRAINT `FK_FBD8E0F8C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `job_type` (`id`);

--
-- enable job document type
--
INSERT INTO `document_type` (`id`, `name`, `identifier`)
VALUES (NULL, 'document.type.job', 'job');

COMMIT;