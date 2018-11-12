<?php
/**
 * Plugin Name: Notification
 * Description: Creates and maintains notification system for users on WordPress site.
 * Author: KAGG Design
 * Author URI: http://kagg.eu/en/
 * Version: 1.0.0
 * Plugin Slug: kagg-notification
 * Requires at least: 4.4
 * Tested up to: 5.0
 *
 * Text Domain: kagg-notification
 * Domain Path: /languages/
 *
 * @package kagg-notification
 * @author KAGG Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'KAGG_NOTIFICATION_PATH', dirname( __FILE__ ) );
define( 'KAGG_NOTIFICATION_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'KAGG_NOTIFICATION_FILE', __FILE__ );
define( 'KAGG_NOTIFICATION_VERSION', '1.0.0' );

/**
 * Init plugin class on plugin load.
 */

static $plugin;

if ( ! isset( $plugin ) ) {
	// Require main class of the plugin.
	require_once KAGG_NOTIFICATION_PATH . '/includes/class-kagg-notification.php';

	$plugin = new KAGG_Notification();
}
