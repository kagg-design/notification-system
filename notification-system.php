<?php
/**
 * Plugin Name: Notification System
 * Description: Creates and maintains notification system for users on WordPress site.
 * Author: KAGG Design
 * Author URI: http://kagg.eu/en/
 * Version: 1.0.2
 * Plugin Slug: notification-system
 * Requires at least: 4.4
 * Tested up to: 5.2
 * Requires PHP: 5.6
 *
 * Text Domain: notification-system
 * Domain Path: /languages/
 *
 * @package notification-system
 * @author  KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'KAGG_NOTIFICATIONS_PATH' ) ) {
	/**
	 * Plugin path.
	 */

	define( 'KAGG_NOTIFICATIONS_PATH', dirname( __FILE__ ) );
}

if ( ! defined( 'KAGG_NOTIFICATIONS_URL' ) ) {
	/**
	 * Plugin url.
	 */
	define( 'KAGG_NOTIFICATIONS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'KAGG_NOTIFICATIONS_FILE' ) ) {
	/**
	 * Plugin main file.
	 */
	define( 'KAGG_NOTIFICATIONS_FILE', __FILE__ );
}

if ( ! defined( 'KAGG_NOTIFICATIONS_VERSION' ) ) {
	/**
	 * Plugin version.
	 */
	define( 'KAGG_NOTIFICATIONS_VERSION', '1.0.2' );
}

if ( ! defined( 'KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION' ) ) {
	/**
	 * Minimum required php version.
	 */
	define( 'KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION', '5.6' );
}

/**
 * Init plugin class on plugin load.
 */

static $requirements;
static $plugin;

if ( ! isset( $requirements ) ) {
	require_once KAGG_NOTIFICATIONS_PATH . '/includes/class-kagg-requirements.php'; // We cannot use composer autoloader here.
	$requirements = new KAGG_Requirements();
}

if ( ! isset( $plugin ) ) {
	require_once KAGG_NOTIFICATIONS_PATH . '/vendor/autoload.php';

	$plugin = new KAGG_Notifications();
}
