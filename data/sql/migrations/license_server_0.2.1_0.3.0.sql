ALTER TABLE `license` ADD `date_start` DATETIME NOT NULL AFTER `date_created`;

INSERT INTO `system_setting` (`id`, `setting_key`, `setting_value`) VALUES (NULL, 'install-trial-product', '1');