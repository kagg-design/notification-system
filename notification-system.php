<?php
/**
 * Plugin Name: Notification System
 * Description: Creates and maintains notification system for users on WordPress site.
 * Author: KAGG Design
 * Author URI: http://kagg.eu/en/
 * Version: 1.0.4
 * Plugin Slug: notification-system
 * Requires at least: 4.4
 * Tested up to: 5.4
 * Requires PHP: 5.6
 *
 * Text Domain: notification-system
 * Domain Path: /languages/
 *
 * @package notification-system
 * @author  KAGG Design
 */

namespace KAGG\Notification_System;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( defined( 'KAGG_NOTIFICATIONS_VERSION' ) ) {
	return;
}

/**
 * Plugin version.
 */
define( 'KAGG_NOTIFICATIONS_VERSION', '1.0.4' );

/**
 * Plugin path.
 */
define( 'KAGG_NOTIFICATIONS_PATH', dirname( __FILE__ ) );

/**
 * Plugin url.
 */
define( 'KAGG_NOTIFICATIONS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Plugin main file.
 */
define( 'KAGG_NOTIFICATIONS_FILE', __FILE__ );

/**
 * Minimum required php version.
 */
define( 'KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION', '5.6' );

/**
 * Init plugin class on plugin load.
 */

require_once KAGG_NOTIFICATIONS_PATH . '/vendor/autoload.php';

$notification_system_requirements = new Requirements();

if ( ! $notification_system_requirements->are_requirements_met() ) {
	return;
}

new Notifications();
