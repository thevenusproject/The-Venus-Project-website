<?php

/* Settings */
$localhost_mysql_user = '';
$localhost_mysql_user_pw = ''; 
$newtvp_database = '';
$newtvp_db_prefix = '';
$newtvp_root_dir = '/vagrant/newtvp/';
$ngrok_domain = 'newtvp.example.com';


/* Instructions
1) Change $ngrok_domain to needed value
2) Run this script throught CLI
*/


/* Establish database connection */

$con = mysqli_connect('localhost',$localhost_mysql_user,$localhost_mysql_user_pw);
if(!$con) {
	echo 'Could not connect to mysql';
	die();
}
if(!mysqli_set_charset($con, 'utf8')) {
	echo 'Could not set mysql charset' . '. Failed with error: ' . mysqli_error($con);
}


/* Change values in database */

$sql = "USE " . $newtvp_database;
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
$sql = "UPDATE " . $newtvp_db_prefix . "blogs SET domain = '" . $ngrok_domain . "' WHERE site_id = 1";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed blogs table\n";


$sql = "UPDATE " . $newtvp_db_prefix . "options SET option_value = 'https://" . $ngrok_domain . "' WHERE option_name = 'siteurl'";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed options table, siteurl row\n";	


$sql = "UPDATE " . $newtvp_db_prefix . "options SET option_value = 'https://" . $ngrok_domain . "' WHERE option_name = 'home'";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed options table, home row\n";
	
	
$sql = "UPDATE " . $newtvp_db_prefix . "site SET domain = '" . $ngrok_domain . "' WHERE id = 1";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed site table\n";
	

$sql = "UPDATE " . $newtvp_db_prefix . "sitemeta SET meta_value = 'https://" . $ngrok_domain . "/' WHERE meta_key = 'siteurl'";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed sitemeta table\n";


/** Change lines in configuration files **/

/* Change wp-config.php */

$wp_config = file($newtvp_root_dir . 'wp-config.php');

foreach($wp_config as $i => $value) {
	if(strpos($value,'DOMAIN_CURRENT_SITE') !== false) {
		$wp_config[$i] = "define('DOMAIN_CURRENT_SITE','" . $ngrok_domain . "');\n";
	}
	if(strpos($value,'COOKIE_DOMAIN') !== false) {
		$wp_config[$i] = '//' . $wp_config[$i];
	}
}

implode('', $wp_config);
if(file_put_contents($newtvp_root_dir . 'wp-config.php', $wp_config)) {
	echo "Changed wp-config.php file\n";
}

/* Change civicrm.settings.php */

$civi_config = file($newtvp_root_dir . 'wp-content/plugins/civicrm/civicrm.settings.php');
foreach($civi_config as $i => $value) {
	if(strpos($value,"define( 'CIVICRM_UF_BASEURL'") !== false) {
		$civi_config[$i] = "define( 'CIVICRM_UF_BASEURL', 'https://" . $ngrok_domain . "/' );\n";
	}
}

implode('', $civi_config);
if(file_put_contents($newtvp_root_dir . 'wp-content/plugins/civicrm/civicrm.settings.php', $civi_config)) {
	echo "Changed civicrm.settings.php file\n";
}
