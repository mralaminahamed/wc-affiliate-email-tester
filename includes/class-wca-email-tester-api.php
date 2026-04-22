<?php
defined( 'ABSPATH' ) || exit;

/**
 * REST API endpoints for Select2 AJAX searches.
 */
class WCA_Email_Tester_API {

	const NS = 'wca-email-tester/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$cap = array( $this, 'check_permission' );

		register_rest_route( self::NS, '/affiliates', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_affiliates' ),
			'permission_callback' => $cap,
			'args'                => array(
				'search'   => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ),
				'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
			),
		) );

		register_rest_route( self::NS, '/referrals', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_referrals' ),
			'permission_callback' => $cap,
			'args'                => array(
				'search'   => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ),
				'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
			),
		) );

		register_rest_route( self::NS, '/transactions', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_transactions' ),
			'permission_callback' => $cap,
			'args'                => array(
				'search'   => array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ),
				'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
			),
		) );
	}

	public function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_affiliates( \WP_REST_Request $request ): \WP_REST_Response {
		$search   = $request->get_param( 'search' );
		$per_page = min( 100, max( 1, $request->get_param( 'per_page' ) ) );

		$query_args = array(
			'role'    => 'affiliate',
			'number'  => $per_page,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);

		if ( $search ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$users   = get_users( $query_args );
		$results = array();

		foreach ( $users as $user ) {
			$results[] = array(
				'id'   => $user->ID,
				'text' => sprintf( '#%d – %s (%s)', $user->ID, $user->display_name, $user->user_email ),
			);
		}

		return rest_ensure_response( $results );
	}

	public function get_referrals( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$search   = $request->get_param( 'search' );
		$per_page = min( 100, max( 1, $request->get_param( 'per_page' ) ) );
		$table    = $wpdb->prefix . 'wca_referrals';

		if ( $search ) {
			// Search by referral ID, order_id, or affiliate ID.
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT r.id, r.affiliate, r.order_id, r.commission, u.display_name
				FROM {$table} r
				LEFT JOIN {$wpdb->users} u ON r.affiliate = u.ID
				WHERE r.id LIKE %s OR r.order_id LIKE %s OR r.affiliate LIKE %s OR u.display_name LIKE %s
				ORDER BY r.id DESC
				LIMIT %d",
				$like, $like, $like, $like, $per_page
			) );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT r.id, r.affiliate, r.order_id, r.commission, u.display_name
				FROM {$table} r
				LEFT JOIN {$wpdb->users} u ON r.affiliate = u.ID
				ORDER BY r.id DESC
				LIMIT %d",
				$per_page
			) );
		}

		$results = array();
		foreach ( $rows as $row ) {
			$affiliate_name = $row->display_name ?: sprintf( 'User #%d', $row->affiliate );
			$results[]      = array(
				'id'   => (int) $row->id,
				'text' => sprintf(
					'#%d – Order #%s | Affiliate: %s | Commission: %s',
					$row->id,
					$row->order_id,
					$affiliate_name,
					wc_price( $row->commission )
				),
			);
		}

		return rest_ensure_response( $results );
	}

	public function get_transactions( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$search   = $request->get_param( 'search' );
		$per_page = min( 100, max( 1, $request->get_param( 'per_page' ) ) );
		$table    = $wpdb->prefix . 'wca_transactions';

		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT t.id, t.affiliate, t.amount, t.type, u.display_name
				FROM {$table} t
				LEFT JOIN {$wpdb->users} u ON t.affiliate = u.ID
				WHERE t.id LIKE %s OR t.affiliate LIKE %s OR u.display_name LIKE %s OR t.type LIKE %s
				ORDER BY t.id DESC
				LIMIT %d",
				$like, $like, $like, $like, $per_page
			) );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT t.id, t.affiliate, t.amount, t.type, u.display_name
				FROM {$table} t
				LEFT JOIN {$wpdb->users} u ON t.affiliate = u.ID
				ORDER BY t.id DESC
				LIMIT %d",
				$per_page
			) );
		}

		$results = array();
		foreach ( $rows as $row ) {
			$affiliate_name = $row->display_name ?: sprintf( 'User #%d', $row->affiliate );
			$results[]      = array(
				'id'   => (int) $row->id,
				'text' => sprintf(
					'#%d – %s | Affiliate: %s | Amount: %s',
					$row->id,
					ucfirst( str_replace( '_', ' ', $row->type ) ),
					$affiliate_name,
					wc_price( $row->amount )
				),
			);
		}

		return rest_ensure_response( $results );
	}

	// -----------------------------------------------------------------------
	// Single-item option loaders (for pre-selected values)
	// -----------------------------------------------------------------------

	public static function get_affiliate_option( int $id ): ?array {
		$user = get_userdata( $id );
		if ( ! $user ) {
			return null;
		}
		return array(
			'id'   => $user->ID,
			'text' => sprintf( '#%d – %s (%s)', $user->ID, $user->display_name, $user->user_email ),
		);
	}

	public static function get_referral_option( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
			"SELECT r.id, r.affiliate, r.order_id, r.commission, u.display_name
			FROM {$wpdb->prefix}wca_referrals r
			LEFT JOIN {$wpdb->users} u ON r.affiliate = u.ID
			WHERE r.id = %d LIMIT 1",
			$id
		) );
		if ( ! $row ) {
			return null;
		}
		$affiliate_name = $row->display_name ?: sprintf( 'User #%d', $row->affiliate );
		return array(
			'id'   => (int) $row->id,
			'text' => sprintf(
				'#%d – Order #%s | Affiliate: %s | Commission: %s',
				$row->id,
				$row->order_id,
				$affiliate_name,
				wc_price( $row->commission )
			),
		);
	}

	public static function get_transaction_option( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
			"SELECT t.id, t.affiliate, t.amount, t.type, u.display_name
			FROM {$wpdb->prefix}wca_transactions t
			LEFT JOIN {$wpdb->users} u ON t.affiliate = u.ID
			WHERE t.id = %d LIMIT 1",
			$id
		) );
		if ( ! $row ) {
			return null;
		}
		$affiliate_name = $row->display_name ?: sprintf( 'User #%d', $row->affiliate );
		return array(
			'id'   => (int) $row->id,
			'text' => sprintf(
				'#%d – %s | Affiliate: %s | Amount: %s',
				$row->id,
				ucfirst( str_replace( '_', ' ', $row->type ) ),
				$affiliate_name,
				wc_price( $row->amount )
			),
		);
	}
}
