<?php

/**
 * Tests for WCA_Email_Tester_Logger — DB CRUD, retention, and source tagging.
 */
class LoggerTest extends WCA_ET_TestCase {

	private WCA_Email_Tester_Logger $logger;

	public function set_up(): void {
		parent::set_up();
		$this->logger = new WCA_Email_Tester_Logger();
		$this->logger->truncate();
	}

	public function tear_down(): void {
		$this->logger->truncate();
		parent::tear_down();
	}

	// ===================================================================
	// Table existence
	// ===================================================================

	public function test_log_table_exists(): void {
		global $wpdb;
		$table  = $wpdb->prefix . WCA_Email_Tester_Logger::TABLE_SUFFIX;
		$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$this->assertEquals( $table, $result );
	}

	// ===================================================================
	// Settings defaults
	// ===================================================================

	public function test_get_settings_returns_array_with_required_keys(): void {
		$settings = $this->logger->get_settings();

		$required = [
			'logger_enabled',
			'logger_log_test_emails',
			'logger_retention_count_enabled',
			'logger_retention_count',
			'logger_retention_days_enabled',
			'logger_retention_days',
			'logger_delete_on_uninstall',
		];

		foreach ( $required as $key ) {
			$this->assertArrayHasKey( $key, $settings, "Settings missing key: {$key}" );
		}
	}

	public function test_is_enabled_returns_bool(): void {
		$this->assertIsBool( $this->logger->is_enabled() );
	}

	public function test_logger_is_enabled_by_default(): void {
		delete_option( WCA_Email_Tester_Logger::OPTION_KEY );
		$logger = new WCA_Email_Tester_Logger();
		$this->assertTrue( $logger->is_enabled() );
	}

	// ===================================================================
	// Log capture via wp_mail hooks
	// ===================================================================

	public function test_logger_captures_successful_wp_mail(): void {
		// Ensure logger is on.
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [ 'logger_enabled' => true ] );

		// Simulate wp_mail success lifecycle.
		$args = [
			'to'          => 'capture@example.org',
			'subject'     => 'Capture Test',
			'message'     => '<p>Hello</p>',
			'headers'     => [ 'Content-Type: text/html' ],
			'attachments' => [],
		];

		// Trigger the capture filter.
		apply_filters( 'wp_mail', $args );
		// Trigger success action.
		do_action( 'wp_mail_succeeded', $args );

		$count = $this->logger->count_logs( [ 'status' => 1 ] );
		$this->assertGreaterThanOrEqual( 1, $count );

		$logs = $this->logger->get_logs( [ 'search' => 'capture@example.org' ] );
		$this->assertNotEmpty( $logs );
		$this->assertEquals( 'Capture Test', $logs[0]->subject );
		$this->assertEquals( 'capture@example.org', $logs[0]->to_email );
		$this->assertEquals( 1, (int) $logs[0]->status );
	}

	public function test_logger_captures_failed_wp_mail(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [ 'logger_enabled' => true ] );

		$args = [
			'to'          => 'fail@example.org',
			'subject'     => 'Fail Test',
			'message'     => '<p>Error</p>',
			'headers'     => [],
			'attachments' => [],
		];

		apply_filters( 'wp_mail', $args );
		$error = new WP_Error( 'wp_mail_failed', 'SMTP connection refused' );
		do_action( 'wp_mail_failed', $error );

		$logs = $this->logger->get_logs( [ 'search' => 'fail@example.org', 'status' => '' ] );
		$failed = array_filter( $logs, static fn( $l ) => '0' === (string) $l->status );

		$this->assertNotEmpty( $failed );
		$entry = array_values( $failed )[0];
		$this->assertStringContainsString( 'SMTP', $entry->error );
	}

	public function test_source_is_test_inside_test_send_hooks(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [
			'logger_enabled'         => true,
			'logger_log_test_emails' => true,
		] );

		$args = [
			'to'          => 'test-source@example.org',
			'subject'     => 'Test Source',
			'message'     => 'test',
			'headers'     => [],
			'attachments' => [],
		];

		do_action( 'wca_email_tester_before_test_send', 'affiliate_applied' );
		apply_filters( 'wp_mail', $args );
		do_action( 'wp_mail_succeeded', $args );
		do_action( 'wca_email_tester_after_test_send', 'affiliate_applied' );

		$logs = $this->logger->get_logs( [ 'search' => 'test-source@example.org' ] );
		$this->assertNotEmpty( $logs );
		$this->assertEquals( 'test', $logs[0]->source );
	}

	public function test_source_is_live_outside_test_send_hooks(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [ 'logger_enabled' => true ] );

		$args = [
			'to'          => 'live-source@example.org',
			'subject'     => 'Live Source',
			'message'     => 'live',
			'headers'     => [],
			'attachments' => [],
		];

		apply_filters( 'wp_mail', $args );
		do_action( 'wp_mail_succeeded', $args );

		$logs = $this->logger->get_logs( [ 'search' => 'live-source@example.org' ] );
		$this->assertNotEmpty( $logs );
		$this->assertEquals( 'live', $logs[0]->source );
	}

	public function test_disabled_logger_does_not_capture(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [ 'logger_enabled' => false ] );
		$logger = new WCA_Email_Tester_Logger();

		$before = $logger->count_logs();

		$args = [
			'to'          => 'no-capture@example.org',
			'subject'     => 'Should not be logged',
			'message'     => 'x',
			'headers'     => [],
			'attachments' => [],
		];
		apply_filters( 'wp_mail', $args );
		do_action( 'wp_mail_succeeded', $args );

		$this->assertEquals( $before, $logger->count_logs() );
	}

	// ===================================================================
	// CRUD
	// ===================================================================

	public function test_get_logs_returns_array(): void {
		$this->seed_logs( 3 );
		$logs = $this->logger->get_logs();
		$this->assertIsArray( $logs );
		$this->assertCount( 3, $logs );
	}

	public function test_count_logs_returns_total(): void {
		$this->seed_logs( 5 );
		$this->assertEquals( 5, $this->logger->count_logs() );
	}

	public function test_get_logs_filters_by_status_sent(): void {
		$this->seed_logs( 3, 1 );
		$this->seed_logs( 2, 0 );
		$this->assertCount( 3, $this->logger->get_logs( [ 'status' => 1 ] ) );
	}

	public function test_get_logs_filters_by_status_failed(): void {
		$this->seed_logs( 3, 1 );
		$this->seed_logs( 2, 0 );
		$this->assertCount( 2, $this->logger->get_logs( [ 'status' => 0 ] ) );
	}

	public function test_get_logs_filters_by_source_test(): void {
		$this->seed_logs( 2, 1, 'test' );
		$this->seed_logs( 4, 1, 'live' );
		$this->assertCount( 2, $this->logger->get_logs( [ 'source' => 'test' ] ) );
	}

	public function test_get_logs_filters_by_source_live(): void {
		$this->seed_logs( 2, 1, 'test' );
		$this->seed_logs( 4, 1, 'live' );
		$this->assertCount( 4, $this->logger->get_logs( [ 'source' => 'live' ] ) );
	}

	public function test_get_logs_search_by_recipient(): void {
		$this->seed_logs( 3 );
		$this->insert_raw_log( [ 'to_email' => 'find-me@example.org', 'subject' => 'Needle' ] );

		$results = $this->logger->get_logs( [ 'search' => 'find-me@example.org' ] );
		$this->assertCount( 1, $results );
		$this->assertEquals( 'Needle', $results[0]->subject );
	}

	public function test_get_logs_search_by_subject(): void {
		$this->seed_logs( 3 );
		$this->insert_raw_log( [ 'subject' => 'UniqueSubjectXYZ' ] );

		$results = $this->logger->get_logs( [ 'search' => 'UniqueSubjectXYZ' ] );
		$this->assertCount( 1, $results );
	}

	public function test_get_logs_pagination(): void {
		$this->seed_logs( 10 );

		$page1 = $this->logger->get_logs( [ 'per_page' => 3, 'page' => 1 ] );
		$page2 = $this->logger->get_logs( [ 'per_page' => 3, 'page' => 2 ] );

		$this->assertCount( 3, $page1 );
		$this->assertCount( 3, $page2 );
		$this->assertNotEquals( $page1[0]->id, $page2[0]->id );
	}

	public function test_get_single_log_returns_object(): void {
		$this->seed_logs( 1 );
		$logs = $this->logger->get_logs();
		$id   = (int) $logs[0]->id;

		$log = $this->logger->get_log( $id );

		$this->assertNotNull( $log );
		$this->assertEquals( $id, (int) $log->id );
	}

	public function test_get_single_log_nonexistent_returns_null(): void {
		$this->assertNull( $this->logger->get_log( 999999 ) );
	}

	public function test_delete_single_log(): void {
		$this->seed_logs( 3 );
		$logs = $this->logger->get_logs();
		$id   = (int) $logs[0]->id;

		$this->logger->delete_log( $id );

		$this->assertEquals( 2, $this->logger->count_logs() );
		$this->assertNull( $this->logger->get_log( $id ) );
	}

	public function test_delete_multiple_logs(): void {
		$this->seed_logs( 5 );
		$logs = $this->logger->get_logs();
		$ids  = array_map( static fn( $l ) => (int) $l->id, array_slice( $logs, 0, 3 ) );

		$this->logger->delete_logs( $ids );

		$this->assertEquals( 2, $this->logger->count_logs() );
	}

	public function test_delete_logs_with_empty_array_does_nothing(): void {
		$this->seed_logs( 3 );
		$this->logger->delete_logs( [] );
		$this->assertEquals( 3, $this->logger->count_logs() );
	}

	public function test_truncate_removes_all_logs(): void {
		$this->seed_logs( 10 );
		$this->logger->truncate();
		$this->assertEquals( 0, $this->logger->count_logs() );
	}

	// ===================================================================
	// Retention / purge
	// ===================================================================

	public function test_purge_old_logs_by_count(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [
			'logger_enabled'                 => true,
			'logger_retention_count_enabled' => true,
			'logger_retention_count'         => 5,
			'logger_retention_days_enabled'  => false,
		] );

		$this->seed_logs( 12 );
		$this->logger->purge_old_logs();

		$this->assertLessThanOrEqual( 5, $this->logger->count_logs() );
	}

	public function test_purge_old_logs_by_days(): void {
		update_option( WCA_Email_Tester_Logger::OPTION_KEY, [
			'logger_enabled'                 => true,
			'logger_retention_count_enabled' => false,
			'logger_retention_days_enabled'  => true,
			'logger_retention_days'          => 1,
		] );

		// Insert an old log directly.
		$this->insert_raw_log( [
			'timestamp' => date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ),
		] );
		// Insert a fresh log.
		$this->seed_logs( 1 );

		$this->logger->purge_old_logs();

		// Only the fresh log should remain.
		$this->assertEquals( 1, $this->logger->count_logs() );
	}

	// ===================================================================
	// Helpers
	// ===================================================================

	private function seed_logs( int $count, int $status = 1, string $source = 'live' ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			$this->insert_raw_log( [ 'status' => $status, 'source' => $source ] );
		}
	}

	private function insert_raw_log( array $overrides = [] ): void {
		global $wpdb;
		$defaults = [
			'timestamp'   => current_time( 'mysql' ),
			'to_email'    => 'seed@example.org',
			'subject'     => 'Seeded Log',
			'message'     => '<p>test</p>',
			'headers'     => maybe_serialize( [] ),
			'attachments' => maybe_serialize( [] ),
			'status'      => 1,
			'error'       => '',
			'source'      => 'live',
		];

		$wpdb->insert(
			$wpdb->prefix . WCA_Email_Tester_Logger::TABLE_SUFFIX,
			array_merge( $defaults, $overrides ),
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
	}
}
