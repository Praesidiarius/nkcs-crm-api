SELECT @item_form_id := `id`
FROM dynamic_form
WHERE form_key = 'item';

--
-- remove basic price field from item
--
DELETE FROM `dynamic_form_field` WHERE `dynamic_form_id` = @item_form_id AND `field_key` = 'price';

ALTER TABLE `item`
    DROP `price`;

--
-- create table for item pricing data
--
CREATE TABLE `item_price`
(
    `id`             int            NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `item_id`        int(11)        NOT NULL,
    `price_purchase` decimal(10, 2) NOT NULL,
    `price_sell`     decimal(10, 2) NOT NULL,
    `date`           datetime       NOT NULL,
    `created_by`     int(11)        NOT NULL,
    `created_date`   datetime       NOT NULL,
    `comment`        varchar(255)   NULL,
    FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
);

--
-- add new tab for pricing to item form
--

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, NULL, 'item.price.price', 'itemPriceTab', @item_form_id);

SET @item_price_tab_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_section` (`id`, `parent_section_id`, `section_label`, `section_key`, `form_id`)
VALUES (NULL, @item_price_tab_section_id, 'item.price.price', 'itemPriceTable', @item_form_id);

SET @item_price_table_section_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `related_table_order`, `on_index_default`, `default_sort_id`)
VALUES (NULL, NULL, @item_price_table_section_id, @item_form_id, 'prices', 'price', 'table', 12, NULL,
        'item_price', 'item_id', 'date ASC', 0, 7),
       (NULL, NULL, @item_price_table_section_id, @item_form_id, 'item.price_history.title', 'price_history', 'chart', 12, NULL,
        'item_price', 'item_id', 'date ASC', 0, 8);

SET @item_price_table_field_id = LAST_INSERT_ID();


--
-- add item pricing form
--
INSERT INTO `dynamic_form` (`id`, `label`, `form_key`)
VALUES (NULL, 'item.price.price', 'itemPrice');

SET @item_price_form_id = LAST_INSERT_ID();

INSERT INTO `dynamic_form_field` (`id`, `parent_field_id`, `section_id`, `dynamic_form_id`, `label`, `field_key`,
                                  `field_type`, `columns`, `default_data`, `related_table`, `related_table_col`,
                                  `on_index_default`, `default_sort_id`)
VALUES (NULL, @item_price_table_field_id, NULL, @item_price_form_id, 'item.price.purchase', 'price_purchase', 'price', 2, NULL,
        NULL, NULL, 0, 0),
       (NULL, @item_price_table_field_id, NULL, @item_price_form_id, 'item.price.sell', 'price_sell', 'price', 2, NULL, NULL,
        NULL, 0, 1),
       (NULL, @item_price_table_field_id, NULL, @item_price_form_id, 'item.price.date', 'date', 'date', 2, NULL, NULL,
        NULL, 0, 2),
       (NULL, @item_price_table_field_id, NULL, @item_price_form_id, 'item.price.comment', 'comment', 'text', 6, NULL, NULL,
        NULL, 0, 3);

--
-- enable extension
--
INSERT INTO `system_setting` (`setting_key`, `setting_value`)
VALUES ('item-price-history-extension-enabled', '1');
