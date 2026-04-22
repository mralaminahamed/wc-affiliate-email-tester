<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wca-email-tester-logger.php';

$settings = get_option( 'wca_email_tester_settings', array() );
if ( ! empty( $settings['logger_delete_on_uninstall'] ) ) {
	WCA_Email_Tester_Logger::drop_table();
}

delete_option( 'wca_email_tester_settings' );
