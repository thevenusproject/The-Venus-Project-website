<?php

require_once(__DIR__ . '/common.php');


if($argc !== 2) {
    usage();

    exit();
}

$domain = $argv[1];
$port = false;

if(($colon_pos = strpos($domain, ':')) !== false) {
    $port = intval(substr($domain, $colon_pos + 1));
    $domain = substr($domain, 0, $colon_pos);

    if(!$port) {
        usage();

        exit();
    }
}

connect_to_db();

$escaped_domain = mysqli_real_escape_string($db, $domain);
$port_part = $port ? ':' . $port : '';

query("UPDATE xo_blogs SET domain = '" . $escaped_domain . "' WHERE site_id = 1");
query("UPDATE xo_options SET option_value = 'http://" . $escaped_domain . $port_part . "' WHERE option_name = 'siteurl'");
query("UPDATE xo_options SET option_value = 'http://" . $escaped_domain . $port_part . "' WHERE option_name = 'home'");
query("UPDATE xo_site SET domain = '" . $escaped_domain . "' WHERE id = 1");
query("UPDATE xo_sitemeta SET meta_value = 'http://" . $escaped_domain . $port_part . "/' WHERE meta_key = 'siteurl'");

set_civicrm_constant('CIVICRM_UF_BASEURL', 'http://' . $domain . $port_part . '/');
set_wp_constant('COOKIE_DOMAIN', $domain);

update_civicrm_paths();

l("Done." . PHP_EOL);
