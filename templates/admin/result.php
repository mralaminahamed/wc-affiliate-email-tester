<?php defined( 'ABSPATH' ) || exit;
/** @var array $result */
$email_types = WCA_Email_Tester_Sender::email_types();
$type_label  = $email_types[ $result['email_type'] ] ?? $result['email_type'];
?>

<div class="wcaet-result">

	<!-- Summary bar -->
	<div class="wcaet-result-summary">
		<div class="wcaet-result-summary__type">
			<?php WCA_Email_Tester_Icons::render( 'mail' ); ?>
			<strong><?php echo esc_html( $type_label ); ?></strong>
		</div>
		<div class="wcaet-result-summary__badges">
			<?php if ( $result['dry_run'] ) : ?>
				<span class="wcaet-badge wcaet-badge--dryrun"><?php esc_html_e( 'Dry Run', 'wc-affiliate-email-tester' ); ?></span>
			<?php elseif ( $result['mail_sent'] ) : ?>
				<span class="wcaet-badge wcaet-badge--sent"><?php esc_html_e( 'Sent', 'wc-affiliate-email-tester' ); ?></span>
			<?php else : ?>
				<span class="wcaet-badge wcaet-badge--failed"><?php esc_html_e( 'Not Sent', 'wc-affiliate-email-tester' ); ?></span>
			<?php endif; ?>

			<?php if ( count( $result['captured'] ) > 0 ) : ?>
				<span class="wcaet-badge wcaet-badge--captured">
					<?php echo esc_html( sprintf(
						/* translators: %d: number of captured emails */
						_n( '%d email captured', '%d emails captured', count( $result['captured'] ), 'wc-affiliate-email-tester' ),
						count( $result['captured'] )
					) ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Error notice -->
	<?php if ( $result['error'] ) : ?>
		<div class="wcaet-notice wcaet-notice--error">
			<?php WCA_Email_Tester_Icons::render( 'alert' ); ?>
			<p><?php echo esc_html( $result['error'] ); ?></p>
		</div>
	<?php endif; ?>

	<!-- No emails captured -->
	<?php if ( empty( $result['captured'] ) && ! $result['error'] ) : ?>
		<div class="wcaet-notice wcaet-notice--warning">
			<?php WCA_Email_Tester_Icons::render( 'alert' ); ?>
			<p><?php esc_html_e( 'No emails were captured. The email type may be disabled in WC Affiliate settings, or no matching data was found.', 'wc-affiliate-email-tester' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Email entries -->
	<?php if ( ! empty( $result['captured'] ) ) : ?>

		<?php if ( count( $result['captured'] ) > 1 ) : ?>
			<div class="wcaet-tabs" role="tablist">
				<?php foreach ( $result['captured'] as $i => $mail ) : ?>
					<button
						type="button"
						class="wcaet-tab-btn <?php echo 0 === $i ? 'is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
						data-tab="wcaet-tab-<?php echo esc_attr( $i ); ?>"
					>
						<?php
						echo esc_html( sprintf(
							/* translators: %d: email index in tab list */
							__( 'Email %d', 'wc-affiliate-email-tester' ),
							$i + 1
						) );
						?>
						<span class="wcaet-tab-to"><?php echo esc_html( is_array( $mail['to'] ) ? implode( ', ', $mail['to'] ) : $mail['to'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php foreach ( $result['captured'] as $i => $mail ) : ?>
			<div
				id="wcaet-tab-<?php echo esc_attr( $i ); ?>"
				class="wcaet-email-tab-panel"
				<?php echo ( count( $result['captured'] ) > 1 && 0 !== $i ) ? 'hidden' : ''; ?>
			>
				<?php include WCA_ET_PATH . 'templates/admin/email-entry.php'; ?>
			</div>
		<?php endforeach; ?>

	<?php endif; ?>

</div>
