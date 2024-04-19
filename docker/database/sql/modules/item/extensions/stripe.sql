ALTER TABLE `item`
  ADD `stripe_enabled` tinyint(1) NOT NULL AFTER `description`,
  ADD `stripe_price_id` varchar(50) DEFAULT NULL AFTER `stripe_enabled`
;