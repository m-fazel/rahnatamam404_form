CREATE TABLE `registrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `registration_type` VARCHAR(20) NOT NULL,
    `student_mode` VARCHAR(20) DEFAULT NULL,
    `entry_year` VARCHAR(20) DEFAULT NULL,
    `married_status` VARCHAR(30) DEFAULT NULL,
    `amount` BIGINT NOT NULL,
    `formatted_amount` VARCHAR(30) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `national_code` VARCHAR(20) NOT NULL,
    `birth_date` DATE NOT NULL,
    `mobile` VARCHAR(30) NOT NULL,
    `spouse_name` VARCHAR(150) DEFAULT NULL,
    `spouse_national_code` VARCHAR(20) DEFAULT NULL,
    `spouse_birth_date` DATE DEFAULT NULL,
    `children_count` INT DEFAULT NULL,
    `payment_order_id` BIGINT DEFAULT NULL,
    `payment_order_guid` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `group_members` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `registration_id` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `national_code` VARCHAR(20) NOT NULL,
    `birth_date` DATE NOT NULL,
    `mobile` VARCHAR(30) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_registration_id` (`registration_id`),
    CONSTRAINT `fk_group_members_registration` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
