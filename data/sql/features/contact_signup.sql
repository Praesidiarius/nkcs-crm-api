ALTER TABLE `contact`
  ADD `contact_identifier` varchar(50) DEFAULT NULL AFTER `description`,
  ADD `signup_token` varchar(255) DEFAULT NULL AFTER `contact_identifier`,
  ADD `signup_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)' AFTER `signup_token`
;