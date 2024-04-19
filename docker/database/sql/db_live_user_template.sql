--
-- create user
--
INSERT INTO `user` (`id`, `username`, `roles`, `password`, `email`, `first_name`, `last_name`, `function`) VALUES (NULL, '##USERNAME##', '[\"ROLE_ADMIN\"]', '##PASSWORD##', '##EMAIL##', '##FIRST##', '##LAST##', 'Inhaber');

SET @last_id_in_table1 = LAST_INSERT_ID();

--
-- add basic user settings
--
INSERT INTO `user_setting` (`id`,`user_id`,`setting_key`,`setting_value`) VALUES (NULL, @last_id_in_table1, 'pagination-page-size', 20);

--
-- add all dynamic fields for user
--
INSERT INTO `dynamic_form_field_relation` (`id`, `field_id`, `user_id`, `sort_id`, `show_on_index`)
SELECT
  NULL, id, 1, default_sort_id, on_index_default
FROM
  dynamic_form_field