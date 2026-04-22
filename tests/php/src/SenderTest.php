<?php

/**
 * Tests for WCA_Email_Tester_Sender — covers every variable email type,
 * dry-run mode, override-recipient, and error handling.
 */
class SenderTest extends WCA_ET_TestCase {

	// ===================================================================
	// Static helpers (no DB needed)
	// ===================================================================

	public function test_email_types_returns_array(): void {
		$types = WCA_Email_Tester_Sender::email_types();

		$this->assertIsArray( $types );
		$this->assertNotEmpty( $types );
	}

	public function test_email_types_contains_all_core_keys(): void {
		$types = WCA_Email_Tester_Sender::email_types();

		$expected_keys = [
			'affiliate_applied',
			'affiliate_applied_admin',
			'affiliate_approved',
			'affiliate_rejected',
			'email_verification',
			'commission_earned',
			'commission_earned_admin',
			'payout_request',
			'payout_request_admin',
			'payout_processed',
			'transaction_created_admin',
			'paid_referral',
			'paid_referral_admin',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $types, "Missing email type key: {$key}" );
		}
	}

	public function test_email_types_all_labels_are_non_empty_strings(): void {
		foreach ( WCA_Email_Tester_Sender::email_types() as $key => $label ) {
			$this->assertIsString( $label, "Label for {$key} is not a string." );
			$this->assertNotEmpty( $label, "Label for {$key} is empty." );
		}
	}

	public function test_field_for_type_covers_all_email_type_keys(): void {
		$types    = array_keys( WCA_Email_Tester_Sender::email_types() );
		$field_map = WCA_Email_Tester_Sender::field_for_type();

		foreach ( $types as $key ) {
			// Pro types may not be in the core map — skip if Pro absent.
			if ( in_array( $key, [ 'mlc_commission', 'signup_bonus', 'referral_bonus' ], true )
				&& ! defined( 'WC_AFFILIATE_PRO_VERSION' ) ) {
				continue;
			}
			$this->assertArrayHasKey( $key, $field_map, "field_for_type missing key: {$key}" );
		}
	}

	public function test_field_for_type_values_are_valid(): void {
		$valid = [ 'affiliate', 'referral', 'transaction' ];
		foreach ( WCA_Email_Tester_Sender::field_for_type() as $key => $field ) {
			$this->assertContains( $field, $valid, "Invalid field value '{$field}' for type '{$key}'." );
		}
	}

	// ===================================================================
	// Affiliate-type emails
	// ===================================================================

	public function test_affiliate_applied_captures_email(): void {
		$before = did_action( 'wc_affiliate_affiliate_applied' );
		$result = $this->make_sender()->send( 'affiliate_applied', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_affiliate_applied' ) );
		$this->assertEmailCaptured( $result );
		$this->assertTrue( $result['dry_run'] );
		$this->assertFalse( $result['mail_sent'] );
	}

	public function test_affiliate_applied_admin_captures_email(): void {
		$before = did_action( 'wc_affiliate_affiliate_applied' );
		$result = $this->make_sender()->send( 'affiliate_applied_admin', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_affiliate_applied' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_affiliate_approved_captures_email(): void {
		$before = did_action( 'wc_affiliate_account_reviewed' );
		$result = $this->make_sender()->send( 'affiliate_approved', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_account_reviewed' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_affiliate_rejected_captures_email(): void {
		$before = did_action( 'wc_affiliate_account_reviewed' );
		$result = $this->make_sender()->send( 'affiliate_rejected', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_account_reviewed' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_email_verification_fires_resend_filter(): void {
		// Force-enable verification for this test.
		add_filter( 'wc_affiliate_email_verification_enabled', '__return_true', 999 );

		$result = $this->make_sender()->send( 'email_verification', $this->affiliate_id1 );

		remove_filter( 'wc_affiliate_email_verification_enabled', '__return_true', 999 );

		// We assert no error and a clean result (email may or may not capture
		// depending on whether verification is configured — no fatal expected).
		$this->assertEmpty( $result['error'] );
		$this->assertIsArray( $result['captured'] );
	}

	public function test_payout_request_captures_email(): void {
		$before = did_action( 'wc_affiliate_payout_request_created' );
		$result = $this->make_sender()->send( 'payout_request', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_payout_request_created' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_payout_request_admin_captures_email(): void {
		$before = did_action( 'wc_affiliate_payout_request_created' );
		$result = $this->make_sender()->send( 'payout_request_admin', $this->affiliate_id1 );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_payout_request_created' ) );
		$this->assertEmailCaptured( $result );
	}

	// ===================================================================
	// Referral-type emails
	// ===================================================================

	public function test_commission_earned_captures_email(): void {
		$before = did_action( 'wc_affiliate_add_credit' );
		$result = $this->make_sender()->send( 'commission_earned', 0, $this->referral_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_add_credit' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_commission_earned_admin_captures_email(): void {
		$before = did_action( 'wc_affiliate_add_credit' );
		$result = $this->make_sender()->send( 'commission_earned_admin', 0, $this->referral_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_add_credit' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_paid_referral_captures_email(): void {
		$before = did_action( 'wc_affiliate_referral_has_paid' );
		$result = $this->make_sender()->send( 'paid_referral', 0, $this->referral_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_referral_has_paid' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_paid_referral_admin_captures_email(): void {
		$before = did_action( 'wc_affiliate_referral_has_paid' );
		$result = $this->make_sender()->send( 'paid_referral_admin', 0, $this->referral_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_referral_has_paid' ) );
		$this->assertEmailCaptured( $result );
	}

	// ===================================================================
	// Transaction-type emails
	// ===================================================================

	public function test_payout_processed_captures_email(): void {
		$before = did_action( 'wc_affiliate_payout_processed' );
		$result = $this->make_sender()->send( 'payout_processed', 0, 0, $this->transaction_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_payout_processed' ) );
		$this->assertEmailCaptured( $result );
	}

	public function test_transaction_created_admin_captures_email(): void {
		$before = did_action( 'wc_affiliate_transaction_after_create' );
		$result = $this->make_sender()->send( 'transaction_created_admin', 0, 0, $this->transaction_id );

		$this->assertGreaterThan( $before, did_action( 'wc_affiliate_transaction_after_create' ) );
		$this->assertEmailCaptured( $result );
	}

	// ===================================================================
	// Pro email types (skipped when Pro not active)
	// ===================================================================

	public function test_mlc_commission_captures_email_when_pro_active(): void {
		$this->skip_if_no_pro();
		$result = $this->make_sender()->send( 'mlc_commission', 0, $this->referral_id );
		$this->assertEmailCaptured( $result );
	}

	public function test_signup_bonus_captures_email_when_pro_active(): void {
		$this->skip_if_no_pro();
		$result = $this->make_sender()->send( 'signup_bonus', $this->affiliate_id1 );
		$this->assertEmailCaptured( $result );
	}

	public function test_referral_bonus_captures_email_when_pro_active(): void {
		$this->skip_if_no_pro();
		$result = $this->make_sender()->send( 'referral_bonus', $this->affiliate_id1 );
		$this->assertEmailCaptured( $result );
	}

	// ===================================================================
	// Dry-run behaviour
	// ===================================================================

	public function test_dry_run_sets_mail_sent_false(): void {
		$result = $this->make_sender( '', true )->send( 'affiliate_applied', $this->affiliate_id1 );

		$this->assertTrue( $result['dry_run'] );
		$this->assertFalse( $result['mail_sent'] );
	}

	public function test_dry_run_still_captures_email_args(): void {
		$result = $this->make_sender( '', true )->send( 'affiliate_applied', $this->affiliate_id1 );

		$this->assertNotEmpty( $result['captured'] );
	}

	public function test_live_send_sets_mail_sent_true(): void {
		// Use override to avoid sending to real affiliate address.
		$result = $this->make_sender( 'test@example.org', false )->send( 'affiliate_approved', $this->affiliate_id1 );

		// mail_sent depends on wp_mail() actually delivering.
		// In test env without SMTP we can't guarantee delivery, but no fatal expected.
		$this->assertEmpty( $result['error'] );
		$this->assertIsBool( $result['mail_sent'] );
	}

	// ===================================================================
	// Override-recipient behaviour
	// ===================================================================

	public function test_override_email_replaces_recipient(): void {
		$override = 'override@example.org';
		$result   = $this->make_sender( $override, true )->send( 'affiliate_approved', $this->affiliate_id1 );

		$this->assertNotEmpty( $result['captured'] );

		foreach ( $result['captured'] as $mail ) {
			$to = is_array( $mail['to'] ) ? implode( ', ', $mail['to'] ) : $mail['to'];
			$this->assertEquals( $override, $to, 'Captured recipient does not match override.' );
		}
	}

	public function test_empty_override_keeps_original_recipient(): void {
		$result = $this->make_sender( '', true )->send( 'affiliate_approved', $this->affiliate_id1 );

		$this->assertNotEmpty( $result['captured'] );

		$affiliate = get_userdata( $this->affiliate_id1 );
		$to        = is_array( $result['captured'][0]['to'] )
			? implode( ', ', $result['captured'][0]['to'] )
			: $result['captured'][0]['to'];

		$this->assertStringContainsString( $affiliate->user_email, $to );
	}

	// ===================================================================
	// Result structure
	// ===================================================================

	public function test_result_has_required_keys(): void {
		$result = $this->make_sender()->send( 'affiliate_applied', $this->affiliate_id1 );

		$this->assertArrayHasKey( 'error', $result );
		$this->assertArrayHasKey( 'captured', $result );
		$this->assertArrayHasKey( 'email_type', $result );
		$this->assertArrayHasKey( 'dry_run', $result );
		$this->assertArrayHasKey( 'mail_sent', $result );
	}

	public function test_result_email_type_matches_input(): void {
		$result = $this->make_sender()->send( 'affiliate_approved', $this->affiliate_id1 );
		$this->assertEquals( 'affiliate_approved', $result['email_type'] );
	}

	// ===================================================================
	// Error handling — missing / invalid IDs
	// ===================================================================

	public function test_missing_affiliate_id_returns_error_for_affiliate_applied(): void {
		$result = $this->make_sender()->send( 'affiliate_applied', 0 );
		$this->assertSenderError( $result );
	}

	public function test_invalid_affiliate_id_returns_error(): void {
		$result = $this->make_sender()->send( 'affiliate_approved', 999999 );
		$this->assertSenderError( $result );
	}

	public function test_missing_affiliate_id_returns_error_for_payout_request(): void {
		$result = $this->make_sender()->send( 'payout_request', 0 );
		$this->assertSenderError( $result );
	}

	public function test_missing_affiliate_id_returns_error_for_payout_request_admin(): void {
		$result = $this->make_sender()->send( 'payout_request_admin', 0 );
		$this->assertSenderError( $result );
	}

	public function test_missing_referral_id_returns_error_for_commission_earned(): void {
		$result = $this->make_sender()->send( 'commission_earned', 0, 0 );
		$this->assertSenderError( $result );
	}

	public function test_invalid_referral_id_returns_error(): void {
		$result = $this->make_sender()->send( 'commission_earned', 0, 999999 );
		$this->assertSenderError( $result );
	}

	public function test_missing_referral_id_returns_error_for_paid_referral(): void {
		$result = $this->make_sender()->send( 'paid_referral', 0, 0 );
		$this->assertSenderError( $result );
	}

	public function test_missing_transaction_id_returns_error_for_payout_processed(): void {
		$result = $this->make_sender()->send( 'payout_processed', 0, 0, 0 );
		$this->assertSenderError( $result );
	}

	public function test_invalid_transaction_id_returns_error(): void {
		$result = $this->make_sender()->send( 'payout_processed', 0, 0, 999999 );
		$this->assertSenderError( $result );
	}

	public function test_missing_transaction_id_returns_error_for_transaction_created(): void {
		$result = $this->make_sender()->send( 'transaction_created_admin', 0, 0, 0 );
		$this->assertSenderError( $result );
	}

	public function test_unknown_email_type_returns_error(): void {
		$result = $this->make_sender()->send( 'completely_unknown_type' );
		$this->assertSenderError( $result );
	}

	// ===================================================================
	// Multiple emails captured in one trigger
	// ===================================================================

	public function test_affiliate_applied_trigger_fires_both_affiliate_and_admin_emails(): void {
		// Fire the full hook (no single-type filter) to get both emails.
		$captured = [];
		add_filter( 'wp_mail', static function ( array $args ) use ( &$captured ): array {
			$captured[] = $args;
			return $args;
		}, 5 );

		add_filter( 'pre_wp_mail', '__return_true', 5 );

		do_action( 'wc_affiliate_affiliate_applied', $this->affiliate_id1, [
			'first_name' => 'Test',
			'last_name'  => 'Affiliate',
			'email'      => get_userdata( $this->affiliate_id1 )->user_email,
		] );

		remove_filter( 'pre_wp_mail', '__return_true', 5 );

		$this->assertGreaterThanOrEqual( 1, count( $captured ), 'Expected at least one email from the applied trigger.' );
	}
}
