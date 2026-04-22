<?php

/**
 * Tests for WCA_Email_Tester_API REST endpoints.
 */
class ApiTest extends WCA_ET_TestCase {

	protected string $namespace = 'wca-email-tester/v1';

	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( $this->admin_id );
	}

	// ===================================================================
	// /affiliates
	// ===================================================================

	public function test_affiliates_endpoint_requires_authentication(): void {
		wp_set_current_user( 0 );
		$response = $this->get_request( '/affiliates' );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_affiliates_endpoint_returns_200_for_admin(): void {
		$response = $this->get_request( '/affiliates' );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_affiliates_endpoint_returns_array(): void {
		$data = $this->get_request( '/affiliates' )->get_data();
		$this->assertIsArray( $data );
	}

	public function test_affiliates_endpoint_returns_created_affiliates(): void {
		$data = $this->get_request( '/affiliates' )->get_data();
		$ids  = array_column( $data, 'id' );

		$this->assertContains( $this->affiliate_id1, $ids );
		$this->assertContains( $this->affiliate_id2, $ids );
	}

	public function test_affiliates_each_result_has_id_and_text(): void {
		$data = $this->get_request( '/affiliates' )->get_data();

		foreach ( $data as $item ) {
			$this->assertArrayHasKey( 'id', $item );
			$this->assertArrayHasKey( 'text', $item );
			$this->assertIsInt( $item['id'] );
			$this->assertIsString( $item['text'] );
			$this->assertNotEmpty( $item['text'] );
		}
	}

	public function test_affiliates_search_filters_by_display_name(): void {
		$user  = get_userdata( $this->affiliate_id1 );
		$name  = $user->display_name;
		$data  = $this->get_request( '/affiliates', [ 'search' => $name ] )->get_data();
		$ids   = array_column( $data, 'id' );

		$this->assertContains( $this->affiliate_id1, $ids );
	}

	public function test_affiliates_search_no_match_returns_empty(): void {
		$data = $this->get_request( '/affiliates', [ 'search' => 'ZZZZ_NO_MATCH_9999' ] )->get_data();
		$this->assertEmpty( $data );
	}

	public function test_affiliates_per_page_respected(): void {
		// Create extra affiliates so we have more than 1.
		$this->factory()->affiliate->create();
		$this->factory()->affiliate->create();

		$data = $this->get_request( '/affiliates', [ 'per_page' => 1 ] )->get_data();
		$this->assertCount( 1, $data );
	}

	// ===================================================================
	// /referrals
	// ===================================================================

	public function test_referrals_endpoint_requires_authentication(): void {
		wp_set_current_user( 0 );
		$response = $this->get_request( '/referrals' );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_referrals_endpoint_returns_200_for_admin(): void {
		$response = $this->get_request( '/referrals' );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_referrals_endpoint_includes_created_referral(): void {
		$data = $this->get_request( '/referrals' )->get_data();
		$ids  = array_column( $data, 'id' );

		$this->assertContains( $this->referral_id, $ids );
	}

	public function test_referrals_each_result_has_id_and_text(): void {
		$data = $this->get_request( '/referrals' )->get_data();

		foreach ( $data as $item ) {
			$this->assertArrayHasKey( 'id', $item );
			$this->assertArrayHasKey( 'text', $item );
			$this->assertIsInt( $item['id'] );
			$this->assertNotEmpty( $item['text'] );
		}
	}

	public function test_referrals_search_by_referral_id(): void {
		$data = $this->get_request( '/referrals', [ 'search' => (string) $this->referral_id ] )->get_data();
		$ids  = array_column( $data, 'id' );

		$this->assertContains( $this->referral_id, $ids );
	}

	// ===================================================================
	// /transactions
	// ===================================================================

	public function test_transactions_endpoint_requires_authentication(): void {
		wp_set_current_user( 0 );
		$response = $this->get_request( '/transactions' );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_transactions_endpoint_returns_200_for_admin(): void {
		$response = $this->get_request( '/transactions' );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_transactions_endpoint_includes_created_transaction(): void {
		$data = $this->get_request( '/transactions' )->get_data();
		$ids  = array_column( $data, 'id' );

		$this->assertContains( $this->transaction_id, $ids );
	}

	public function test_transactions_each_result_has_id_and_text(): void {
		$data = $this->get_request( '/transactions' )->get_data();

		foreach ( $data as $item ) {
			$this->assertArrayHasKey( 'id', $item );
			$this->assertArrayHasKey( 'text', $item );
			$this->assertIsInt( $item['id'] );
			$this->assertNotEmpty( $item['text'] );
		}
	}

	public function test_transactions_search_by_transaction_id(): void {
		$data = $this->get_request( '/transactions', [ 'search' => (string) $this->transaction_id ] )->get_data();
		$ids  = array_column( $data, 'id' );

		$this->assertContains( $this->transaction_id, $ids );
	}

	// ===================================================================
	// Static option loaders
	// ===================================================================

	public function test_get_affiliate_option_returns_correct_structure(): void {
		$option = WCA_Email_Tester_API::get_affiliate_option( $this->affiliate_id1 );

		$this->assertNotNull( $option );
		$this->assertArrayHasKey( 'id', $option );
		$this->assertArrayHasKey( 'text', $option );
		$this->assertEquals( $this->affiliate_id1, $option['id'] );
	}

	public function test_get_affiliate_option_returns_null_for_invalid_id(): void {
		$this->assertNull( WCA_Email_Tester_API::get_affiliate_option( 999999 ) );
	}

	public function test_get_referral_option_returns_correct_structure(): void {
		$option = WCA_Email_Tester_API::get_referral_option( $this->referral_id );

		$this->assertNotNull( $option );
		$this->assertArrayHasKey( 'id', $option );
		$this->assertArrayHasKey( 'text', $option );
		$this->assertEquals( $this->referral_id, $option['id'] );
	}

	public function test_get_referral_option_returns_null_for_invalid_id(): void {
		$this->assertNull( WCA_Email_Tester_API::get_referral_option( 999999 ) );
	}

	public function test_get_transaction_option_returns_correct_structure(): void {
		$option = WCA_Email_Tester_API::get_transaction_option( $this->transaction_id );

		$this->assertNotNull( $option );
		$this->assertArrayHasKey( 'id', $option );
		$this->assertArrayHasKey( 'text', $option );
		$this->assertEquals( $this->transaction_id, $option['id'] );
	}

	public function test_get_transaction_option_returns_null_for_invalid_id(): void {
		$this->assertNull( WCA_Email_Tester_API::get_transaction_option( 999999 ) );
	}
}
