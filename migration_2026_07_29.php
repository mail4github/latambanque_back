<?php

$q = <<<EOT
ALTER TABLE `currencies` 
CHANGE COLUMN `currency_code` `currency_code` CHAR(5) NULL DEFAULT 'USD' ;
EOT;
tep_db_query($q);

tep_db_query("ALTER TABLE `currencies` CHANGE COLUMN `blocks_explorer` `blocks_explorer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
tep_db_query("ALTER TABLE `currencies` CHANGE COLUMN `logo` `logo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");

$q = <<<EOT
ALTER TABLE `cryptodb_addresses` CHANGE COLUMN `currency` `currency` CHAR(10) DEFAULT 'USD';
ALTER TABLE `cryptodb_transactions` CHANGE COLUMN `currency` `currency` CHAR(10) DEFAULT 'USD';
ALTER TABLE `pay_attempts` CHANGE COLUMN `currency` `currency` CHAR(10) DEFAULT 'USD';
ALTER TABLE `services_serving` CHANGE COLUMN `currency` `currency` CHAR(10) DEFAULT 'USD';
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `users` 
CHANGE COLUMN `firstname` `firstname` VARCHAR(224) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT '' ,
CHANGE COLUMN `lastname` `lastname` VARCHAR(224) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT '' ,
CHANGE COLUMN `note` `note` VARCHAR(256) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT '' ,
CHANGE COLUMN `address` `address` VARCHAR(32) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT '' ,
CHANGE COLUMN `website` `website` VARCHAR(64) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT '' ;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `transactions` 
ADD COLUMN `var_text1` VARCHAR(256) NULL DEFAULT '' AFTER `var_int1`,
ADD INDEX `key_var_text1` (`var_text1`)
;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `sent_mails` 
CHANGE COLUMN `subject` `subject` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT NULL ,
CHANGE COLUMN `body_text` `body_text` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT NULL ,
CHANGE COLUMN `body_html` `body_html` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_bin' NULL DEFAULT NULL ;
;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `stocks` 
CHANGE COLUMN `stockid` `stockid` CHAR(15) NOT NULL ;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `stocks_price_history` 
CHANGE COLUMN `stockid` `stockid` CHAR(15) NOT NULL ;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `stocks` 
ADD INDEX `Index_tagline` (`tagline`);
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `email_templates` 
ADD COLUMN `permissions` VARCHAR(1024) NULL DEFAULT 'view=ALL&edit=ALL&delete=' AFTER `body`,
ADD INDEX `Index_permissions` (`permissions` ASC) VISIBLE;
EOT;
tep_db_query($q);

$q = <<<EOT
ALTER TABLE `email_templates` 
ADD COLUMN `sender_email` VARCHAR(256) NULL DEFAULT '' AFTER `user_websiteid`,
ADD COLUMN `sender_name` VARCHAR(256) NULL DEFAULT '' AFTER `sender_email`,
ADD INDEX `index_sender_email` (`sender_email`),
ADD INDEX `index_sender_name` (`sender_name`);
EOT;
tep_db_query($q);

?>