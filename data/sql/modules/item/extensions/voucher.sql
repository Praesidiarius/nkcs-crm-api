--
-- basic db layout for voucher extension (variant: basic)
--

--
-- vouchers
--
CREATE TABLE `item_voucher`
(
  `id`            int(11)                                 NOT NULL,
  `name`          varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount`        double                                  NOT NULL,
  `voucher_type`  varchar(10)                             NOT NULL,
  `use_only_once` int(1)                                  NOT NULL DEFAULT '0',
  `contact_id`    int(11)                                          DEFAULT NULL,
  `date_start`    datetime                                         DEFAULT NULL,
  `date_end`      datetime                                         DEFAULT NULL,
  `created_by`    int(11)                                 NOT NULL,
  `created_date`  datetime                                NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `description`   text COLLATE utf8mb4_unicode_ci                  DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `item_voucher`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `item_voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- voucher codes
--

CREATE TABLE `item_voucher_code`
(
  `id`          int(11)     NOT NULL,
  `item_id`     int(11) DEFAULT NULL,
  `voucher_id`  int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `code`        varchar(50) NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

ALTER TABLE `item_voucher_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `job_position_id` (`position_id`);


ALTER TABLE `item_voucher_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `item_voucher_code`
  ADD CONSTRAINT `item_voucher_code_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  ADD CONSTRAINT `item_voucher_code_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `item_voucher` (`id`),
  ADD CONSTRAINT `item_voucher_code_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `job_position` (`id`);

--
-- voucher code redeem
--
CREATE TABLE `item_voucher_code_redeem`
(
  `id`              int(11)  NOT NULL,
  `voucher_code_id` int(11)  NOT NULL,
  `job_id`          int(11)  NOT NULL,
  `contact_id`      int(11)  NOT NULL,
  `date`            datetime NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;


ALTER TABLE `item_voucher_code_redeem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `voucher_code_id` (`voucher_code_id`),
  ADD KEY `contact_id` (`contact_id`);


ALTER TABLE `item_voucher_code_redeem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `item_voucher_code_redeem`
  ADD CONSTRAINT `item_voucher_code_redeem_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`),
  ADD CONSTRAINT `item_voucher_code_redeem_ibfk_2` FOREIGN KEY (`voucher_code_id`) REFERENCES `item_voucher_code` (`id`),
  ADD CONSTRAINT `item_voucher_code_redeem_ibfk_3` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`);

--
-- dynamic form
--

-- form
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'item.voucher.voucher', 'voucher');

SET
  @voucher_form_id = LAST_INSERT_ID();

-- sections
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'item.voucher.voucher', 'voucherMain', @voucher_form_id);

SET
  @voucher_main_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @voucher_main_section_id, 'voucher.form.section.basic', 'voucherBasic', @voucher_form_id);

SET
  @voucher_basic_section_id = LAST_INSERT_ID();

-- fields
INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `field_required`, `columns`, `default_data`, `related_table`,
                                  `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.name', 'name', 'text', 1, 5, NULL, NULL, NULL, 1,
        0),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.code', 'code', 'text', 1, 3, NULL, NULL,
        NULL, 0, 1),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.amount', 'amount', 'currency', 1, 2,
        NULL, NULL, NULL, 1, 2),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.type.type', 'voucher_type', 'select', 1,
        2, NULL, 'enum', 'ItemVoucherType', 0, 3),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.dateStart', 'date_start', 'date', 0, 3,
        NULL, NULL, NULL, 0, 4),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.dateEnd', 'date_end', 'date', 0, 3,
        NULL, NULL, NULL, 0, 5),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.voucher.isUnique', 'use_only_once', 'checkbox', 0, 2,
        NULL, NULL, NULL, 0, 6),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'job.contact', 'contact_id', 'autocomplete', 0, 4,
        NULL, 'contact', 'first_name', 0, 7),
       (NULL, NULL, @voucher_basic_section_id, @voucher_form_id, 'item.description', 'description', 'textarea', 0, 12,
        NULL, NULL, NULL, 0, 10);

COMMIT;