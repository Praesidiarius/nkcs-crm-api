--
-- basic db layout for item module (variant: basic)
--
CREATE TABLE `item`
(
  `id`           int(11) NOT NULL,
  `unit_id`      int(11) NOT NULL,
  `type_id`      int(11) NOT NULL,
  `name`         varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price`        double                                  NOT NULL,
  `created_by`   int(11) NOT NULL,
  `created_date` datetime                                NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `description`  text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1F1B251EF8BD700D` (`unit_id`);

ALTER TABLE `item`
  MODIFY `id` int (11) NOT NULL AUTO_INCREMENT;

--
-- dynamic form
--

-- form
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'item.item', 'item');

SET
@item_form_id = LAST_INSERT_ID();

-- sections
INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'item.item', 'itemMain', @item_form_id);

SET
@item_main_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @item_main_section_id, NULL, 'itemBasic', @item_form_id);

SET
@item_basic_section_id = LAST_INSERT_ID();

-- fields
INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `field_required`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @item_basic_section_id, @item_form_id, 'item.name', 'name', 'text', 1, 6, NULL, NULL, NULL, 1, 0),
       (NULL, NULL, @item_basic_section_id, @item_form_id, 'item.unit.unit', 'unit_id', 'select', 1, 2, 1,
        'item_unit', 'name', 1, 1),
       (NULL, NULL, @item_basic_section_id, @item_form_id, 'item.type.type', 'type_id', 'select', 1, 2, 1,
        'item_type', 'name', 0, 2),
       (NULL, NULL, @item_basic_section_id, @item_form_id, 'item.price.price', 'price', 'currency', 1, 2, NULL, NULL, NULL, 1, 3),
       (NULL, NULL, @item_basic_section_id, @item_form_id, 'item.description', 'description', 'textarea', 0, 12, NULL, NULL, NULL, 0, 4);

COMMIT;