--
-- basic db layout for job module (variant: basic)
--
CREATE TABLE `job`
(
  `id`           int(11)        NOT NULL,
  `type_id`      int(11)        NOT NULL,
  `contact_id`   int(11)                         DEFAULT NULL,
  `title`        varchar(100)                    DEFAULT NULL,
  `description`  text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date`         date                            DEFAULT NULL,
  `created_by`   int(11)        NOT NULL,
  `created_date` datetime       NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `sub_total`    decimal(10, 0) NOT NULL         DEFAULT 0,
  `vat_mode`     smallint(6)    NOT NULL,
  `vat_rate`     double                          DEFAULT NULL,
  `vat_total`    double                          DEFAULT NULL,
  `total`        double         NOT NULL         DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `job`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FBD8E0F8C54C8C93` (`type_id`),
  ADD KEY `IDX_FBD8E0F8E7A1254A` (`contact_id`);

ALTER TABLE `job`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `job`
  ADD CONSTRAINT `FK_FBD8E0F8E7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`);

--
-- dynamic form
--

-- form
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'job.job', 'jobType1');

SET
  @job_form_id = LAST_INSERT_ID();

-- sections
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'job.form.section.job', 'jobType1Main', @job_form_id);

SET
  @typeOne_main_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @typeOne_main_section_id, 'job.form.section.basic', 'jobType1Basic', @job_form_id);
SET
  @typeOne_basic_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'job.form.section.positions', 'jobType1Positions', @job_form_id);

-- fields
INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `field_required`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @typeOne_basic_section_id, @job_form_id, 'job.title', 'title', 'text', 0, 12, NULL, NULL, NULL, 1, 0),
       (NULL, NULL, @typeOne_basic_section_id, @job_form_id, 'job.contact', 'contact_id', 'autocomplete', 1, 4, NULL,
        'contact', 'first_name', 1, 1),
       (NULL, NULL, @typeOne_basic_section_id, @job_form_id, 'job.vat.vat', 'vat_mode', 'select', 1, 4, 'vat_default',
        'enum', 'JobVatMode', 0, 2),
       (NULL, NULL, @typeOne_basic_section_id, @job_form_id, 'time.date', 'date', 'date', 0, 4, NULL,
        NULL, NULL, 0, 3),
       (NULL, NULL, @typeOne_basic_section_id, @job_form_id, 'job.description', 'description', 'textarea', 0, 12, NULL,
        NULL, NULL, 0, 12);

COMMIT;