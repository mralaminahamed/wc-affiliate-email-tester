<?php
defined( 'ABSPATH' ) || exit;

use WC_Affiliate\Models\Affiliate as WCA_Affiliate_Model;
use WC_Affiliate\Models\Referral as WCA_Referral_Model;
use WC_Affiliate\Models\Transaction as WCA_Transaction_Model;

/**
 * Fires WC Affiliate email trigger hooks, captures wp_mail() calls,
 * resolves placeholders, and optionally blocks dispatch.
 */
class WCA_Email_Tester_Sender {

	/**
	 * All WC Affiliate core email types.
	 */
	public static function email_types(): array {
		$types = array(
			'affiliate_applied'     => __( 'Affiliate: Application (to Affiliate)', 'wc-affiliate-email-tester' ),
			'affiliate_applied_admin' => __( 'Affiliate: Application (to Admin)', 'wc-affiliate-email-tester' ),
			'affiliate_approved'    => __( 'Affiliate: Account Approved', 'wc-affiliate-email-tester' ),
			'affiliate_rejected'    => __( 'Affiliate: Account Rejected', 'wc-affiliate-email-tester' ),
			'email_verification'    => __( 'Affiliate: Email Verification', 'wc-affiliate-email-tester' ),
			'commission_earned'     => __( 'Commission: Earned (to Affiliate)', 'wc-affiliate-email-tester' ),
			'commission_earned_admin' => __( 'Commission: Earned (to Admin)', 'wc-affiliate-email-tester' ),
			'payout_request'        => __( 'Payout: Request (to Affiliate)', 'wc-affiliate-email-tester' ),
			'payout_request_admin'  => __( 'Payout: Request (to Admin)', 'wc-affiliate-email-tester' ),
			'payout_processed'      => __( 'Payout: Processed (to Affiliate)', 'wc-affiliate-email-tester' ),
			'transaction_created_admin' => __( 'Transaction: Created (to Admin)', 'wc-affiliate-email-tester' ),
			'paid_referral'         => __( 'Referral: Paid (to Affiliate)', 'wc-affiliate-email-tester' ),
			'paid_referral_admin'   => __( 'Referral: Paid (to Admin)', 'wc-affiliate-email-tester' ),
		);

		// Pro email types.
		if ( defined( 'WC_AFFILIATE_PRO_VERSION' ) ) {
			$types['mlc_commission']  = __( 'Pro: MLC Commission (to Affiliate)', 'wc-affiliate-email-tester' );
			$types['signup_bonus']    = __( 'Pro: Signup Bonus (to Affiliate)', 'wc-affiliate-email-tester' );
			$types['referral_bonus']  = __( 'Pro: Referral Bonus (to Affiliate)', 'wc-affiliate-email-tester' );
		}

		return apply_filters( 'wca_email_tester_email_types', $types );
	}

	/**
	 * Map email type → which form field is required.
	 * Values: 'affiliate' | 'referral' | 'transaction'
	 */
	public static function field_for_type(): array {
		return array(
			'affiliate_applied'         => 'affiliate',
			'affiliate_applied_admin'   => 'affiliate',
			'affiliate_approved'        => 'affiliate',
			'affiliate_rejected'        => 'affiliate',
			'email_verification'        => 'affiliate',
			'commission_earned'         => 'referral',
			'commission_earned_admin'   => 'referral',
			'payout_request'            => 'affiliate',
			'payout_request_admin'      => 'affiliate',
			'payout_processed'          => 'transaction',
			'transaction_created_admin' => 'transaction',
			'paid_referral'             => 'referral',
			'paid_referral_admin'       => 'referral',
			'mlc_commission'            => 'referral',
			'signup_bonus'              => 'affiliate',
			'referral_bonus'            => 'affiliate',
		);
	}

	/** @var string Redirect all mail to this address (empty = no override). */
	private string $override_email;

	/** @var bool When true, wp_mail() is blocked entirely. */
	private bool $dry_run;

	/** @var array Captured mail args from wp_mail filter. */
	private array $captured = array();

	/** @var bool Whether a real dispatch succeeded. */
	private bool $mail_sent = false;

	public function __construct( string $override_email = '', bool $dry_run = false ) {
		$this->override_email = $override_email;
		$this->dry_run        = $dry_run;
	}

	/**
	 * Fire the appropriate hook and return captured email data.
	 *
	 * @param string $email_type   One of self::email_types() keys.
	 * @param int    $affiliate_id Affiliate user ID (for affiliate-type emails).
	 * @param int    $referral_id  Referral row ID (for commission/referral emails).
	 * @param int    $transaction_id Transaction row ID (for payout/transaction emails).
	 *
	 * @return array{error:string,captured:array,email_type:string,dry_run:bool,mail_sent:bool}
	 */
	public function send( string $email_type, int $affiliate_id = 0, int $referral_id = 0, int $transaction_id = 0 ): array {
		$this->captured  = array();
		$this->mail_sent = false;

		// Attach listeners before firing the hook.
		add_filter( 'wp_mail', array( $this, '_capture_mail' ), 5 );
		add_filter( 'wp_mail', array( $this, '_override_recipient' ), 6 );
		if ( $this->dry_run ) {
			add_filter( 'pre_wp_mail', array( $this, '_block_mail' ), 5 );
		}
		add_action( 'wp_mail_succeeded', array( $this, '_on_mail_succeeded' ) );

		do_action( 'wca_email_tester_before_test_send', $email_type );

		$error = $this->fire_hook( $email_type, $affiliate_id, $referral_id, $transaction_id );

		do_action( 'wca_email_tester_after_test_send', $email_type );

		// Detach listeners.
		remove_filter( 'wp_mail', array( $this, '_capture_mail' ), 5 );
		remove_filter( 'wp_mail', array( $this, '_override_recipient' ), 6 );
		if ( $this->dry_run ) {
			remove_filter( 'pre_wp_mail', array( $this, '_block_mail' ), 5 );
		}
		remove_action( 'wp_mail_succeeded', array( $this, '_on_mail_succeeded' ) );

		return array(
			'error'      => $error,
			'captured'   => $this->captured,
			'email_type' => $email_type,
			'dry_run'    => $this->dry_run,
			'mail_sent'  => $this->mail_sent,
		);
	}

	// -----------------------------------------------------------------------
	// wp_mail hooks
	// -----------------------------------------------------------------------

	/** @internal */
	public function _capture_mail( array $args ): array {
		$this->captured[] = $args;
		return $args;
	}

	/** @internal */
	public function _override_recipient( array $args ): array {
		if ( $this->override_email ) {
			$args['to'] = $this->override_email;
		}
		return $args;
	}

	/** @internal */
	public function _block_mail( $null ): bool {
		return true; // non-null return prevents wp_mail() from sending.
	}

	/** @internal */
	public function _on_mail_succeeded(): void {
		$this->mail_sent = true;
	}

	// -----------------------------------------------------------------------
	// Hook dispatchers
	// -----------------------------------------------------------------------

	private function fire_hook( string $email_type, int $affiliate_id, int $referral_id, int $transaction_id ): string {
		switch ( $email_type ) {

			case 'affiliate_applied':
			case 'affiliate_applied_admin':
				return $this->fire_affiliate_applied( $affiliate_id, $email_type );

			case 'affiliate_approved':
				return $this->fire_account_reviewed( $affiliate_id, 'approve' );

			case 'affiliate_rejected':
				return $this->fire_account_reviewed( $affiliate_id, 'reject' );

			case 'email_verification':
				return $this->fire_email_verification( $affiliate_id );

			case 'commission_earned':
			case 'commission_earned_admin':
				return $this->fire_add_credit( $referral_id );

			case 'payout_request':
			case 'payout_request_admin':
				return $this->fire_payout_request( $affiliate_id );

			case 'payout_processed':
				return $this->fire_payout_processed( $transaction_id );

			case 'transaction_created_admin':
				return $this->fire_transaction_created( $transaction_id );

			case 'paid_referral':
			case 'paid_referral_admin':
				return $this->fire_paid_referral( $referral_id );

			case 'mlc_commission':
				return $this->fire_mlc_commission( $referral_id );

			case 'signup_bonus':
				return $this->fire_signup_bonus( $affiliate_id );

			case 'referral_bonus':
				return $this->fire_referral_bonus( $affiliate_id );

			default:
				return sprintf(
					/* translators: %s: email type key */
					__( 'Unknown email type: %s', 'wc-affiliate-email-tester' ),
					esc_html( $email_type )
				);
		}
	}

	// -----------------------------------------------------------------------
	// Individual dispatchers
	// -----------------------------------------------------------------------

	private function fire_affiliate_applied( int $affiliate_id, string $type ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for this email type.', 'wc-affiliate-email-tester' );
		}
		$user = get_userdata( $affiliate_id );
		if ( ! $user ) {
			return __( 'Affiliate user not found.', 'wc-affiliate-email-tester' );
		}

		$affiliate_data = array(
			'first_name' => $user->first_name ?: $user->display_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
		);

		// Temporarily filter to send only the relevant email.
		if ( 'affiliate_applied' === $type ) {
			add_filter( 'wc_affiliate_email_admin_application_enabled', '__return_false', 99 );
		} else {
			add_filter( 'wc_affiliate_email_affiliate_application_enabled', '__return_false', 99 );
			add_filter( 'wc_affiliate_email_verification_enabled', '__return_false', 99 );
		}

		do_action( 'wc_affiliate_affiliate_applied', $affiliate_id, $affiliate_data );

		remove_filter( 'wc_affiliate_email_admin_application_enabled', '__return_false', 99 );
		remove_filter( 'wc_affiliate_email_affiliate_application_enabled', '__return_false', 99 );
		remove_filter( 'wc_affiliate_email_verification_enabled', '__return_false', 99 );

		return '';
	}

	private function fire_account_reviewed( int $affiliate_id, string $action ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for this email type.', 'wc-affiliate-email-tester' );
		}
		if ( ! get_userdata( $affiliate_id ) ) {
			return __( 'Affiliate user not found.', 'wc-affiliate-email-tester' );
		}

		do_action( 'wc_affiliate_account_reviewed', $affiliate_id, $action );
		return '';
	}

	private function fire_email_verification( int $affiliate_id ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for this email type.', 'wc-affiliate-email-tester' );
		}
		$user = get_userdata( $affiliate_id );
		if ( ! $user ) {
			return __( 'Affiliate user not found.', 'wc-affiliate-email-tester' );
		}

		apply_filters( 'wc_affiliate_resend_verification_email', array( 'status' => 0, 'message' => '' ), $user );
		return '';
	}

	private function fire_add_credit( int $referral_id ): string {
		if ( ! $referral_id ) {
			return __( 'A referral is required for this email type.', 'wc-affiliate-email-tester' );
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wca_referrals WHERE id = %d LIMIT 1",
			$referral_id
		) );

		if ( ! $row ) {
			return __( 'Referral not found.', 'wc-affiliate-email-tester' );
		}

		$referral_data = array(
			'commission'   => $row->commission ?? 0,
			'order_total'  => $row->order_total ?? 0,
			'order_id'     => $row->order_id ?? 0,
			'affiliate'    => $row->affiliate ?? 0,
			'referred_by'  => $row->affiliate ?? 0,
			'time'         => $row->time ?? time(),
		);

		do_action( 'wc_affiliate_add_credit', (int) ( $row->affiliate ?? 0 ), (int) ( $row->order_id ?? 0 ), $referral_data );
		return '';
	}

	private function fire_payout_request( int $affiliate_id ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for this email type.', 'wc-affiliate-email-tester' );
		}

		if ( ! class_exists( 'WC_Affiliate\Models\Affiliate' ) ) {
			return __( 'WC Affiliate model class not available.', 'wc-affiliate-email-tester' );
		}

		$affiliate = new WCA_Affiliate_Model( $affiliate_id );
		if ( ! $affiliate->exists() ) {
			return __( 'Affiliate not found.', 'wc-affiliate-email-tester' );
		}

		$balance = (float) ( $affiliate->get( 'balance' ) ?? 0 );
		do_action( 'wc_affiliate_payout_request_created', $balance, $affiliate );
		return '';
	}

	private function fire_payout_processed( int $transaction_id ): string {
		if ( ! $transaction_id ) {
			return __( 'A transaction is required for this email type.', 'wc-affiliate-email-tester' );
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wca_transactions WHERE id = %d LIMIT 1",
			$transaction_id
		) );

		if ( ! $row ) {
			return __( 'Transaction not found.', 'wc-affiliate-email-tester' );
		}

		$amount           = (float) ( $row->amount ?? 0 );
		$transaction_data = array(
			'id'           => $row->id,
			'affiliate'    => $row->affiliate ?? 0,
			'amount'       => $amount,
			'type'         => $row->type ?? '',
			'note'         => $row->note ?? '',
			'time'         => $row->time ?? time(),
		);

		do_action( 'wc_affiliate_payout_processed', (int) ( $row->affiliate ?? 0 ), $amount, $transaction_data );
		return '';
	}

	private function fire_transaction_created( int $transaction_id ): string {
		if ( ! $transaction_id ) {
			return __( 'A transaction is required for this email type.', 'wc-affiliate-email-tester' );
		}

		if ( ! class_exists( 'WC_Affiliate\Models\Transaction' ) ) {
			return __( 'WC Affiliate Transaction model not available.', 'wc-affiliate-email-tester' );
		}

		$transaction = new WCA_Transaction_Model( $transaction_id );
		if ( ! $transaction->exists() ) {
			return __( 'Transaction not found.', 'wc-affiliate-email-tester' );
		}

		do_action( 'wc_affiliate_transaction_after_create', $transaction_id, $transaction );
		return '';
	}

	private function fire_paid_referral( int $referral_id ): string {
		if ( ! $referral_id ) {
			return __( 'A referral is required for this email type.', 'wc-affiliate-email-tester' );
		}

		if ( ! class_exists( 'WC_Affiliate\Models\Referral' ) || ! class_exists( 'WC_Affiliate\Models\Transaction' ) ) {
			return __( 'WC Affiliate model classes not available.', 'wc-affiliate-email-tester' );
		}

		$referral = new WCA_Referral_Model( $referral_id );
		if ( ! $referral->exists() ) {
			return __( 'Referral not found.', 'wc-affiliate-email-tester' );
		}

		// Find latest transaction for this referral's affiliate, or use a stub.
		global $wpdb;
		$tx_row = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}wca_transactions WHERE affiliate = %d ORDER BY id DESC LIMIT 1",
			(int) $referral->get( 'affiliate' )
		) );

		$transaction_id = $tx_row ? (int) $tx_row->id : 0;
		$transaction    = $transaction_id ? new WCA_Transaction_Model( $transaction_id ) : null;

		if ( ! $transaction || ! $transaction->exists() ) {
			// Fire with a dummy transaction when none available.
			$transaction = null;
		}

		do_action( 'wc_affiliate_referral_has_paid', $referral_id, $referral, $transaction );
		return '';
	}

	private function fire_mlc_commission( int $referral_id ): string {
		if ( ! $referral_id ) {
			return __( 'A referral is required for MLC commission email.', 'wc-affiliate-email-tester' );
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wca_referrals WHERE id = %d LIMIT 1",
			$referral_id
		) );

		if ( ! $row ) {
			return __( 'Referral not found.', 'wc-affiliate-email-tester' );
		}

		do_action( 'wc_affiliate_mlc_commission_added', (int) ( $row->affiliate ?? 0 ), (int) $referral_id, (array) $row );
		return '';
	}

	private function fire_signup_bonus( int $affiliate_id ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for signup bonus email.', 'wc-affiliate-email-tester' );
		}

		$user = get_userdata( $affiliate_id );
		if ( ! $user ) {
			return __( 'Affiliate user not found.', 'wc-affiliate-email-tester' );
		}

		do_action( 'wc_affiliate_signup_bonus_added', $affiliate_id, array(
			'amount' => 0,
			'bonus'  => 0,
		) );
		return '';
	}

	private function fire_referral_bonus( int $affiliate_id ): string {
		if ( ! $affiliate_id ) {
			return __( 'An affiliate is required for referral bonus email.', 'wc-affiliate-email-tester' );
		}

		$user = get_userdata( $affiliate_id );
		if ( ! $user ) {
			return __( 'Affiliate user not found.', 'wc-affiliate-email-tester' );
		}

		do_action( 'wc_affiliate_referral_bonus_added', $affiliate_id, array(
			'amount' => 0,
		) );
		return '';
	}
}
