CREATE TABLE `contact_history_event`
(
    `id`         int(11) NOT NULL AUTO_INCREMENT,
    `name`       varchar(50) NOT NULL,
    `selectable` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `contact_history_event` (`id`, `name`, `selectable`)
VALUES (1, 'contact.history.event.create', 0),
       (2, 'contact.history.event.edit', 0),
       (3, 'contact.history.event.add_address', 0),
       (4, 'contact.history.event.edit_address', 0),
       (5, 'contact.history.event.remove_address', 0),
       (6, 'contact.history.event.generate_document', 0),
       (7, 'contact.history.event.call_inbound', 1),
       (8, 'contact.history.event.call_outbound', 1);

CREATE TABLE `contact_history`
(
    `id`            int(11) NOT NULL AUTO_INCREMENT,
    `event_id`      int(11) NOT NULL,
    `contact_id`    int(11) NOT NULL,
    `date`          datetime NOT NULL,
    `date_reminder` datetime     DEFAULT NULL,
    `comment`       varchar(255) DEFAULT NULL,
    `created_by`    int(11) NOT NULL,
    `created_at`    datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY             `contact_id` (`contact_id`),
    KEY             `created_by` (`created_by`),
    CONSTRAINT `contact_history_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`id`),
    CONSTRAINT `contact_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;