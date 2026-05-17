<?php
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin singleton.
 */
final class WCA_Email_Tester {

	private static ?self $instance = null;

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		new WCA_Email_Tester_API();
		new WCA_Email_Tester_Logger();
		new WCA_Email_Tester_Admin();

		add_filter( 'plugin_action_links_' . plugin_basename( WCA_ET_FILE ), array( $this, 'action_links' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wc-affiliate-email-tester',
			false,
			dirname( plugin_basename( WCA_ET_FILE ) ) . '/languages'
		);
	}

	private function __clone() {}
	public function __wakeup() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function action_links( array $links ): array {
		$tester_url   = admin_url( 'admin.php?page=wca-email-tester-testing' );
		$settings_url = admin_url( 'admin.php?page=wca-email-tester-settings' );

		array_unshift(
			$links,
			'<a href="' . esc_url( $tester_url ) . '">' . esc_html__( 'Open Tester', 'wc-affiliate-email-tester' ) . '</a>',
			'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'wc-affiliate-email-tester' ) . '</a>'
		);
		return $links;
	}
}
