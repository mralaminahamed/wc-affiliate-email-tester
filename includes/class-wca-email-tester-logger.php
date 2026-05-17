<?php
defined( 'ABSPATH' ) || exit;

/**
 * Captures all outgoing wp_mail() calls to a DB table.
 */
class WCA_Email_Tester_Logger {

	const TABLE_SUFFIX = 'wca_et_logs';
	const OPTION_KEY   = 'wca_email_tester_settings';
	const CRON_HOOK    = 'wca_email_tester_log_cleanup';

	private bool $is_test_send = false;
	private array $pending_args = array();

	/** @var array|null Memoized settings (per request). */
	private ?array $settings_cache = null;

	public function __construct() {
		add_filter( 'wp_mail', array( $this, '_capture_args' ), PHP_INT_MAX );
		add_action( 'wp_mail_succeeded', array( $this, '_log_success' ) );
		add_action( 'wp_mail_failed', array( $this, '_log_failure' ) );
		add_action( 'wca_email_tester_before_test_send', array( $this, '_mark_test' ) );
		add_action( 'wca_email_tester_after_test_send', array( $this, '_unmark_test' ) );
		add_action( self::CRON_HOOK, array( $this, 'purge_old_logs' ) );
		add_action( 'update_option_' . self::OPTION_KEY, array( $this, 'flush_settings_cache' ) );
		add_action( 'add_option_' . self::OPTION_KEY, array( $this, 'flush_settings_cache' ) );
	}

	// -----------------------------------------------------------------------
	// Activation / deactivation helpers (called statically)
	// -----------------------------------------------------------------------

	public static function create_table(): void {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE_SUFFIX;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			timestamp datetime NOT NULL,
			to_email varchar(500) NOT NULL DEFAULT '',
			subject varchar(500) NOT NULL DEFAULT '',
			message longtext NOT NULL,
			headers text NOT NULL,
			attachments text NOT NULL,
			status tinyint(1) NOT NULL DEFAULT 1,
			error varchar(500) NOT NULL DEFAULT '',
			source varchar(20) NOT NULL DEFAULT 'live',
			PRIMARY KEY (id),
			KEY timestamp (timestamp),
			KEY status (status),
			KEY source (source)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	public static function table_exists(): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (bool) $wpdb->get_var( 'SHOW TABLES LIKE \'' . self::table() . '\'' );
	}

	public static function drop_table(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::TABLE_SUFFIX );
	}

	public static function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	public static function unschedule_cleanup(): void {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
	}

	// -----------------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------------

	/** @internal */
	public function _capture_args( array $args ): array {
		$this->pending_args = $args;
		return $args;
	}

	/** @internal */
	public function _mark_test(): void {
		$this->is_test_send = true;
	}

	/** @internal */
	public function _unmark_test(): void {
		$this->is_test_send = false;
	}

	/** @internal */
	public function _log_success( array $mail_data ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		if ( $this->is_test_send && ! $this->get_settings()['logger_log_test_emails'] ) {
			return;
		}
		$this->insert_log( $mail_data, 1, '', $this->is_test_send ? 'test' : 'live' );
	}

	/** @internal */
	public function _log_failure( \WP_Error $error ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}
		$mail_data = $this->pending_args ?: array(
			'to'          => '',
			'subject'     => '',
			'message'     => '',
			'headers'     => array(),
			'attachments' => array(),
		);
		$this->insert_log( $mail_data, 0, $error->get_error_message(), $this->is_test_send ? 'test' : 'live' );
	}

	// -----------------------------------------------------------------------
	// CRUD
	// -----------------------------------------------------------------------

	private function insert_log( array $data, int $status, string $error, string $source ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE_SUFFIX,
			array(
				'timestamp'   => current_time( 'mysql' ),
				'to_email'    => is_array( $data['to'] ?? '' ) ? implode( ', ', $data['to'] ) : (string) ( $data['to'] ?? '' ),
				'subject'     => (string) ( $data['subject'] ?? '' ),
				'message'     => (string) ( $data['message'] ?? '' ),
				'headers'     => maybe_serialize( $data['headers'] ?? array() ),
				'attachments' => maybe_serialize( $data['attachments'] ?? array() ),
				'status'      => $status,
				'error'       => $error,
				'source'      => $source,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	public function get_logs( array $args = array() ): array {
		global $wpdb;
		$table    = $wpdb->prefix . self::TABLE_SUFFIX;
		$defaults = array(
			'page'     => 1,
			'per_page' => 25,
			'status'   => '',
			'source'   => '',
			'search'   => '',
			'orderby'  => 'id',
			'order'    => 'DESC',
		);
		$args     = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( '' !== $args['status'] ) {
			$where[]  = 'status = %d';
			$values[] = (int) $args['status'];
		}
		if ( '' !== $args['source'] ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}
		if ( '' !== $args['search'] ) {
			$where[]  = '(to_email LIKE %s OR subject LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$orderby   = in_array( $args['orderby'], array( 'id', 'timestamp', 'status', 'source' ), true ) ? $args['orderby'] : 'id';
		$order     = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit     = absint( $args['per_page'] );
		$offset    = ( absint( $args['page'] ) - 1 ) * $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $values ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				array_merge( $values, array( $limit, $offset ) )
			);
		} else {
			$sql = "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";
		}
		// phpcs:enable

		return $wpdb->get_results( $sql ) ?: array(); // phpcs:ignore
	}

	/**
	 * Single-query aggregate of total / sent / failed / test counts.
	 *
	 * @return array{total:int,sent:int,failed:int,test_count:int}
	 */
	public function get_stats(): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			"SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS sent,
				SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS failed,
				SUM(CASE WHEN source = 'test' THEN 1 ELSE 0 END) AS test_count
			FROM {$table}",
			ARRAY_A
		);

		return array(
			'total'      => (int) ( $row['total'] ?? 0 ),
			'sent'       => (int) ( $row['sent'] ?? 0 ),
			'failed'     => (int) ( $row['failed'] ?? 0 ),
			'test_count' => (int) ( $row['test_count'] ?? 0 ),
		);
	}

	public function count_logs( array $args = array() ): int {
		global $wpdb;
		$table  = $wpdb->prefix . self::TABLE_SUFFIX;
		$where  = array( '1=1' );
		$values = array();

		if ( isset( $args['status'] ) && '' !== $args['status'] ) {
			$where[]  = 'status = %d';
			$values[] = (int) $args['status'];
		}
		if ( isset( $args['source'] ) && '' !== $args['source'] ) {
			$where[]  = 'source = %s';
			$values[] = $args['source'];
		}
		if ( isset( $args['search'] ) && '' !== $args['search'] ) {
			$where[]  = '(to_email LIKE %s OR subject LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $values ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $values ) );
		} else {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}
		// phpcs:enable

		return (int) $count;
	}

	public function get_log( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
			'SELECT * FROM ' . $wpdb->prefix . self::TABLE_SUFFIX . ' WHERE id = %d LIMIT 1',
			$id
		) ) ?: null;
	}

	public function delete_log( int $id ): void {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::TABLE_SUFFIX, array( 'id' => $id ), array( '%d' ) );
	}

	public function delete_logs( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( // phpcs:ignore
			"DELETE FROM {$wpdb->prefix}" . self::TABLE_SUFFIX . " WHERE id IN ({$placeholders})", // phpcs:ignore
			$ids
		) );
	}

	public function truncate(): void {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . self::TABLE_SUFFIX ); // phpcs:ignore
	}

	public function purge_old_logs(): void {
		$settings = $this->get_settings();
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;

		if ( $settings['logger_retention_count_enabled'] ) {
			$keep = (int) $settings['logger_retention_count'];
			if ( $keep > 0 ) {
				$wpdb->query( $wpdb->prepare( // phpcs:ignore
					"DELETE FROM {$table} WHERE id NOT IN (SELECT id FROM (SELECT id FROM {$table} ORDER BY id DESC LIMIT %d) AS keep)", // phpcs:ignore
					$keep
				) );
			}
		}

		if ( $settings['logger_retention_days_enabled'] ) {
			$days = (int) $settings['logger_retention_days'];
			if ( $days > 0 ) {
				$wpdb->query( $wpdb->prepare( // phpcs:ignore
					"DELETE FROM {$table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
					$days
				) );
			}
		}
	}

	// -----------------------------------------------------------------------
	// Settings helpers
	// -----------------------------------------------------------------------

	public function is_enabled(): bool {
		return (bool) $this->get_settings()['logger_enabled'];
	}

	public function get_settings(): array {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		$defaults = array(
			'logger_enabled'                 => true,
			'logger_log_test_emails'         => true,
			'logger_retention_count_enabled' => true,
			'logger_retention_count'         => 500,
			'logger_retention_days_enabled'  => false,
			'logger_retention_days'          => 30,
			'logger_delete_on_uninstall'     => false,
		);
		$saved = get_option( self::OPTION_KEY, array() );

		$this->settings_cache = wp_parse_args( $saved, $defaults );
		return $this->settings_cache;
	}

	/**
	 * Invalidate the memoized settings cache. Call after update_option().
	 */
	public function flush_settings_cache(): void {
		$this->settings_cache = null;
	}
}
