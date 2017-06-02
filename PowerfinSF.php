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

// if (!wp_next_scheduled('PSF_CRON'))
// 	wp_schedule_event(time(), 'weekly', 'PSF_CRON');

// add_action('PSF_CRON', function() {
// 	(new \Powerfin\Main)->update();
// });

(new \Powerfin\Main(strtotime('-4 month')))->update();