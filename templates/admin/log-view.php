<?php defined( 'ABSPATH' ) || exit;
/** @var object $log */
$headers     = maybe_unserialize( $log->headers );
$attachments = maybe_unserialize( $log->attachments );
$headers_str = is_array( $headers ) ? implode( "\n", $headers ) : (string) $headers;
?>
<div class="wrap wcaet-wrap">

	<div class="wcaet-page-header">
		<h1>
			<?php WCA_Email_Tester_Icons::render( 'mail' ); ?>
			<?php echo esc_html( sprintf( __( 'Log #%d', 'wc-affiliate-email-tester' ), absint( $log->id ) ) ); ?>
		</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester-logs' ) ); ?>" class="wcaet-back-link">
			<?php WCA_Email_Tester_Icons::render( 'arrow-left' ); ?>
			<?php esc_html_e( 'Back to Logs', 'wc-affiliate-email-tester' ); ?>
		</a>
	</div>

	<div class="wcaet-log-view-grid">

		<div class="wcaet-card wcaet-log-meta">
			<h3><?php esc_html_e( 'Details', 'wc-affiliate-email-tester' ); ?></h3>
			<table class="wcaet-meta-table">
				<tr>
					<th><?php esc_html_e( 'ID', 'wc-affiliate-email-tester' ); ?></th>
					<td>#<?php echo absint( $log->id ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Date', 'wc-affiliate-email-tester' ); ?></th>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->timestamp ) ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'To', 'wc-affiliate-email-tester' ); ?></th>
					<td><?php echo esc_html( $log->to_email ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Subject', 'wc-affiliate-email-tester' ); ?></th>
					<td><?php echo esc_html( $log->subject ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'wc-affiliate-email-tester' ); ?></th>
					<td>
						<?php if ( $log->status ) : ?>
							<span class="wcaet-badge wcaet-badge--sent"><?php esc_html_e( 'Sent', 'wc-affiliate-email-tester' ); ?></span>
						<?php else : ?>
							<span class="wcaet-badge wcaet-badge--failed"><?php esc_html_e( 'Failed', 'wc-affiliate-email-tester' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Source', 'wc-affiliate-email-tester' ); ?></th>
					<td>
						<?php if ( 'test' === $log->source ) : ?>
							<span class="wcaet-badge wcaet-badge--source-test"><?php esc_html_e( 'Test', 'wc-affiliate-email-tester' ); ?></span>
						<?php else : ?>
							<span class="wcaet-badge wcaet-badge--source-live"><?php esc_html_e( 'Live', 'wc-affiliate-email-tester' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( $log->error ) : ?>
				<tr>
					<th><?php esc_html_e( 'Error', 'wc-affiliate-email-tester' ); ?></th>
					<td class="wcaet-error-text"><?php echo esc_html( $log->error ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<div class="wcaet-log-actions">
				<a
					href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester-logs&action=delete&log_id=' . absint( $log->id ) . '&_wpnonce=' . wp_create_nonce( 'wca_et_delete_log_' . $log->id ) ) ); ?>"
					class="button wcaet-delete-btn"
					onclick="return confirm('<?php esc_attr_e( 'Delete this log entry?', 'wc-affiliate-email-tester' ); ?>')"
				>
					<?php WCA_Email_Tester_Icons::render( 'trash' ); ?>
					<?php esc_html_e( 'Delete Log', 'wc-affiliate-email-tester' ); ?>
				</a>
			</div>
		</div>

		<div class="wcaet-log-preview-area">

			<!-- Preview -->
			<div class="wcaet-entry-section">
				<button
					type="button"
					class="wcaet-collapsible-toggle is-open"
					aria-expanded="true"
					data-target="wcaet-log-preview"
					data-label-open="<?php esc_attr_e( 'Hide Preview', 'wc-affiliate-email-tester' ); ?>"
					data-label-closed="<?php esc_attr_e( 'Show Preview', 'wc-affiliate-email-tester' ); ?>"
				>
					<?php WCA_Email_Tester_Icons::render( 'eye' ); ?>
					<span><?php esc_html_e( 'Hide Preview', 'wc-affiliate-email-tester' ); ?></span>
					<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
				</button>
				<div id="wcaet-log-preview">
					<?php
					$preview_html = WCA_Email_Tester_Admin::wrap_preview_html( $log->message );
					$encoded      = base64_encode( $preview_html ); // phpcs:ignore
					?>
					<iframe
						class="wcaet-preview-iframe"
						src="data:text/html;base64,<?php echo esc_attr( $encoded ); ?>"
						title="<?php esc_attr_e( 'Email Preview', 'wc-affiliate-email-tester' ); ?>"
					></iframe>
				</div>
			</div>

			<!-- Source -->
			<div class="wcaet-entry-section">
				<button
					type="button"
					class="wcaet-collapsible-toggle"
					aria-expanded="false"
					data-target="wcaet-log-source"
					data-label-open="<?php esc_attr_e( 'Hide Source', 'wc-affiliate-email-tester' ); ?>"
					data-label-closed="<?php esc_attr_e( 'Show Source', 'wc-affiliate-email-tester' ); ?>"
				>
					<?php WCA_Email_Tester_Icons::render( 'code' ); ?>
					<span><?php esc_html_e( 'Show Source', 'wc-affiliate-email-tester' ); ?></span>
					<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
				</button>
				<div id="wcaet-log-source" hidden>
					<pre class="wcaet-source-block"><code><?php echo esc_html( $log->message ); ?></code></pre>
				</div>
			</div>

			<!-- Headers -->
			<?php if ( $headers_str ) : ?>
			<div class="wcaet-entry-section">
				<button
					type="button"
					class="wcaet-collapsible-toggle"
					aria-expanded="false"
					data-target="wcaet-log-headers"
					data-label-open="<?php esc_attr_e( 'Hide Headers', 'wc-affiliate-email-tester' ); ?>"
					data-label-closed="<?php esc_attr_e( 'Show Headers', 'wc-affiliate-email-tester' ); ?>"
				>
					<?php WCA_Email_Tester_Icons::render( 'braces' ); ?>
					<span><?php esc_html_e( 'Show Headers', 'wc-affiliate-email-tester' ); ?></span>
					<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
				</button>
				<div id="wcaet-log-headers" hidden>
					<pre class="wcaet-source-block"><code><?php echo esc_html( $headers_str ); ?></code></pre>
				</div>
			</div>
			<?php endif; ?>

		</div><!-- .wcaet-log-preview-area -->

	</div><!-- .wcaet-log-view-grid -->

</div>
