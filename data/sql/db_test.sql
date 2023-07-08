SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `nkcs_test` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nkcs_test`;

CREATE TABLE `contact` (
                         `id` int(11) NOT NULL,
                         `salution_id` int(11) DEFAULT NULL,
                         `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `is_company` tinyint(1) NOT NULL,
                         `email_private` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `email_business` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `created_by` int(11) NOT NULL,
                         `created_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                         `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `company_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `company_uid` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `contact_identifier` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `signup_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                         `signup_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                         `birthday` date DEFAULT NULL,
                         `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_address` (
                                 `id` int(11) NOT NULL,
                                 `contact_id` int(11) DEFAULT NULL,
                                 `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                 `zip` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                 `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                 `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_salution` (
                                  `id` int(11) NOT NULL,
                                  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contact_salution` (`id`, `name`) VALUES
                                                (1, 'contact.salution.mr'),
                                                (2, 'contact.salution.mrs');

CREATE TABLE `doctrine_migration_versions` (
                                             `version` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
                                             `executed_at` datetime DEFAULT NULL,
                                             `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
  ('DoctrineMigrations\\Version20230703184144', '2023-07-03 18:41:50', 342);

CREATE TABLE `document` (
                          `id` int(11) NOT NULL,
                          `type_id` int(11) NOT NULL,
                          `template_id` int(11) NOT NULL,
                          `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                          `entity_id` int(11) NOT NULL,
                          `created_by` int(11) NOT NULL,
                          `created_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_template` (
                                   `id` int(11) NOT NULL,
                                   `type_id` int(11) NOT NULL,
                                   `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document_type` (
                               `id` int(11) NOT NULL,
                               `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                               `identifier` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `document_type` (`id`, `name`, `identifier`) VALUES
                                                           (1, 'document.type.contact', 'contact'),
                                                           (2, 'document.type.job', 'job');

CREATE TABLE `dynamic_form` (
                              `id` int(11) NOT NULL,
                              `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                              `form_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dynamic_form` (`id`, `label`, `form_key`) VALUES
                                                         (1, 'contact.contact', 'contact'),
                                                         (2, 'contact.contact.address', 'contactAddress'),
                                                         (3, 'contact.contact', 'company'),
                                                         (4, 'item.item', 'item');

CREATE TABLE `dynamic_form_field` (
                                    `id` int(11) NOT NULL,
                                    `parent_field_id` int(11) DEFAULT NULL,
                                    `section_id` int(11) NOT NULL,
                                    `dynamic_form_id` int(11) NOT NULL,
                                    `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `field_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                                    `field_type` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
                                    `columns` int(11) NOT NULL,
                                    `default_data` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `related_table` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                    `related_table_col` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`, `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`) VALUES
                                                                                                                                                                                                   (1, NULL, 2, 1, 'salution', 'salution_id', 'select', 2, '#salutions#', 'contact_salution', 'name'),
                                                                                                                                                                                                   (2, NULL, 2, 1, 'firstname', 'first_name', 'text', 5, NULL, NULL, NULL),
                                                                                                                                                                                                   (3, NULL, 2, 1, 'lastname', 'last_name', 'text', 5, NULL, NULL, NULL),
                                                                                                                                                                                                   (4, NULL, 2, 1, 'email.address', 'email_private', 'email', 8, NULL, NULL, NULL),
                                                                                                                                                                                                   (5, NULL, 2, 1, 'phone', 'phone', 'phone', 4, NULL, NULL, NULL),
                                                                                                                                                                                                   (6, NULL, 3, 1, 'addresses', 'address', 'table', 12, NULL, NULL, NULL),
                                                                                                                                                                                                   (7, 6, 3, 2, 'address.street', 'street', 'text', 6, NULL, NULL, NULL),
                                                                                                                                                                                                   (8, 6, 3, 2, 'address.zip', 'zip', 'zip', 1, NULL, NULL, NULL),
                                                                                                                                                                                                   (9, 6, 3, 2, 'address.city', 'city', 'city', 5, NULL, NULL, NULL),
                                                                                                                                                                                                   (10, NULL, 2, 1, NULL, 'is_company', 'hidden', 0, '', NULL, NULL),
                                                                                                                                                                                                   (11, NULL, 6, 3, 'company', 'company_name', 'text', 12, NULL, NULL, NULL),
                                                                                                                                                                                                   (12, NULL, 6, 3, 'email.address', 'email_private', 'email', 8, NULL, NULL, NULL),
                                                                                                                                                                                                   (13, NULL, 6, 3, 'phone', 'phone', 'phone', 4, NULL, NULL, NULL),
                                                                                                                                                                                                   (14, NULL, 7, 3, 'addresses', 'address', 'table', 12, NULL, NULL, NULL),
                                                                                                                                                                                                   (15, 14, 7, 3, 'address.street', 'street', 'text', 6, NULL, NULL, NULL),
                                                                                                                                                                                                   (16, 14, 7, 3, 'address.zip', 'zip', 'zip', 1, NULL, NULL, NULL),
                                                                                                                                                                                                   (17, 14, 7, 3, 'address.city', 'city', 'city', 5, NULL, NULL, NULL),
                                                                                                                                                                                                   (18, NULL, 6, 3, NULL, 'is_company', 'hidden', 0, '1', NULL, NULL),
                                                                                                                                                                                                   (19, NULL, 10, 4, 'item.name', 'name', 'text', 6, NULL, NULL, NULL),
                                                                                                                                                                                                   (20, NULL, 10, 4, 'item.unit', 'unit_id', 'select', 2, '#units#', 'item_unit', 'name'),
                                                                                                                                                                                                   (21, NULL, 10, 4, 'item.price', 'price', 'currency', 2, '', '', '');

CREATE TABLE `dynamic_form_field_relation` (
                                             `id` int(11) NOT NULL,
                                             `field_id` int(11) NOT NULL,
                                             `user_id` int(11) NOT NULL,
                                             `sort_id` int(11) NOT NULL,
                                             `show_on_index` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dynamic_form_field_relation` (`id`, `field_id`, `user_id`, `sort_id`, `show_on_index`) VALUES
                                                                                                      (1, 1, 1, 0, 1),
                                                                                                      (2, 2, 1, 1, 1),
                                                                                                      (3, 3, 1, 2, 1),
                                                                                                      (4, 4, 1, 3, 0),
                                                                                                      (5, 5, 1, 4, 0),
                                                                                                      (6, 6, 1, 5, 0),
                                                                                                      (7, 7, 1, 6, 0),
                                                                                                      (8, 10, 1, 7, 0),
                                                                                                      (9, 11, 1, 8, 0),
                                                                                                      (10, 12, 1, 9, 0),
                                                                                                      (11, 13, 1, 10, 0),
                                                                                                      (12, 14, 1, 11, 0),
                                                                                                      (13, 18, 1, 12, 0),
                                                                                                      (14, 19, 1, 13, 0),
                                                                                                      (15, 20, 1, 14, 0),
                                                                                                      (16, 21, 1, 15, 0);

CREATE TABLE `dynamic_form_section` (
                                      `id` int(11) NOT NULL,
                                      `parent_section_id` int(11) DEFAULT NULL,
                                      `section_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                      `section_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
                                      `form_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`) VALUES
                                                                                                            (1, NULL, 'contact.form.section.contact', 'contactMain', 1),
                                                                                                            (2, 1, 'contact.form.section.basic', 'contactBasic', 1),
                                                                                                            (3, 1, 'contact.form.section.addresses', 'contactAddress', 1),
                                                                                                            (4, NULL, 'contact.form.section.history', 'contactHistory', 1),
                                                                                                            (5, NULL, 'contact.form.section.contact', 'contactMain', 3),
                                                                                                            (6, 1, 'contact.form.section.basic', 'contactBasic', 3),
                                                                                                            (7, 1, 'contact.form.section.addresses', 'contactAddress', 3),
                                                                                                            (8, NULL, 'contact.form.section.history', 'contactHistory', 3),
                                                                                                            (9, NULL, 'item.item', 'itemMain', 4),
                                                                                                            (10, 9, 'item.form.section.basic', 'itemBasic', 4),
                                                                                                            (11, 9, 'item.form.section.finance', 'itemFinance', 4);

CREATE TABLE `item` (
                      `id` int(11) NOT NULL,
                      `unit_id` int(11) NOT NULL,
                      `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `price` double NOT NULL,
                      `created_by` int(11) NOT NULL,
                      `created_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                      `stripe_enabled` tinyint(1) NOT NULL DEFAULT 0,
                      `stripe_price_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                      `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                      `price_buy` float DEFAULT NULL,
                      `price_sell` float DEFAULT NULL,
                      `price_retail` float DEFAULT NULL,
                      `price_my_cost` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_unit` (
                           `id` int(11) NOT NULL,
                           `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
                           `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `item_unit` (`id`, `name`, `type`) VALUES
                                                 (1, 'item.unit.piece', NULL),
                                                 (2, 'item.unit.hour', NULL);

CREATE TABLE `job` (
                     `id` int(11) NOT NULL,
                     `type_id` int(11) NOT NULL,
                     `contact_id` int(11) NOT NULL,
                     `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                     `created_by` int(11) NOT NULL,
                     `created_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                     `sub_total` double NOT NULL,
                     `vat_mode` smallint(6) NOT NULL,
                     `vat_rate` double DEFAULT NULL,
                     `vat_total` double DEFAULT NULL,
                     `total` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_position` (
                              `id` int(11) NOT NULL,
                              `item_id` int(11) DEFAULT NULL,
                              `job_id` int(11) NOT NULL,
                              `unit_id` int(11) NOT NULL,
                              `price` double DEFAULT NULL,
                              `comment` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                              `amount` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_position_unit` (
                                   `id` int(11) NOT NULL,
                                   `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_type` (
                          `id` int(11) NOT NULL,
                          `type_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                          `type_value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_setting` (
                                `id` int(11) NOT NULL,
                                `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                                `setting_value` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_setting` (`id`, `setting_key`, `setting_value`) VALUES
  (1, 'add-field-allowed-tables', '[\"contact\",\"item\"]');

CREATE TABLE `user` (
                      `id` int(11) NOT NULL,
                      `username` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `roles` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
                      `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                      `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                      `function` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user` (`id`, `username`, `roles`, `password`, `first_name`, `last_name`, `function`) VALUES
  (1, 'dev', '[\"ROLE_ADMIN\"]', '$2y$13$cMyLSyniGkyrM2IhCm68vejEqypYm6vGCsngOgc4VARcSeky2yAw6', 'Dev', 'System', 'Developer');

CREATE TABLE `user_setting` (
                              `id` int(11) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                              `setting_value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_setting` (`id`, `user_id`, `setting_key`, `setting_value`) VALUES
  (1, 1, 'pagination-page-size', '25');

CREATE TABLE `worktime` (
                          `id` int(11) NOT NULL,
                          `user_id` int(11) NOT NULL,
                          `date` date NOT NULL,
                          `start` time NOT NULL,
                          `end` time DEFAULT NULL,
                          `comment` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                          `created_by` int(11) NOT NULL,
                          `created_date` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_4C62E6382C2D130` (`salution_id`);

ALTER TABLE `contact_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_97614E00E7A1254A` (`contact_id`);

ALTER TABLE `contact_salution`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

ALTER TABLE `document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D8698A76C54C8C93` (`type_id`),
  ADD KEY `IDX_D8698A765DA0FB8` (`template_id`);

ALTER TABLE `document_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_18A1EEDAC54C8C93` (`type_id`);

ALTER TABLE `document_type`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `dynamic_form`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `dynamic_form_field`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_518F4EA1DBFAAB61` (`parent_field_id`),
  ADD KEY `IDX_518F4EA1D823E37A` (`section_id`),
  ADD KEY `IDX_518F4EA1818A7566` (`dynamic_form_id`);

ALTER TABLE `dynamic_form_field_relation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3A0D0510443707B0` (`field_id`),
  ADD KEY `IDX_3A0D0510A76ED395` (`user_id`);

ALTER TABLE `dynamic_form_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_7FDFF3E99F60672A` (`parent_section_id`);

ALTER TABLE `item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1F1B251EF8BD700D` (`unit_id`);

ALTER TABLE `item_unit`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `job`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FBD8E0F8C54C8C93` (`type_id`),
  ADD KEY `IDX_FBD8E0F8E7A1254A` (`contact_id`);

ALTER TABLE `job_position`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_216B418E126F525E` (`item_id`),
  ADD KEY `IDX_216B418EBE04EA9` (`job_id`),
  ADD KEY `IDX_216B418EF8BD700D` (`unit_id`);

ALTER TABLE `job_position_unit`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `job_type`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `system_setting`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649F85E0677` (`username`);

ALTER TABLE `user_setting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C779A692A76ED395` (`user_id`);

ALTER TABLE `worktime`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5891D623A76ED395` (`user_id`);


ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contact_salution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `document_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `document_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `dynamic_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `dynamic_form_field`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

ALTER TABLE `dynamic_form_field_relation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

ALTER TABLE `dynamic_form_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `item_unit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `job`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `job_position`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `job_position_unit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `job_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `system_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `user_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `worktime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `contact`
  ADD CONSTRAINT `FK_4C62E6382C2D130` FOREIGN KEY (`salution_id`) REFERENCES `contact_salution` (`id`);

ALTER TABLE `contact_address`
  ADD CONSTRAINT `FK_97614E00E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`);

ALTER TABLE `document`
  ADD CONSTRAINT `FK_D8698A765DA0FB8` FOREIGN KEY (`template_id`) REFERENCES `document_template` (`id`),
  ADD CONSTRAINT `FK_D8698A76C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `document_type` (`id`);

ALTER TABLE `document_template`
  ADD CONSTRAINT `FK_18A1EEDAC54C8C93` FOREIGN KEY (`type_id`) REFERENCES `document_type` (`id`);

ALTER TABLE `dynamic_form_field`
  ADD CONSTRAINT `FK_518F4EA1818A7566` FOREIGN KEY (`dynamic_form_id`) REFERENCES `dynamic_form` (`id`),
  ADD CONSTRAINT `FK_518F4EA1D823E37A` FOREIGN KEY (`section_id`) REFERENCES `dynamic_form_section` (`id`),
  ADD CONSTRAINT `FK_518F4EA1DBFAAB61` FOREIGN KEY (`parent_field_id`) REFERENCES `dynamic_form_field` (`id`);

ALTER TABLE `dynamic_form_field_relation`
  ADD CONSTRAINT `FK_3A0D0510443707B0` FOREIGN KEY (`field_id`) REFERENCES `dynamic_form_field` (`id`),
  ADD CONSTRAINT `FK_3A0D0510A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `dynamic_form_section`
  ADD CONSTRAINT `FK_7FDFF3E99F60672A` FOREIGN KEY (`parent_section_id`) REFERENCES `dynamic_form_section` (`id`);

ALTER TABLE `item`
  ADD CONSTRAINT `FK_1F1B251EF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `item_unit` (`id`);

ALTER TABLE `job`
  ADD CONSTRAINT `FK_FBD8E0F8C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `job_type` (`id`),
  ADD CONSTRAINT `FK_FBD8E0F8E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`);

ALTER TABLE `job_position`
  ADD CONSTRAINT `FK_216B418E126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  ADD CONSTRAINT `FK_216B418EBE04EA9` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`),
  ADD CONSTRAINT `FK_216B418EF8BD700D` FOREIGN KEY (`unit_id`) REFERENCES `job_position_unit` (`id`);

ALTER TABLE `user_setting`
  ADD CONSTRAINT `FK_C779A692A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `worktime`
  ADD CONSTRAINT `FK_5891D623A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
