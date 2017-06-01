<?php
/**
 * Plugin Name: Powerfin SF
 * Description: Automatic download CPS files and import to SF
 * Version: 1.0.0
 * Author: Talasan Nicholson
 * Author URI: https://www.linkedin.com/in/talasan
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

(new \Powerfin\Main)->execute();