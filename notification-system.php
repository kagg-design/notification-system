<?php
/**
 * Notification System
 *
 * @package              notification-system
 * @author               KAGG Design
 * @license              GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:          Notification System
 * Plugin URI:           https://wordpress.org/plugins/notification-system/
 * Description:          Creates and maintains notification system for users on WordPress site.
 * Version:              2.0.1
 * Requires at least:    5.0
 * Requires PHP:         7.0
 * Author:               KAGG Design
 * Author URI:           http://kagg.eu/en/
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          notification-system
 * Domain Path:          /languages/
 */

namespace KAGG\NotificationSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( defined( 'KAGG_NOTIFICATIONS_VERSION' ) ) {
	return;
}

/**
 * Plugin version.
 */
define( 'KAGG_NOTIFICATIONS_VERSION', '2.0.1' );

/**
 * Plugin path.
 */
define( 'KAGG_NOTIFICATIONS_PATH', __DIR__ );

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
define( 'KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION', '7.0' );

/**
 * Init plugin class on the plugin load.
 */
require_once KAGG_NOTIFICATIONS_PATH . '/vendor/autoload.php';

$notification_system_requirements = new Requirements();

if ( ! $notification_system_requirements->are_requirements_met() ) {
	return;
}

new Notifications();
