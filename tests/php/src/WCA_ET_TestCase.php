<?php

use WC_Affiliate\Test\WCAffiliateTestCase;
use WC_Affiliate\Models\Referral as WCA_Referral_Model;
use WC_Affiliate\Models\Transaction as WCA_Transaction_Model;

/**
 * Base test case for WC Affiliate Email Tester tests.
 *
 * Extends WCAffiliateTestCase to inherit WP + WC + WCA fixtures, factories,
 * and REST helpers, then adds email-tester-specific fixtures and assertions.
 */
abstract class WCA_ET_TestCase extends WCAffiliateTestCase {

	/** @var int Referral ID shared across test class. */
	protected int $referral_id;

	/** @var int Transaction ID shared across test class. */
	protected int $transaction_id;

	/** @var int WooCommerce order ID for referral fixture. */
	protected int $wc_order_id;

	public function set_up(): void {
		parent::set_up();

		// Ensure all WCA emails are enabled for testing.
		$this->force_enable_all_emails();

		wp_set_current_user( $this->admin_id );

		$this->wc_order_id    = $this->create_single_affiliate_order( $this->affiliate_id1 );
		$this->referral_id    = $this->create_test_referral();
		$this->transaction_id = $this->create_test_transaction();
	}

	public function tear_down(): void {
		$this->restore_email_filters();
		parent::tear_down();
	}

	// -----------------------------------------------------------------------
	// Fixtures
	// -----------------------------------------------------------------------

	/**
	 * Insert a referral row via the WCA factory.
	 */
	protected function create_test_referral(): int {
		return $this->factory()->referral->create( [
			'affiliate' => $this->affiliate_id1,
			'order_id'  => $this->wc_order_id,
			'commission' => 15.00,
			'order_total' => 150.00,
		] );
	}

	/**
	 * Insert a transaction row via the WCA factory.
	 */
	protected function create_test_transaction(): int {
		return $this->factory()->transaction->create( [
			'affiliate' => $this->affiliate_id1,
			'amount'    => 50.00,
			'status'    => 'pending',
		] );
	}

	// -----------------------------------------------------------------------
	// Email enable / disable helpers
	// -----------------------------------------------------------------------

	/** @var array Filters added to force-enable emails. */
	private array $email_filters = [];

	protected function force_enable_all_emails(): void {
		$filters = [
			'wc_affiliate_email_affiliate_application_enabled',
			'wc_affiliate_email_admin_application_enabled',
			'wc_affiliate_email_affiliate_approval_enabled',
			'wc_affiliate_email_affiliate_rejection_enabled',
			'wc_affiliate_email_verification_enabled',
			'wc_affiliate_email_affiliate_commission_enabled',
			'wc_affiliate_email_admin_commission_enabled',
			'wc_affiliate_email_affiliate_payout_request_enabled',
			'wc_affiliate_email_admin_payout_request_enabled',
			'wc_affiliate_email_payout_processed_enabled',
			'wc_affiliate_email_admin_transaction_enabled',
			'wc_affiliate_email_affiliate_paid_referral_enabled',
			'wc_affiliate_email_admin_paid_referral_enabled',
		];

		foreach ( $filters as $filter ) {
			add_filter( $filter, '__return_true', 999 );
		}

		$this->email_filters = $filters;
	}

	protected function restore_email_filters(): void {
		foreach ( $this->email_filters as $filter ) {
			remove_filter( $filter, '__return_true', 999 );
		}
		$this->email_filters = [];
	}

	// -----------------------------------------------------------------------
	// Assertions
	// -----------------------------------------------------------------------

	/**
	 * Assert a sender result has no error and captured at least one email.
	 */
	protected function assertEmailCaptured( array $result, string $message = '' ): void {
		$this->assertEmpty( $result['error'], $message ?: "Sender returned error: {$result['error']}" );
		$this->assertNotEmpty( $result['captured'], $message ?: 'No emails were captured.' );
		$this->assertIsArray( $result['captured'][0] );
		$this->assertArrayHasKey( 'to', $result['captured'][0] );
		$this->assertArrayHasKey( 'subject', $result['captured'][0] );
		$this->assertNotEmpty( $result['captured'][0]['to'] );
		$this->assertNotEmpty( $result['captured'][0]['subject'] );
	}

	/**
	 * Assert a sender result has the expected error (non-empty).
	 */
	protected function assertSenderError( array $result, string $message = '' ): void {
		$this->assertNotEmpty( $result['error'], $message ?: 'Expected an error but result was empty.' );
		$this->assertEmpty( $result['captured'], 'Expected no captured emails when error occurs.' );
	}

	/**
	 * Skip test when WC Affiliate Pro is not active.
	 */
	protected function skip_if_no_pro(): void {
		if ( ! defined( 'WC_AFFILIATE_PRO_VERSION' ) ) {
			$this->markTestSkipped( 'WC Affiliate Pro is not active.' );
		}
	}

	// -----------------------------------------------------------------------
	// Sender factory shortcut
	// -----------------------------------------------------------------------

	protected function make_sender( string $override = '', bool $dry_run = true ): WCA_Email_Tester_Sender {
		return new WCA_Email_Tester_Sender( $override, $dry_run );
	}
}
