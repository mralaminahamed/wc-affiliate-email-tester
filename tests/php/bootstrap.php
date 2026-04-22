<?php

define( 'WCA_ET_TEST_PLUGIN_DIR', dirname( __DIR__, 2 ) );
define( 'WCA_CORE_TEST_DIR', dirname( WCA_ET_TEST_PLUGIN_DIR ) . '/wc-affiliate' );
define( 'WCA_WC_TEST_DIR', dirname( WCA_ET_TEST_PLUGIN_DIR ) . '/woocommerce' );

// Email tester autoloader (classmap for plugin classes).
require_once WCA_ET_TEST_PLUGIN_DIR . '/vendor/autoload.php';

// WC Affiliate autoloader — also exposes its test dev classes via classmap.
if ( file_exists( WCA_CORE_TEST_DIR . '/vendor/autoload.php' ) ) {
	require_once WCA_CORE_TEST_DIR . '/vendor/autoload.php';
}

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Run the WP test suite installer first." . PHP_EOL; // phpcs:ignore
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

function _wca_et_load_plugins(): void {
	define( 'WC_TAX_ROUNDING_MODE', 'auto' );
	define( 'WC_USE_TRANSACTIONS', false );

	require WCA_WC_TEST_DIR . '/woocommerce.php';
	require WCA_CORE_TEST_DIR . '/wc-affiliate.php';
	require WCA_ET_TEST_PLUGIN_DIR . '/wc-affiliate-email-tester.php';
}

tests_add_filter( 'muplugins_loaded', '_wca_et_load_plugins' );

function _wca_et_install(): void {
	// Install WooCommerce.
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include WCA_WC_TEST_DIR . '/uninstall.php';
	WC_Install::install();

	// Reload capabilities after WC install.
	$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	wp_roles();

	echo 'Installing WooCommerce...' . PHP_EOL; // phpcs:ignore

	// Install WC Affiliate tables.
	WC_Affiliate\Bootstrap\Installer::install();
	echo 'Installing WC Affiliate...' . PHP_EOL; // phpcs:ignore

	// Install email tester log table.
	WCA_Email_Tester_Logger::create_table();
	echo 'Installing WC Affiliate Email Tester...' . PHP_EOL; // phpcs:ignore

	// Truncate WCA tables between test runs.
	global $wpdb;
	foreach ( [ 'wca_visits', 'wca_referrals', 'wca_transactions', 'wca_et_logs' ] as $t ) {
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}{$t}" ); // phpcs:ignore
	}
}

tests_add_filter( 'setup_theme', '_wca_et_install' );

require $_tests_dir . '/includes/bootstrap.php';
