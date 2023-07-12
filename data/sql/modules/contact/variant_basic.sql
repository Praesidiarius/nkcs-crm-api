--
-- basic db layout for contact module (variant: basic)
--
CREATE TABLE `contact`
(
  `id`            int(11)    NOT NULL,
  `salution_id`   int(11)                                 DEFAULT NULL,
  `first_name`    varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name`     varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_company`    tinyint(1) NOT NULL,
  `email_private` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by`    int(11)    NOT NULL,
  `created_date`  datetime   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `phone`         varchar(20) COLLATE utf8mb4_unicode_ci  DEFAULT NULL,
  `company_name`  varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_uid`   varchar(30) COLLATE utf8mb4_unicode_ci  DEFAULT NULL,
  `description`   text COLLATE utf8mb4_unicode_ci         DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_4C62E6382C2D130` (`salution_id`);

ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- dynamic form
--

-- form
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'contact.contact', 'contact');

SET
  @contact_form_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'contact.address', 'contactAddress');

SET
  @contact_address_form_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'contact.contact', 'company');

SET
  @company_form_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'contact.address', 'companyAddress');

SET
  @company_address_form_id = LAST_INSERT_ID();

-- sections
-- contact
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'contact.form.section.contact', 'contactMain', @contact_form_id);

SET
  @contact_main_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @contact_main_section_id, 'contact.form.section.basic', 'contactBasic', @contact_form_id);

SET
  @contact_basic_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @contact_main_section_id, 'contact.form.section.addresses', 'contactAddress', @contact_form_id);

SET
  @contact_address_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'contact.form.section.history', 'contactHistory', @contact_form_id);

-- company
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'contact.form.section.contact', 'contactCompanyMain', @company_form_id);

SET
  @company_main_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @company_main_section_id, 'contact.form.section.basic', 'contactCompanyBasic', @company_form_id);

SET
  @company_basic_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @company_main_section_id, 'contact.form.section.addresses', 'contactCompanyAddress', @company_form_id);

SET
  @company_address_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'contact.form.section.history', 'contactCompanyHistory', @company_form_id);

-- fields
-- contact
INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @contact_basic_section_id, @contact_form_id, NULL, 'is_company', 'hidden', 0, '0', NULL, NULL, 0, 0),
       (NULL, NULL, @contact_basic_section_id, @contact_form_id, 'salution', 'salution_id', 'select', 2, NULL, 'contact_salution', 'name', 0, 1),
       (NULL, NULL, @contact_basic_section_id, @contact_form_id, 'firstname', 'first_name', 'text', 5, NULL, NULL, NULL, 1, 2),
       (NULL, NULL, @contact_basic_section_id, @contact_form_id, 'lastname', 'last_name', 'text', 5, NULL, NULL, NULL, 1, 3),
       (NULL, NULL, @contact_basic_section_id, @contact_form_id, 'email.address', 'email_private', 'email', 8, NULL, NULL, NULL, 0, 4),
       (NULL, NULL, @contact_basic_section_id, @contact_form_id,  'phone', 'phone', 'phone', 4, NULL, NULL, NULL, 0, 5);

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @contact_address_section_id, @contact_form_id, 'addresses', 'address', 'table', 12, NULL, NULL, NULL, 0, 6);

SET
  @contact_address_field_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, @contact_address_field_id, NULL, @contact_address_form_id, 'address.street', 'street', 'text', 6, NULL, NULL, NULL, 0, 0),
       (NULL, @contact_address_field_id, NULL, @contact_address_form_id, 'address.zip', 'zip', 'zip', 1, NULL, NULL, NULL, 0, 1),
       (NULL, @contact_address_field_id, NULL, @contact_address_form_id, 'address.city', 'city', 'city', 5, NULL, NULL, NULL, 0, 2);

-- company
INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @company_basic_section_id, @company_form_id, NULL, 'is_company', 'hidden', 0, '1', NULL, NULL, 0, 0),
       (NULL, NULL, @company_basic_section_id, @company_form_id, 'company', 'company_name', 'text', 12, NULL, NULL, NULL, 1, 1),
       (NULL, NULL, @company_basic_section_id, @company_form_id, 'email.address', 'email_private', 'email', 8, NULL, NULL, NULL, 0, 2),
       (NULL, NULL, @company_basic_section_id, @company_form_id,  'phone', 'phone', 'phone', 4, NULL, NULL, NULL, 0, 3);

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @company_address_section_id, @company_form_id, 'addresses', 'address', 'table', 12, NULL, NULL, NULL, 0, 4);

SET
  @company_address_field_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, @company_address_field_id, NULL, @company_address_form_id, 'address.street', 'street', 'text', 6, NULL, NULL, NULL, 0, 0),
       (NULL, @company_address_field_id, NULL, @company_address_form_id, 'address.zip', 'zip', 'zip', 1, NULL, NULL, NULL, 0, 1),
       (NULL, @company_address_field_id, NULL, @company_address_form_id, 'address.city', 'city', 'city', 5, NULL, NULL, NULL, 0, 2);
