<?php

function usage() {
    global $argv;

    print "Usage: " . $argv[0] . " new.domain.com" . PHP_EOL;
}

function l($str) {
    echo $str;
}

function ok() {
    echo "OK" . PHP_EOL;
}

function error($error) {
    echo "Error: " . $error . PHP_EOL;
}

function connect_to_db() {
    global $db;

    l('Connecting to the database... ');
    // root is needed to create 'thevenus_test@localhost' user
    $db = mysqli_connect(getenv('WORDPRESS_DB_HOST'), 'root', getenv('WORDPRESS_DB_PASSWORD'), getenv('WORDPRESS_DB_NAME'));
    if(!$db) {
        error(mysqli_connect_error());
        exit();
    }
    ok();
}

function query($q) {
    global $db;

    l($q . '... ');
    $res = mysqli_query($db, $q);

    if($res) {
        ok();
    } else {
        error(mysqli_error($db));
    }

    return $res;
}

function select($q) {
    global $db;

    $res = query($q);
    if(!$res) return false;

    $r = [];
    while($tmp = mysqli_fetch_assoc($res)) {
        $r[] = $tmp;
    }
    return $r;
}

function select_row($q) {
    $res = select($q);
    if($res === false) return false;
    if(count($res) != 1) return false;

    return $res[0];
}

function get_civicrm_config_filename() {
    return '/var/www/html/wp-content/uploads/civicrm/civicrm.settings.php';
}

function get_wp_config_filename() {
    return '/var/www/html/wp-config.php';
}

function set_civicrm_config($name, $value, $type) {
    $civicrm_config_path = get_civicrm_config_filename();

    l("CiviCRM config " . $type . ": '" . $name . "' = '" . $value. "'... ");

    $config = file($civicrm_config_path);
    if(!$config) {
        error('Can\'t open "' . $civicrm_config_path . '"');

        return false;
    }

    $found = false;
    foreach($config as $i => $line) {
        if($type == 'constant') {
            if(preg_match('/^(\s*)define\s*\(\s*[\'"]' . $name . '[\'"]/', $line, $matches)) {
                // $matches[1] contains indentation
                $new_line = $matches[1] . sprintf('define(\'%s\', \'%s\');' . PHP_EOL, $name, $value);

                $config[$i] = $new_line;

                $found = true;
            }
        } elseif($type == 'var') {
            if(preg_match('/^(\s*)\$' . $name . '\s*=\s*/', $line, $matches)) {
                // $matches[1] contains indentation
                $new_line = $matches[1] . sprintf('$%s = \'%s\';' . PHP_EOL, $name, $value);

                $config[$i] = $new_line;

                $found = true;
            }
        }
    }

    if(!$found) {
        error('Config constatnt "' . $name . '" is not present in the config file');

        return false;
    }

    file_put_contents($civicrm_config_path, join('', $config));

    ok();

    return true;
}

function set_wp_constant($name, $value) {
    $wp_config_path = get_wp_config_filename();

    l("WP config: '" . $name . "' = '" . $value. "'... ");

    $config = file($wp_config_path);
    if(!$config) {
        error('Can\'t open "' . $wp_config_path . '"');

        return false;
    }

    $found = false;
    foreach($config as $i => $line) {
        if(preg_match('/^(\s*)define\s*\(\s*[\'"]' . $name . '[\'"]/', $line, $matches)) {
            // $matches[1] contains indentation
            $new_line = $matches[1] . sprintf('define(\'%s\', \'%s\');' . PHP_EOL, $name, $value);

            $config[$i] = $new_line;

            $found = true;
        }
    }

    if(!$found) {
        array_splice($config, 1, 0, [
            PHP_EOL,
            sprintf('define("%s", "%s");', addslashes($name), addslashes($value)) . PHP_EOL,
            PHP_EOL,
        ]);
    }

    file_put_contents($wp_config_path, join('', $config));

    ok();

    return true;
}

function set_civicrm_constant($name, $value) {
    return set_civicrm_config($name, $value, 'constant');
}

function set_civicrm_var($name, $value) {
    return set_civicrm_config($name, $value, 'var');
}

function update_civicrm_paths() {
    l("Updating CiviCRM paths... ");

    // @todo hardcode. fetch timezone from db or include plugins/civicrm/civicrm.php
    date_default_timezone_set('America/New_York');

    $settings_filename = get_civicrm_config_filename();
    require_once $settings_filename;

    define('CIVICRM_SETTINGS_PATH', $settings_filename);
    require_once $civicrm_root . '/civicrm.config.php';

    require_once 'CRM/Core/Config.php';
    $config =& CRM_Core_Config::singleton();

    require_once 'CRM/Core/BAO/ConfigSetting.php';
    $moveStatus = CRM_Core_BAO_ConfigSetting::doSiteMove();

    ok();

    l($moveStatus . PHP_EOL);
}
