<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Log list table for the Logs admin page.
 */
class WCA_Email_Tester_Log_List extends WP_List_Table {

	private WCA_Email_Tester_Logger $logger;

	public function __construct( WCA_Email_Tester_Logger $logger ) {
		parent::__construct( array(
			'singular' => 'log',
			'plural'   => 'logs',
			'ajax'     => false,
		) );
		$this->logger = $logger;
	}

	public function get_columns(): array {
		return array(
			'cb'        => '<input type="checkbox">',
			'id'        => __( 'ID', 'wc-affiliate-email-tester' ),
			'timestamp' => __( 'Date / Time', 'wc-affiliate-email-tester' ),
			'to_email'  => __( 'Recipient', 'wc-affiliate-email-tester' ),
			'subject'   => __( 'Subject', 'wc-affiliate-email-tester' ),
			'status'    => __( 'Status', 'wc-affiliate-email-tester' ),
			'source'    => __( 'Source', 'wc-affiliate-email-tester' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'id'        => array( 'id', true ),
			'timestamp' => array( 'timestamp', false ),
			'status'    => array( 'status', false ),
			'source'    => array( 'source', false ),
		);
	}

	protected function get_bulk_actions(): array {
		return array( 'delete' => __( 'Delete', 'wc-affiliate-email-tester' ) );
	}

	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'wca_et_logs_per_page', 25 );
		$paged    = $this->get_pagenum();

		$args = array(
			'page'     => $paged,
			'per_page' => $per_page,
			'orderby'  => sanitize_text_field( $_REQUEST['orderby'] ?? 'id' ),
			'order'    => sanitize_text_field( $_REQUEST['order'] ?? 'DESC' ),
			'status'   => sanitize_text_field( $_REQUEST['log_status'] ?? '' ),
			'source'   => sanitize_text_field( $_REQUEST['log_source'] ?? '' ),
			'search'   => sanitize_text_field( $_REQUEST['s'] ?? '' ),
		);

		$total = $this->logger->count_logs( $args );

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
		) );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $this->logger->get_logs( $args );
	}

	protected function column_cb( $item ): string {
		return '<input type="checkbox" name="log_ids[]" value="' . absint( $item->id ) . '">';
	}

	protected function column_id( $item ): string {
		$view_url   = admin_url( 'admin.php?page=wca-email-tester-logs&action=view&log_id=' . absint( $item->id ) . '&_wpnonce=' . wp_create_nonce( 'wca_et_view_log_' . $item->id ) );
		$delete_url = admin_url( 'admin.php?page=wca-email-tester-logs&action=delete&log_id=' . absint( $item->id ) . '&_wpnonce=' . wp_create_nonce( 'wca_et_delete_log_' . $item->id ) );

		$row_actions = array(
			'view'   => '<a href="' . esc_url( $view_url ) . '">' . esc_html__( 'View', 'wc-affiliate-email-tester' ) . '</a>',
			'delete' => '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this log entry?', 'wc-affiliate-email-tester' ) ) . '\')">' . esc_html__( 'Delete', 'wc-affiliate-email-tester' ) . '</a>',
		);

		return '<strong>#' . absint( $item->id ) . '</strong>' . $this->row_actions( $row_actions );
	}

	protected function column_timestamp( $item ): string {
		return esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item->timestamp ) ) );
	}

	protected function column_to_email( $item ): string {
		$email = esc_html( $item->to_email );
		return strlen( $email ) > 50 ? '<span title="' . esc_attr( $item->to_email ) . '">' . esc_html( substr( $item->to_email, 0, 47 ) ) . '…</span>' : $email;
	}

	protected function column_subject( $item ): string {
		$subject = esc_html( $item->subject );
		return strlen( $subject ) > 60 ? '<span title="' . esc_attr( $item->subject ) . '">' . esc_html( substr( $item->subject, 0, 57 ) ) . '…</span>' : $subject;
	}

	protected function column_status( $item ): string {
		if ( $item->status ) {
			return '<span class="wcaet-badge wcaet-badge--sent">' . esc_html__( 'Sent', 'wc-affiliate-email-tester' ) . '</span>';
		}
		return '<span class="wcaet-badge wcaet-badge--failed">' . esc_html__( 'Failed', 'wc-affiliate-email-tester' ) . '</span>';
	}

	protected function column_source( $item ): string {
		if ( 'test' === $item->source ) {
			return '<span class="wcaet-badge wcaet-badge--source-test">' . esc_html__( 'Test', 'wc-affiliate-email-tester' ) . '</span>';
		}
		return '<span class="wcaet-badge wcaet-badge--source-live">' . esc_html__( 'Live', 'wc-affiliate-email-tester' ) . '</span>';
	}

	protected function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '' );
	}

	protected function get_views(): array {
		$current = sanitize_text_field( $_REQUEST['log_status'] ?? '' );
		$source  = sanitize_text_field( $_REQUEST['log_source'] ?? '' );
		$base    = admin_url( 'admin.php?page=wca-email-tester-logs' );

		$views = array(
			'all'    => sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( $base ),
				'' === $current && '' === $source ? 'class="current"' : '',
				esc_html__( 'All', 'wc-affiliate-email-tester' ),
				$this->logger->count_logs()
			),
			'sent'   => sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'log_status', '1', $base ) ),
				'1' === $current ? 'class="current"' : '',
				esc_html__( 'Sent', 'wc-affiliate-email-tester' ),
				$this->logger->count_logs( array( 'status' => 1 ) )
			),
			'failed' => sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'log_status', '0', $base ) ),
				'0' === $current ? 'class="current"' : '',
				esc_html__( 'Failed', 'wc-affiliate-email-tester' ),
				$this->logger->count_logs( array( 'status' => 0 ) )
			),
			'live'   => sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'log_source', 'live', $base ) ),
				'live' === $source ? 'class="current"' : '',
				esc_html__( 'Live', 'wc-affiliate-email-tester' ),
				$this->logger->count_logs( array( 'source' => 'live' ) )
			),
			'test'   => sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'log_source', 'test', $base ) ),
				'test' === $source ? 'class="current"' : '',
				esc_html__( 'Test', 'wc-affiliate-email-tester' ),
				$this->logger->count_logs( array( 'source' => 'test' ) )
			),
		);

		return $views;
	}
}
