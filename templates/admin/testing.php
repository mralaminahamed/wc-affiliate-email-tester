<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap wcaet-wrap">

	<div class="wcaet-page-header">
		<h1><?php WCA_Email_Tester_Icons::render( 'send' ); ?> <?php esc_html_e( 'Email Testing', 'wc-affiliate-email-tester' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester' ) ); ?>" class="wcaet-back-link">
			<?php WCA_Email_Tester_Icons::render( 'arrow-left' ); ?>
			<?php esc_html_e( 'Dashboard', 'wc-affiliate-email-tester' ); ?>
		</a>
	</div>

	<div class="wcaet-testing-layout">

		<!-- Send Form -->
		<div class="wcaet-card wcaet-send-form">
			<h2><?php esc_html_e( 'Send Test Email', 'wc-affiliate-email-tester' ); ?></h2>
			<p class="wcaet-card__subtitle"><?php esc_html_e( 'Select an email type and a target affiliate, referral, or transaction to fire a real notification.', 'wc-affiliate-email-tester' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'wca_et_send', 'wca_et_send_nonce' ); ?>

				<div class="wcaet-field">
					<label for="email_type"><?php esc_html_e( 'Email Type', 'wc-affiliate-email-tester' ); ?></label>
					<select id="email_type" name="email_type">
						<?php foreach ( $email_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $result['email_type'] ?? '', $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Affiliate field -->
				<div class="wcaet-field wcaet-id-field" id="field-affiliate" style="display:none;">
					<label for="affiliate_id"><?php esc_html_e( 'Affiliate', 'wc-affiliate-email-tester' ); ?></label>
					<select
						id="affiliate_id"
						name="affiliate_id"
						class="wcaet-select2"
						data-endpoint="affiliates"
						data-placeholder="<?php esc_attr_e( 'Search affiliates…', 'wc-affiliate-email-tester' ); ?>"
					>
						<?php if ( $affiliate_option ) : ?>
							<option value="<?php echo esc_attr( $affiliate_option['id'] ); ?>" selected>
								<?php echo esc_html( $affiliate_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
				</div>

				<!-- Referral field -->
				<div class="wcaet-field wcaet-id-field" id="field-referral" style="display:none;">
					<label for="referral_id"><?php esc_html_e( 'Referral', 'wc-affiliate-email-tester' ); ?></label>
					<select
						id="referral_id"
						name="referral_id"
						class="wcaet-select2"
						data-endpoint="referrals"
						data-placeholder="<?php esc_attr_e( 'Search referrals…', 'wc-affiliate-email-tester' ); ?>"
					>
						<?php if ( $referral_option ) : ?>
							<option value="<?php echo esc_attr( $referral_option['id'] ); ?>" selected>
								<?php echo esc_html( $referral_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
				</div>

				<!-- Transaction field -->
				<div class="wcaet-field wcaet-id-field" id="field-transaction" style="display:none;">
					<label for="transaction_id"><?php esc_html_e( 'Transaction', 'wc-affiliate-email-tester' ); ?></label>
					<select
						id="transaction_id"
						name="transaction_id"
						class="wcaet-select2"
						data-endpoint="transactions"
						data-placeholder="<?php esc_attr_e( 'Search transactions…', 'wc-affiliate-email-tester' ); ?>"
					>
						<?php if ( $transaction_option ) : ?>
							<option value="<?php echo esc_attr( $transaction_option['id'] ); ?>" selected>
								<?php echo esc_html( $transaction_option['text'] ); ?>
							</option>
						<?php endif; ?>
					</select>
				</div>

				<div class="wcaet-field">
					<label for="override_email"><?php esc_html_e( 'Override Recipient', 'wc-affiliate-email-tester' ); ?></label>
					<input
						type="email"
						id="override_email"
						name="override_email"
						value="<?php echo esc_attr( sanitize_email( $_POST['override_email'] ?? '' ) ); ?>"
						placeholder="<?php esc_attr_e( 'Leave empty to use original recipient', 'wc-affiliate-email-tester' ); ?>"
					>
					<p class="description"><?php esc_html_e( 'All emails will be redirected to this address instead of the original recipient.', 'wc-affiliate-email-tester' ); ?></p>
				</div>

				<div class="wcaet-field wcaet-field--checkbox">
					<label>
						<input type="checkbox" name="dry_run" value="1" <?php checked( ! empty( $_POST['dry_run'] ) ); ?>>
						<?php esc_html_e( 'Dry Run (capture without sending)', 'wc-affiliate-email-tester' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Preview the rendered email without actually dispatching it via wp_mail().', 'wc-affiliate-email-tester' ); ?></p>
				</div>

				<button type="submit" class="button button-primary wcaet-submit-btn">
					<?php WCA_Email_Tester_Icons::render( 'send' ); ?>
					<?php esc_html_e( 'Send Test Email', 'wc-affiliate-email-tester' ); ?>
				</button>
			</form>
		</div>

		<!-- Result panel -->
		<div class="wcaet-result-panel">
			<?php if ( null !== $result ) : ?>
				<?php include WCA_ET_PATH . 'templates/admin/result.php'; ?>
			<?php else : ?>
				<div class="wcaet-result-placeholder">
					<div class="wcaet-result-placeholder__icon"><?php WCA_Email_Tester_Icons::render( 'mail' ); ?></div>
					<p><?php esc_html_e( 'Send a test email to see the results here.', 'wc-affiliate-email-tester' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

	</div><!-- .wcaet-testing-layout -->

</div>
