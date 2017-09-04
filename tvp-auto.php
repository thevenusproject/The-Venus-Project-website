<?php
/* Note: this script works on Windows and Linux. */

/* Instructions
1) Change the settings below to match the correct values for your localhost
2) Run this script (on the guest vm) throught CLI, e.g.: php tvp-auto.php
*/

define('DS', DIRECTORY_SEPARATOR);

/*** Settings - Filesystem ***/
$delete_exst_filesystem = true; //whether to delete the existing filesystem and extract an archived new one on its place. Set to false if you already have the filesystem and you only want db import
$newtvp_root_dir = DS.'vagrant'.DS.'newtvp'.DS; //the Wordpress root directory, with a trailing slash
$path_to_zip_archive = DS.'vagrant'.DS.'tvp.zip'; //path to the zipped archive of the filesystem. If you don't have a zipped archived but instead have a folder with the filesystem, leave this empty and set $delete_exst_filesystem to false

/*** Settings - Database ***/
$localhost_mysql_user = ''; //preferably put root in here
$localhost_mysql_user_pw = ''; //password of your mysql root user, if you have set one
$newtvp_database = ''; //how you want the Wordpress db to be named. If you already have one, it'll first be dropped.
$newtvp_db_prefix = ''; //Wordpress table prefix
$newtvp_civi_database = ''; //how you want the CiviCRM db to be named. If you already have one, it'll first be dropped.
$path_newtvp_dump = DS.'vagrant' . DS .'tvp.sql'; //path to the Wordpress dump
$path_newtvpcivi_dump = DS.'vagrant'.DS.'tvpcivi.sql'; //path to the CiviCRM dump
$path_to_mysqlexe = 'D:'.DS.'xampp'.DS.'mysql'.DS.'bin'.DS.'mysql.exe'; //path to your mysql binary; needed on Windows only
$newtvp_domain = 'newtvp.example.com'; //the domain you would like the site to have



/**************************/
/*** BEGIN SCRIPT CODE ****/
/**************************/

/* Find out OS */

$OS = php_uname('s');
if (strtoupper(substr($OS, 0, 3)) === 'LIN') {
    $OS = 'Linux';
} elseif(strtoupper(substr($OS, 0, 3)) === 'WIN') {
    $OS = 'Windows';
} else {
	echo 'Operating system not supported. Aborting.';
	die;
}

/* Determine whether to delete existing filesystem */

if(!$delete_exst_filesystem) {
	echo "Skipping deletion of {$newtvp_root_dir} as per the Settings.\n";
}
else {
	/* Extract the zipped filesystem and rename the folder */

	//Delete site dir if it exists
	if(file_exists($newtvp_root_dir)) {
		echo "Attempting to delete {$newtvp_root_dir}. If you don't get an error, it succeeded.\n";
		
		if($OS == 'Windows') {
			exec("rd /s /q {$newtvp_root_dir}", $result);
		} 
		elseif($OS == 'Linux') {
			exec("rm -rf {$newtvp_root_dir}", $result);
		}
	}

	$zip = new ZipArchive;
	if($zip->open($path_to_zip_archive) === true) {
		
			// get the name of the folder inside the archive
			$old_folder_name = $zip->getNameIndex(0);
			$old_folder_name = rtrim($old_folder_name, DS);
			
			//figure out directory to extract the archive to
			$extraction_dir = rtrim($newtvp_root_dir, DS);
			$parts = explode(DS, $extraction_dir);
			array_pop($parts);
			$extraction_dir = implode(DS, $parts);
			
			// extract archive
			echo "Starting to extract {$path_to_zip_archive}\n";
			$zip->extractTo($extraction_dir);
			$zip->close();
			echo "Extracted " . $path_to_zip_archive . " to " . $extraction_dir . "\n";
			
			// rename the extracted folder
			$parts = explode(DS, $path_to_zip_archive, -1);
			$old_path = implode(DS, $parts);
			$old_path .= DS . $old_folder_name . DS;
			if(rename($old_path, $newtvp_root_dir)) {
				echo "Renamed " . $old_path . " to " . $newtvp_root_dir . "\n";
			}
	}
	else {
		echo "Could not open the archive " . $path_to_zip_archive . "\n";
		die;
	}
}

/* Establish database connection */

$con = mysqli_connect('localhost',$localhost_mysql_user,$localhost_mysql_user_pw);
if(!$con) {
	echo 'Could not connect to mysql';
	die();
}
if(!mysqli_set_charset($con, 'utf8')) {
	echo 'Could not set mysql charset' . '. Failed with error: ' . mysqli_error($con);
	die();
}


/* Dropping the two databases */

$db_exists = mysqli_select_db($con, $newtvp_database);
if($db_exists) {
	$sql = 'DROP DATABASE ' . $newtvp_database;
	$result = mysqli_query($con, $sql);
	if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}
	echo 'Dropped database ' . $newtvp_database . "\n";
}


$db_exists = mysqli_select_db($con, $newtvp_civi_database);
if($db_exists) {
	$sql = 'DROP DATABASE ' . $newtvp_civi_database;
	$result = mysqli_query($con, $sql);
	if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}
	echo 'Dropped database ' . $newtvp_civi_database . "\n";
}


/* Creating the two databases */

$sql = 'CREATE DATABASE ' . $newtvp_database;
$result = mysqli_query($con, $sql);
if(!$result) {
	echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
	die();
}
echo 'Created database ' . $newtvp_database . "\n";

$sql = 'CREATE DATABASE ' . $newtvp_civi_database;
$result = mysqli_query($con, $sql);
if(!$result) {
	echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
	die();
}
echo 'Created database ' . $newtvp_civi_database . "\n";


/* Create the user that was used to create the triggers for Civi */

$sql = "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'thevenus_t30c')";
$result = mysqli_query($con, $sql);

if(!$result) {
	echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
	die();
}

$row = mysqli_fetch_array($result);

//this query returns 0 if user doesn't exist and 1 if it does
if($row[0] == '0') {
	$sql = "CREATE USER 'thevenus_t30c'@'localhost'";
	$result = mysqli_query($con, $sql);
	if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}
	echo "Created database user thevenus_t30c needed for the CiviCRM triggers\n";
	
	$sql = "GRANT ALL ON " . $newtvp_civi_database . ".* TO 'thevenus_t30c'@'localhost'"; 
	$result = mysqli_query($con, $sql);
	if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}
	echo "Granted privileges to the thevenus_t30c user for the {$newtvp_civi_database} database\n";
}


/* Importing data into the two databases */

//wordpress database
if($OS == 'Windows') {
	$cmd = "{$path_to_mysqlexe} -h localhost -u {$localhost_mysql_user} -p{$localhost_mysql_user_pw} {$newtvp_database} < {$path_newtvp_dump}";
}
elseif($OS == 'Linux') {
	$cmd = "mysql -u {$localhost_mysql_user} -p{$localhost_mysql_user_pw} {$newtvp_database} < {$path_newtvp_dump}";
}

echo "Attempting to import {$path_newtvp_dump} to {$newtvp_database}. If you don't get an error message, the import completed successfully.\n";
exec($cmd, $result, $return_value);

//civi database
if($OS == 'Windows') {
	$cmd = "{$path_to_mysqlexe} -h localhost -u {$localhost_mysql_user} -p{$localhost_mysql_user_pw} {$newtvp_civi_database} < {$path_newtvpcivi_dump}";
}
elseif($OS == 'Linux') {
	$cmd = "mysql -u {$localhost_mysql_user} -p{$localhost_mysql_user_pw} {$newtvp_civi_database} < {$path_newtvpcivi_dump}";
}

echo "Attempting to import {$path_newtvpcivi_dump} to {$newtvp_civi_database}. If you don't get an error message, the import completed successfully.\n";
exec($cmd, $result, $return_value);


/* Change values in Wordpress database */

$sql = "USE " . $newtvp_database;
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
$sql = "UPDATE " . $newtvp_db_prefix . "blogs SET domain = '" . $newtvp_domain . "' WHERE site_id = 1";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed blogs table\n";


$sql = "UPDATE " . $newtvp_db_prefix . "options SET option_value = 'https://" . $newtvp_domain . "' WHERE option_name = 'siteurl'";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed options table, siteurl row\n";	


$sql = "UPDATE " . $newtvp_db_prefix . "options SET option_value = 'https://" . $newtvp_domain . "' WHERE option_name = 'home'";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed options table, home row\n";
	
	
$sql = "UPDATE " . $newtvp_db_prefix . "site SET domain = '" . $newtvp_domain . "' WHERE id = 1";
$result = mysqli_query($con, $sql);
if(!$result) {
		echo 'Failed to execute the query: ' . $sql . '. Failed with error: ' . mysqli_error($con);
		die();
	}	
	
echo "Changed site table\n";
	

$sql = "UPDATE " . $newtvp_db_prefix . "sitemeta SET meta_value = 'https://" . $newtvp_domain . "/' WHERE meta_key = 'siteurl'";
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
	if(strpos($value,'DB_NAME') !== false) {
		$wp_config[$i] = "define('DB_NAME','" . $newtvp_database . "');\n";
	}
	if(strpos($value,'DB_USER') !== false) {
		$wp_config[$i] = "define('DB_USER','" . $localhost_mysql_user . "');\n";
	}
	if(strpos($value,'DB_PASSWORD') !== false) {
		$wp_config[$i] = "define('DB_PASSWORD','" . $localhost_mysql_user_pw . "');\n";
	}
	if(strpos($value,'DOMAIN_CURRENT_SITE') !== false) {
		$wp_config[$i] = "define('DOMAIN_CURRENT_SITE','" . $newtvp_domain . "');\n";
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
	if(strpos($value,"define( 'CIVICRM_UF_DSN'") !== false) {
		$civi_config[$i] = "define( 'CIVICRM_UF_DSN', 'mysql://" . $localhost_mysql_user . ":" . $localhost_mysql_user_pw .  "@localhost/" . $newtvp_database . "?new_link=true' );\n";
	}
	if(strpos($value,"define( 'CIVICRM_DSN'") !== false) {
		$civi_config[$i] = "define( 'CIVICRM_DSN', 'mysql://" . $localhost_mysql_user . ":" . $localhost_mysql_user_pw .  "@localhost/" . $newtvp_civi_database . "?new_link=true' );\n";
	}
	if(strpos($value,(string)'$civicrm_root =') !== false) {
		$civi_config[$i] = '$civicrm_root = ' . "'" . $newtvp_root_dir . "wp-content/plugins/civicrm/civicrm/';\n";
	}
	if(strpos($value,"define( 'CIVICRM_TEMPLATE_COMPILEDIR'") !== false) {
		$civi_config[$i] = "define( 'CIVICRM_TEMPLATE_COMPILEDIR'," . "'" . $newtvp_root_dir . "wp-content/plugins/files/civicrm/templates_c/' );\n";
	}
	if(strpos($value,"define( 'CIVICRM_UF_BASEURL'") !== false) {
		$civi_config[$i] = "define( 'CIVICRM_UF_BASEURL', 'https://" . $newtvp_domain . "/' );\n";
	}
}

implode('', $civi_config);
if(file_put_contents($newtvp_root_dir . 'wp-content/plugins/civicrm/civicrm.settings.php', $civi_config)) {
	echo "Changed civicrm.settings.php file\n";
}


/* Disable php.ini files */

if(file_exists($newtvp_root_dir . 'php.ini')) {
	rename($newtvp_root_dir . 'php.ini', $newtvp_root_dir . 'php.ini_disabled');
	echo $newtvp_root_dir . "php.ini disabled\n"; 
}

if(file_exists($newtvp_root_dir . 'wp-admin/php.ini')) {
	rename($newtvp_root_dir . 'wp-admin/php.ini', $newtvp_root_dir . 'wp-admin/php.ini_disabled');
	echo $newtvp_root_dir . "wp-admin/php.ini disabled\n"; 
}


/* Disable object-cache.php file */

if(file_exists($newtvp_root_dir . 'wp-content/object-cache.php')) {
	rename($newtvp_root_dir . 'wp-content/object-cache.php', $newtvp_root_dir . 'wp-content/object-cache.php_disabled');
	echo $newtvp_root_dir . "wp-content/object-cache.php disabled\n"; 
}


/* Change .htaccess file in root dir */

$root_htaccess = file($newtvp_root_dir . '.htaccess');

foreach($root_htaccess as $i => $value) {
	if(strpos($value,"AddHandler") !== false) {
		$root_htaccess[$i] = "#" . $value;
	}	
	if(strpos($value,"SetEnv") !== false) {
		$root_htaccess[$i] = "#" . $value;
	}
}	

implode('', $root_htaccess);
if(file_put_contents($newtvp_root_dir . '.htaccess', $root_htaccess)) {
	echo "Changed " . $newtvp_root_dir . ".htaccess\n";
}

/* Log Civi mails to file */

$civi_config = file($newtvp_root_dir . 'wp-content'.DS.'plugins'.DS.'civicrm'.DS.'civicrm.settings.php');
$civi_config[3] = "define('CIVICRM_MAIL_LOG', 1);\n";

implode('', $civi_config);
if(file_put_contents($newtvp_root_dir . 'wp-content'.DS.'plugins'.DS.'civicrm'.DS.'civicrm.settings.php', $civi_config)) {
	echo "Added define('CIVICRM_MAIL_LOG', 1) to civicrm.settings.php\n";
}

/* Delete Civi template cache */
//apparently this doesn't work with forward slashes, so hardcoding it for now
$path = $newtvp_root_dir . 'wp-content'.DS.'plugins'.DS.'files'.DS.'civicrm'.DS.'templates_c'.DS.'en_US';
echo "Attempting to delete Civi template cache. If you don't get an error message, the deletion completed successfully.\n";

if($OS == 'Windows') {
	exec("rd /s /q {$path}", $result);
}
elseif($OS == 'Linux') {
	exec("rm -rf {$path}", $result);
}

echo "The script has completed. Making five beeps to let you know.\n";
//make some noise to alert the sleepy user
print "\x07"."\x07"."\x07"."\x07"."\x07";
