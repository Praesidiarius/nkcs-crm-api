INSERT INTO `user` (`id`, `username`, `roles`, `password`, `first_name`, `last_name`, `function`) VALUES (NULL, '##USERNAME##', '[\"ROLE_ADMIN\"]', '##PASSWORD##', '##FIRST##', '##LAST##', 'Inhaber');

SET @last_id_in_table1 = LAST_INSERT_ID();
INSERT INTO `user_setting` (`id`,`user_id`,`setting_key`,`setting_value`) VALUES (NULL, @last_id_in_table1, 'pagination-page-size', 5);

INSERT INTO `dynamic_form_field_relation` (`id`, `field_id`, `user_id`, `sort_id`, `show_on_index`) VALUES
(NULL, 1, @last_id_in_table1, 0, 1),
(NULL, 2, @last_id_in_table1, 1, 1),
(NULL, 3, @last_id_in_table1, 2, 1),
(NULL, 4, @last_id_in_table1, 3, 0),
(NULL, 5, @last_id_in_table1, 4, 0),
(NULL, 6, @last_id_in_table1, 5, 0),
(NULL, 7, @last_id_in_table1, 6, 0),
(NULL, 8, @last_id_in_table1, 7, 0),
(NULL, 9, @last_id_in_table1, 8, 0),
(NULL, 10, @last_id_in_table1, 9, 0),
(NULL, 11, @last_id_in_table1, 10, 0),
(NULL, 12, @last_id_in_table1, 11, 0),
(NULL, 13, @last_id_in_table1, 12, 0),
(NULL, 14, @last_id_in_table1, 13, 0),
(NULL, 15, @last_id_in_table1, 14, 0),
(NULL, 16, @last_id_in_table1, 15, 0),
(NULL, 17, @last_id_in_table1, 16, 0),
(NULL, 18, @last_id_in_table1, 17, 0),
(NULL, 19, @last_id_in_table1, 18, 1),
(NULL, 20, @last_id_in_table1, 19, 0),
(NULL, 21, @last_id_in_table1, 20, 1);