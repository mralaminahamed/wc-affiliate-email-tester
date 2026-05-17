<?php defined( 'ABSPATH' ) || exit;
/** @var array $mail Captured wp_mail args */
$to      = is_array( $mail['to'] ) ? implode( ', ', $mail['to'] ) : ( $mail['to'] ?? '' );
$subject = $mail['subject'] ?? '';
$message = $mail['message'] ?? '';
$headers = is_array( $mail['headers'] ) ? implode( "\n", $mail['headers'] ) : ( $mail['headers'] ?? '' );

// Detect unresolved placeholders (%%token%%).
preg_match_all( '/%%[\w_]+%%/', $subject . ' ' . $message, $unresolved_matches );
$unresolved = array_unique( $unresolved_matches[0] ?? array() );
?>

<div class="wcaet-email-entry">

	<table class="wcaet-meta-table">
		<tr>
			<th><?php esc_html_e( 'To', 'wc-affiliate-email-tester' ); ?></th>
			<td><?php echo esc_html( $to ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Subject', 'wc-affiliate-email-tester' ); ?></th>
			<td><?php echo esc_html( $subject ); ?></td>
		</tr>
	</table>

	<?php if ( $unresolved ) : ?>
		<div class="wcaet-notice wcaet-notice--warning">
			<?php WCA_Email_Tester_Icons::render( 'alert' ); ?>
			<p>
				<strong><?php esc_html_e( 'Unresolved placeholders:', 'wc-affiliate-email-tester' ); ?></strong>
				<?php echo esc_html( implode( ', ', $unresolved ) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Preview tab -->
	<div class="wcaet-entry-section">
		<button
			type="button"
			class="wcaet-collapsible-toggle is-open"
			aria-expanded="true"
			data-target="wcaet-preview-<?php echo esc_attr( $i ); ?>"
			data-label-open="<?php esc_attr_e( 'Hide Preview', 'wc-affiliate-email-tester' ); ?>"
			data-label-closed="<?php esc_attr_e( 'Show Preview', 'wc-affiliate-email-tester' ); ?>"
		>
			<?php WCA_Email_Tester_Icons::render( 'eye' ); ?>
			<span><?php esc_html_e( 'Hide Preview', 'wc-affiliate-email-tester' ); ?></span>
			<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
		</button>
		<div id="wcaet-preview-<?php echo esc_attr( $i ); ?>">
			<?php
			$preview_html = WCA_Email_Tester_Admin::wrap_preview_html( $message );
			$encoded      = base64_encode( $preview_html ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			?>
			<iframe
				class="wcaet-preview-iframe"
				src="data:text/html;base64,<?php echo esc_attr( $encoded ); ?>"
				sandbox="allow-same-origin"
				referrerpolicy="no-referrer"
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
			data-target="wcaet-source-<?php echo esc_attr( $i ); ?>"
			data-label-open="<?php esc_attr_e( 'Hide Source', 'wc-affiliate-email-tester' ); ?>"
			data-label-closed="<?php esc_attr_e( 'Show Source', 'wc-affiliate-email-tester' ); ?>"
		>
			<?php WCA_Email_Tester_Icons::render( 'code' ); ?>
			<span><?php esc_html_e( 'Show Source', 'wc-affiliate-email-tester' ); ?></span>
			<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
		</button>
		<div id="wcaet-source-<?php echo esc_attr( $i ); ?>" hidden>
			<pre class="wcaet-source-block"><code><?php echo esc_html( $message ); ?></code></pre>
		</div>
	</div>

	<!-- Headers -->
	<?php if ( $headers ) : ?>
	<div class="wcaet-entry-section">
		<button
			type="button"
			class="wcaet-collapsible-toggle"
			aria-expanded="false"
			data-target="wcaet-headers-<?php echo esc_attr( $i ); ?>"
			data-label-open="<?php esc_attr_e( 'Hide Headers', 'wc-affiliate-email-tester' ); ?>"
			data-label-closed="<?php esc_attr_e( 'Show Headers', 'wc-affiliate-email-tester' ); ?>"
		>
			<?php WCA_Email_Tester_Icons::render( 'braces' ); ?>
			<span><?php esc_html_e( 'Show Headers', 'wc-affiliate-email-tester' ); ?></span>
			<?php WCA_Email_Tester_Icons::render( 'chevron-down' ); ?>
		</button>
		<div id="wcaet-headers-<?php echo esc_attr( $i ); ?>" hidden>
			<pre class="wcaet-source-block"><code><?php echo esc_html( $headers ); ?></code></pre>
		</div>
	</div>
	<?php endif; ?>

</div>
