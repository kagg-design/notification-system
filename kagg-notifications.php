<?php
/**
 * Plugin Name: Notifications
 * Description: Creates and maintains notification system for users on WordPress site.
 * Author: KAGG Design
 * Author URI: http://kagg.eu/en/
 * Version: 1.0.0
 * Plugin Slug: kagg-notifications
 * Requires at least: 4.4
 * Tested up to: 5.0
 *
 * Text Domain: kagg-notifications
 * Domain Path: /languages/
 *
 * @package kagg-notifications
 * @author KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
 * Plugin version.
 */
define( 'KAGG_NOTIFICATIONS_VERSION', '1.0.0' );

/**
 * Init plugin class on plugin load.
 */

static $plugin;

if ( ! isset( $plugin ) ) {
	if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
		require_once KAGG_NOTIFICATIONS_PATH . '/vendor/autoload.php';
	} else {
		require_once KAGG_NOTIFICATIONS_PATH . '/vendor/autoload_52.php';
	}

	$plugin = new KAGG_Notifications();
}
