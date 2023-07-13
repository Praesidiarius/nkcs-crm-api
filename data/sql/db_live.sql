SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- system settings
--
CREATE TABLE `system_setting`
(
  `id`            int(11)                                NOT NULL,
  `setting_key`   varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8mb4_unicode_ci    NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO `system_setting` (`id`, `setting_key`, `setting_value`)
VALUES (1, 'add-field-allowed-tables', '[\"contact\",\"item\"]')
;

ALTER TABLE `system_setting`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `system_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;

--
-- user and user settings
--
CREATE TABLE `user`
(
  `id`         int(11)                                 NOT NULL,
  `username`   varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles`      longtext COLLATE utf8mb4_unicode_ci     NOT NULL COMMENT '(DC2Type:json)',
  `password`   varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email`      VARCHAR(255)                            NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci  NOT NULL,
  `last_name`  varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `function`   varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `user_setting`
(
  `id`            int(11)                                NOT NULL,
  `user_id`       int(11)                                NOT NULL,
  `setting_key`   varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649F85E0677` (`username`);

ALTER TABLE `user_setting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C779A692A76ED395` (`user_id`);

ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_setting`
  ADD CONSTRAINT `FK_C779A692A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- dynamic forms
--
CREATE TABLE `dynamic_form`
(
  `id`       int(11)                                 NOT NULL,
  `label`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `dynamic_form_field`
(
  `id`                int(11)                                 NOT NULL,
  `parent_field_id`   int(11)                                          DEFAULT NULL,
  `section_id`        int(11)                                          DEFAULT NULL,
  `dynamic_form_id`   int(11)                                 NOT NULL,
  `label`             varchar(100) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
  `field_key`         varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type`        varchar(25) COLLATE utf8mb4_unicode_ci  NOT NULL,
  `field_required`    INT(1)                                  NOT NULL DEFAULT '0',
  `columns`           int(11)                                 NOT NULL,
  `default_data`      varchar(255) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
  `related_table`     varchar(100) COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
  `related_table_col` varchar(50) COLLATE utf8mb4_unicode_ci           DEFAULT NULL,
  `on_index_default`  INT(1)                                  NOT NULL DEFAULT '0',
  `default_sort_id`   INT(1)                                  NOT NULL DEFAULT '0'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `dynamic_form_field_relation`
(
  `id`            int(11) NOT NULL,
  `field_id`      int(11) NOT NULL,
  `user_id`       int(11) NOT NULL,
  `sort_id`       int(11) NOT NULL,
  `show_on_index` int(1)  NOT NULL DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `dynamic_form_section`
(
  `id`                int(11)                                 NOT NULL,
  `parent_section_id` int(11)                                 DEFAULT NULL,
  `section_label`     varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_key`       varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_id`           int(11)                                 NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

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


ALTER TABLE `dynamic_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dynamic_form_field`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dynamic_form_field_relation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dynamic_form_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dynamic_form_field`
  ADD CONSTRAINT `FK_518F4EA1818A7566` FOREIGN KEY (`dynamic_form_id`) REFERENCES `dynamic_form` (`id`),
  ADD CONSTRAINT `FK_518F4EA1D823E37A` FOREIGN KEY (`section_id`) REFERENCES `dynamic_form_section` (`id`),
  ADD CONSTRAINT `FK_518F4EA1DBFAAB61` FOREIGN KEY (`parent_field_id`) REFERENCES `dynamic_form_field` (`id`);

ALTER TABLE `dynamic_form_field_relation`
  ADD CONSTRAINT `FK_3A0D0510443707B0` FOREIGN KEY (`field_id`) REFERENCES `dynamic_form_field` (`id`),
  ADD CONSTRAINT `FK_3A0D0510A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `dynamic_form_section`
  ADD CONSTRAINT `FK_7FDFF3E99F60672A` FOREIGN KEY (`parent_section_id`) REFERENCES `dynamic_form_section` (`id`);

--
-- document module
--
CREATE TABLE `document`
(
  `id`           int(11)                                 NOT NULL,
  `type_id`      int(11)                                 NOT NULL,
  `template_id`  int(11)                                 NOT NULL,
  `file_name`    varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id`    int(11)                                 NOT NULL,
  `created_by`   int(11)                                 NOT NULL,
  `created_date` datetime                                NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `document_template`
(
  `id`      int(11)                                 NOT NULL,
  `type_id` int(11)                                 NOT NULL,
  `name`    varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `document_type`
(
  `id`         int(11)                                 NOT NULL,
  `name`       varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(50) COLLATE utf8mb4_unicode_ci  NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D8698A76C54C8C93` (`type_id`),
  ADD KEY `IDX_D8698A765DA0FB8` (`template_id`);

ALTER TABLE `document_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_18A1EEDAC54C8C93` (`type_id`);

ALTER TABLE `document_type`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `document_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `document_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `document`
  ADD CONSTRAINT `FK_D8698A765DA0FB8` FOREIGN KEY (`template_id`) REFERENCES `document_template` (`id`),
  ADD CONSTRAINT `FK_D8698A76C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `document_type` (`id`);

ALTER TABLE `document_template`
  ADD CONSTRAINT `FK_18A1EEDAC54C8C93` FOREIGN KEY (`type_id`) REFERENCES `document_type` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
