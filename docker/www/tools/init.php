<?php

require_once(__DIR__ . '/common.php');

connect_to_db();

$dsn = sprintf(
    'mysql://%s:%s@%s/%s?new_link=true',
    getenv('WORDPRESS_DB_USER'),
    getenv('WORDPRESS_DB_PASSWORD'),
    getenv('WORDPRESS_DB_HOST'),
    getenv('WORDPRESS_DB_NAME')
);
set_civicrm_constant('CIVICRM_UF_DSN', $dsn);
set_civicrm_constant('CIVICRM_DSN', $dsn);
set_civicrm_var('civicrm_root', '/var/www/html/wp-content/plugins/civicrm/civicrm/');
set_civicrm_constant('CIVICRM_TEMPLATE_COMPILEDIR', '/var/www/html/wp-content/uploads/civicrm/templates_c/');

// disable SSL redirect
query("UPDATE `xo_options` SET `option_value` = 'no' WHERE `option_name` = 'woocommerce_force_ssl_checkout'");

// grant admin rights to thevenusproject user
query("UPDATE `xo_usermeta` SET `meta_value` = 'a:1:{s:13:\"administrator\";b:1;}' WHERE `meta_key` = 'xo_capabilities' AND `user_id` = 5");

// WordPress SMTP settings
query("UPDATE `xo_options` SET `option_value` = 'mail' WHERE `option_name` = 'smtp_host'");
query("UPDATE `xo_options` SET `option_value` = '1025' WHERE `option_name` = 'smtp_port'");
query("UPDATE `xo_options` SET `option_value` = '' WHERE `option_name` = 'smtp_ssl'");

// CiviCRM SMTP settings
query("UPDATE `civicrm_mail_settings` SET `server` = 'mail', `port` = '1025', `is_ssl` = 0");

$row = select_row('SELECT id, value FROM civicrm_setting WHERE name = "mailing_backend"');
$mailing_backend = unserialize($row['value']);
$mailing_backend['smtpAuth'] = '0';
$mailing_backend['smtpServer'] = 'mail1';
$mailing_backend['smtpPort'] = '1025';
query('UPDATE civicrm_setting SET value = "' . mysqli_real_escape_string($db, serialize($mailing_backend)) . '" WHERE id = ' . $row['id']);

// creator user needed to call some procedures
query("CREATE USER 'thevenus_test'@'localhost' IDENTIFIED BY 'wp'");
query("GRANT ALL ON *.* TO 'thevenus_test'@'localhost' IDENTIFIED BY 'wp'");
query("FLUSH PRIVILEGES");

// fix some inconsistency between stripe code base and DB structure
query("ALTER TABLE `civicrm_stripe_subscriptions` CHANGE `subscription_id` `subscription_id` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL DEFAULT '' COMMENT 'Stripe subscription id'");
query("ALTER TABLE `civicrm_stripe_plans` CHANGE `subscription_id` `subscription_id` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL DEFAULT '' COMMENT 'Stripe subscription id'");
query("ALTER TABLE `civicrm_stripe_customers` CHANGE `subscription_id` `subscription_id` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL DEFAULT '' COMMENT 'Stripe subscription id'");

update_civicrm_paths();

l("Done." . PHP_EOL);
