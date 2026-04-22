<?php
/**
 * Plugin Name: WC Affiliate Email Tester
 * Plugin URI:  https://github.com/mralaminahamed/wc-affiliate-email-tester
 * Description: Developer tool to test, preview, and debug WC Affiliate email notifications without real triggers or live SMTP.
 * Version:     1.0.0
 * Author:      Al Amin Ahamed
 * Author URI:  https://github.com/mralaminahamed
 * Text Domain: wc-affiliate-email-tester
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

// Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

define( 'WCA_ET_VERSION', '1.0.0' );
define( 'WCA_ET_FILE', __FILE__ );
define( 'WCA_ET_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCA_ET_URL', plugin_dir_url( __FILE__ ) );
define( 'WCA_ET_MIN_PHP', '8.0' );

// Activation / deactivation hooks.
register_activation_hook( __FILE__, 'wca_et_activate' );
register_deactivation_hook( __FILE__, 'wca_et_deactivate' );

/**
 * Activation: create DB table and schedule cron.
 */
function wca_et_activate(): void {
	WCA_Email_Tester_Logger::create_table();
	WCA_Email_Tester_Logger::schedule_cleanup();
}

/**
 * Deactivation: remove cron event.
 */
function wca_et_deactivate(): void {
	WCA_Email_Tester_Logger::unschedule_cleanup();
}

/**
 * Boot the plugin after all plugins are loaded.
 */
add_action( 'plugins_loaded', 'wca_et_init' );

function wca_et_init(): void {
	// PHP version check.
	if ( version_compare( PHP_VERSION, WCA_ET_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html( sprintf(
					/* translators: %s minimum PHP version */
					__( 'WC Affiliate Email Tester requires PHP %s or higher.', 'wc-affiliate-email-tester' ),
					WCA_ET_MIN_PHP
				) ) .
				'</p></div>';
		} );
		return;
	}

	// WC Affiliate core check.
	if ( ! defined( 'WC_AFFILIATE_VERSION' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'WC Affiliate Email Tester requires the WC Affiliate plugin to be active.', 'wc-affiliate-email-tester' ) .
				'</p></div>';
		} );
		return;
	}

	wca_email_tester();
}

/**
 * Global accessor for the main plugin instance.
 */
function wca_email_tester(): WCA_Email_Tester {
	return WCA_Email_Tester::instance();
}
