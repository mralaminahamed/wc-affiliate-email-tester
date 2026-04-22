<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin pages, asset enqueueing, and form handling.
 */
class WCA_Email_Tester_Admin {

	private WCA_Email_Tester_Logger $logger;

	/** Hook suffixes returned by add_menu_page() / add_submenu_page(). */
	private array $page_hooks = array();

	const PAGES = array(
		'wca-email-tester',
		'wca-email-tester-testing',
		'wca-email-tester-logs',
		'wca-email-tester-settings',
	);

	public function __construct() {
		$this->logger = new WCA_Email_Tester_Logger();

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_filter( 'set_screen_option_wca_et_logs_per_page', array( $this, 'save_screen_option' ), 10, 3 );
	}

	// -----------------------------------------------------------------------
	// Menu registration
	// -----------------------------------------------------------------------

	public function register_menus(): void {
		// Top-level menu.
		$hook = add_menu_page(
			__( 'WCA Email Tester', 'wc-affiliate-email-tester' ),
			__( 'WCA Email Tester', 'wc-affiliate-email-tester' ),
			'manage_options',
			'wca-email-tester',
			array( $this, 'render_dashboard' ),
			'dashicons-email-alt',
			56
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}

		// Rename the auto-generated first submenu entry from "WCA Email Tester" → "Dashboard".
		add_submenu_page(
			'wca-email-tester',
			__( 'Dashboard — WCA Email Tester', 'wc-affiliate-email-tester' ),
			__( 'Dashboard', 'wc-affiliate-email-tester' ),
			'manage_options',
			'wca-email-tester',
			array( $this, 'render_dashboard' )
		);

		// Testing submenu.
		$hook = add_submenu_page(
			'wca-email-tester',
			__( 'Testing — WCA Email Tester', 'wc-affiliate-email-tester' ),
			__( 'Testing', 'wc-affiliate-email-tester' ),
			'manage_options',
			'wca-email-tester-testing',
			array( $this, 'render_testing' )
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}

		// Logs submenu — show live count badge when logs exist.
		$log_count  = WCA_Email_Tester_Logger::table_exists() ? $this->logger->count_logs() : 0;
		$logs_label = $log_count > 0
			? sprintf( 'Logs <span class="awaiting-mod">%d</span>', $log_count )
			: __( 'Logs', 'wc-affiliate-email-tester' );

		$hook = add_submenu_page(
			'wca-email-tester',
			__( 'Logs — WCA Email Tester', 'wc-affiliate-email-tester' ),
			$logs_label,
			'manage_options',
			'wca-email-tester-logs',
			array( $this, 'render_logs' )
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
			add_action( "load-{$hook}", array( $this, 'add_screen_options' ) );
		}

		// Settings submenu.
		$hook = add_submenu_page(
			'wca-email-tester',
			__( 'Settings — WCA Email Tester', 'wc-affiliate-email-tester' ),
			__( 'Settings', 'wc-affiliate-email-tester' ),
			'manage_options',
			'wca-email-tester-settings',
			array( $this, 'render_settings' )
		);

		if ( $hook ) {
			$this->page_hooks[] = $hook;
		}
	}

	public function add_screen_options(): void {
		add_screen_option( 'per_page', array(
			'label'   => __( 'Logs per page', 'wc-affiliate-email-tester' ),
			'default' => 25,
			'option'  => 'wca_et_logs_per_page',
		) );
	}

	public function save_screen_option( $status, string $option, $value ) {
		if ( 'wca_et_logs_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	// -----------------------------------------------------------------------
	// Assets
	// -----------------------------------------------------------------------

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		$this->enqueue_select2();
		$this->enqueue_admin_styles();
		$this->enqueue_admin_scripts();
	}

	/**
	 * Ensure select2 CSS + JS are available on our pages.
	 *
	 * WC registers 'selectWoo' (script) and 'select2' (style) only on its own
	 * admin pages. On our standalone pages we must register them ourselves so
	 * they are available as dependencies.
	 */
	private function enqueue_select2(): void {
		// --- style ----------------------------------------------------------
		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			// WC ships select2 CSS — register it from WC's own asset path.
			$wc_select2_css = WC()->plugin_url() . '/assets/css/select2.css';
			wp_register_style( 'select2', $wc_select2_css, array(), \WC_VERSION );
		}
		wp_enqueue_style( 'select2' );

		// --- script ---------------------------------------------------------
		if ( wp_script_is( 'selectWoo', 'registered' ) ) {
			wp_enqueue_script( 'selectWoo' );
		} elseif ( wp_script_is( 'select2', 'registered' ) ) {
			wp_enqueue_script( 'select2' );
		}
	}

	private function enqueue_admin_styles(): void {
		wp_register_style(
			'wca-email-tester-admin',
			WCA_ET_URL . 'assets/css/admin.css',
			array( 'select2' ),
			$this->asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_style( 'wca-email-tester-admin' );
	}

	private function enqueue_admin_scripts(): void {
		// Use whichever Select2 script handle is available.
		$select2_handle = wp_script_is( 'selectWoo', 'registered' ) ? 'selectWoo' : 'select2';

		wp_register_script(
			'wca-email-tester-admin',
			WCA_ET_URL . 'assets/js/admin.js',
			array( 'jquery', $select2_handle ),
			$this->asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_enqueue_script( 'wca-email-tester-admin' );

		wp_localize_script( 'wca-email-tester-admin', 'wcaetAdmin', array(
			'restUrl'        => esc_url_raw( rest_url( WCA_Email_Tester_API::NS ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'fieldMap'       => WCA_Email_Tester_Sender::field_for_type(),
			'select2Handle'  => $select2_handle,
		) );
	}

	/**
	 * Return a cache-busting version string using the file's mtime.
	 * Falls back to WCA_ET_VERSION when the file is not found.
	 */
	private function asset_version( string $relative_path ): string {
		$abs = WCA_ET_PATH . $relative_path;
		return file_exists( $abs ) ? (string) filemtime( $abs ) : WCA_ET_VERSION;
	}

	public function body_class( string $classes ): string {
		$page = sanitize_text_field( $_GET['page'] ?? '' );
		if ( in_array( $page, self::PAGES, true ) ) {
			$classes = preg_replace( '/\bfolded\b/', '', $classes );
			$classes .= ' wcaet-page';
		}
		return $classes;
	}

	// -----------------------------------------------------------------------
	// Page renderers
	// -----------------------------------------------------------------------

	public function render_dashboard(): void {
		$total      = $this->logger->count_logs();
		$sent       = $this->logger->count_logs( array( 'status' => 1 ) );
		$failed     = $this->logger->count_logs( array( 'status' => 0 ) );
		$test_count = $this->logger->count_logs( array( 'source' => 'test' ) );
		$this->render_page_nav();
		$this->load_template( 'dashboard', compact( 'total', 'sent', 'failed', 'test_count' ) );
	}

	public function render_testing(): void {
		$result   = null;
		$settings = $this->logger->get_settings();

		if ( isset( $_POST['wca_et_send_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wca_et_send_nonce'] ) ), 'wca_et_send' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wc-affiliate-email-tester' ) );
			}

			$email_type     = sanitize_text_field( $_POST['email_type'] ?? '' );
			$affiliate_id   = absint( $_POST['affiliate_id'] ?? 0 );
			$referral_id    = absint( $_POST['referral_id'] ?? 0 );
			$transaction_id = absint( $_POST['transaction_id'] ?? 0 );
			$override_email = sanitize_email( $_POST['override_email'] ?? '' );
			$dry_run        = ! empty( $_POST['dry_run'] );

			$sender = new WCA_Email_Tester_Sender( $override_email, $dry_run );
			$result = $sender->send( $email_type, $affiliate_id, $referral_id, $transaction_id );
		}

		$email_types = WCA_Email_Tester_Sender::email_types();
		$field_map   = WCA_Email_Tester_Sender::field_for_type();

		// Pre-selected values for Select2.
		$selected_affiliate   = absint( $_POST['affiliate_id'] ?? 0 );
		$selected_referral    = absint( $_POST['referral_id'] ?? 0 );
		$selected_transaction = absint( $_POST['transaction_id'] ?? 0 );

		$affiliate_option   = $selected_affiliate ? WCA_Email_Tester_API::get_affiliate_option( $selected_affiliate ) : null;
		$referral_option    = $selected_referral ? WCA_Email_Tester_API::get_referral_option( $selected_referral ) : null;
		$transaction_option = $selected_transaction ? WCA_Email_Tester_API::get_transaction_option( $selected_transaction ) : null;

		$this->render_page_nav();
		$this->load_template( 'testing', compact(
			'result', 'email_types', 'field_map', 'settings',
			'selected_affiliate', 'selected_referral', 'selected_transaction',
			'affiliate_option', 'referral_option', 'transaction_option'
		) );
	}

	public function render_logs(): void {
		$action = sanitize_text_field( $_GET['action'] ?? '' );

		if ( 'view' === $action ) {
			$log_id = absint( $_GET['log_id'] ?? 0 );
			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'wca_et_view_log_' . $log_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wc-affiliate-email-tester' ) );
			}
			$log = $this->logger->get_log( $log_id );
			if ( $log ) {
				$this->load_template( 'log-view', compact( 'log' ) );
				return;
			}
		}

		if ( 'delete' === $action ) {
			$log_id = absint( $_GET['log_id'] ?? 0 );
			if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'wca_et_delete_log_' . $log_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wc-affiliate-email-tester' ) );
			}
			$this->logger->delete_log( $log_id );
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=wca-email-tester-logs&deleted=1' ) ) );
			exit;
		}

		// Bulk actions.
		if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
			check_admin_referer( 'bulk-logs' );
			$ids = array_map( 'absint', (array) ( $_POST['log_ids'] ?? array() ) );
			if ( $ids ) {
				$this->logger->delete_logs( $ids );
			}
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=wca-email-tester-logs&deleted=' . count( $ids ) ) ) );
			exit;
		}

		// Clear all logs.
		if ( isset( $_POST['wca_et_clear_logs_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wca_et_clear_logs_nonce'] ) ), 'wca_et_clear_logs' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wc-affiliate-email-tester' ) );
			}
			$this->logger->truncate();
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=wca-email-tester-logs&cleared=1' ) ) );
			exit;
		}

		$list_table = new WCA_Email_Tester_Log_List( $this->logger );
		$list_table->prepare_items();
		$this->render_page_nav();
		$this->load_template( 'logs', compact( 'list_table' ) );
	}

	public function render_settings(): void {
		$saved   = false;
		$options = $this->logger->get_settings();

		if ( isset( $_POST['wca_et_settings_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wca_et_settings_nonce'] ) ), 'wca_et_save_settings' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wc-affiliate-email-tester' ) );
			}

			$options = array(
				'logger_enabled'                 => ! empty( $_POST['logger_enabled'] ),
				'logger_log_test_emails'         => ! empty( $_POST['logger_log_test_emails'] ),
				'logger_retention_count_enabled' => ! empty( $_POST['logger_retention_count_enabled'] ),
				'logger_retention_count'         => absint( $_POST['logger_retention_count'] ?? 500 ),
				'logger_retention_days_enabled'  => ! empty( $_POST['logger_retention_days_enabled'] ),
				'logger_retention_days'          => absint( $_POST['logger_retention_days'] ?? 30 ),
				'logger_delete_on_uninstall'     => ! empty( $_POST['logger_delete_on_uninstall'] ),
			);

			update_option( WCA_Email_Tester_Logger::OPTION_KEY, $options );
			$saved = true;
		}

		$this->render_page_nav();
		$this->load_template( 'settings', compact( 'options', 'saved' ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Render the shared sub-navigation tab bar shown on every page.
	 */
	public function render_page_nav(): void {
		$current = sanitize_text_field( $_GET['page'] ?? '' );

		$nav_items = array(
			'wca-email-tester'          => array(
				'label' => __( 'Dashboard', 'wc-affiliate-email-tester' ),
				'icon'  => 'mail',
			),
			'wca-email-tester-testing'  => array(
				'label' => __( 'Testing', 'wc-affiliate-email-tester' ),
				'icon'  => 'send',
			),
			'wca-email-tester-logs'     => array(
				'label' => __( 'Logs', 'wc-affiliate-email-tester' ),
				'icon'  => 'list',
			),
			'wca-email-tester-settings' => array(
				'label' => __( 'Settings', 'wc-affiliate-email-tester' ),
				'icon'  => 'settings',
			),
		);

		echo '<nav class="wcaet-page-nav" aria-label="' . esc_attr__( 'Email Tester Navigation', 'wc-affiliate-email-tester' ) . '">';
		foreach ( $nav_items as $slug => $item ) {
			$is_active = ( $current === $slug );
			printf(
				'<a href="%s" class="wcaet-page-nav__item%s">%s<span>%s</span></a>',
				esc_url( admin_url( 'admin.php?page=' . $slug ) ),
				$is_active ? ' is-active' : '',
				WCA_Email_Tester_Icons::get( $item['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $item['label'] )
			);
		}
		echo '</nav>';
	}

	public function load_template( string $name, array $args = array() ): void {
		$file = WCA_ET_PATH . 'templates/admin/' . $name . '.php';
		if ( file_exists( $file ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			include $file;
		}
	}

	public static function wrap_preview_html( string $body ): string {
		return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;">' . $body . '</body></html>';
	}
}
