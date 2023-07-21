ALTER TABLE `contact`
  ADD `contact_identifier` varchar(50)  DEFAULT NULL AFTER `description`,
  ADD `referral_id`        int(11)      DEFAULT NULL AFTER `contact_identifier`,
  ADD `signup_token`       varchar(255) DEFAULT NULL AFTER `referral`,
  ADD `signup_date_step1`  datetime     DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)' AFTER `signup_token`,
  ADD `signup_date_step2`  datetime     DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)' AFTER `signup_date_step1`
;

INSERT INTO `system_setting` (`id`, `setting_key`, `setting_value`)
VALUES (NULL, 'contact-signup-email-subject', 'NKCS Test Signup'),
       (NULL, 'contact-signup-email-content', 'Signup here: http://example.com/step2/#HASH#'),
       (NULL, 'contact-signup-email-text', 'Signup here: http://example.com/step2/#HASH#'),
       (NULL, 'contact-signup-email-name', 'NKCS CRM');
;