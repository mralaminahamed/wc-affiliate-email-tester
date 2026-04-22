<?php defined( 'ABSPATH' ) || exit;
/** @var array  $options */
/** @var bool   $saved */
?>
<div class="wrap wcaet-wrap">

	<div class="wcaet-page-header">
		<h1><?php WCA_Email_Tester_Icons::render( 'settings' ); ?> <?php esc_html_e( 'Email Tester Settings', 'wc-affiliate-email-tester' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester' ) ); ?>" class="wcaet-back-link">
			<?php WCA_Email_Tester_Icons::render( 'arrow-left' ); ?>
			<?php esc_html_e( 'Dashboard', 'wc-affiliate-email-tester' ); ?>
		</a>
	</div>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'wc-affiliate-email-tester' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" class="wcaet-settings-form">
		<?php wp_nonce_field( 'wca_et_save_settings', 'wca_et_settings_nonce' ); ?>

		<div class="wcaet-card wcaet-settings-section">
			<h2><?php esc_html_e( 'Email Logging', 'wc-affiliate-email-tester' ); ?></h2>

			<div class="wcaet-field wcaet-field--checkbox">
				<label>
					<input type="checkbox" name="logger_enabled" value="1" id="logger_enabled" <?php checked( $options['logger_enabled'] ); ?>>
					<?php esc_html_e( 'Enable email logging', 'wc-affiliate-email-tester' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Capture all outgoing wp_mail() calls to the database.', 'wc-affiliate-email-tester' ); ?></p>
			</div>

			<div class="wcaet-field wcaet-field--checkbox wcaet-dependent" data-depends-on="logger_enabled">
				<label>
					<input type="checkbox" name="logger_log_test_emails" value="1" <?php checked( $options['logger_log_test_emails'] ); ?>>
					<?php esc_html_e( 'Log test emails', 'wc-affiliate-email-tester' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Also log emails sent through this tester (tagged as source: test).', 'wc-affiliate-email-tester' ); ?></p>
			</div>
		</div>

		<div class="wcaet-card wcaet-settings-section">
			<h2><?php esc_html_e( 'Log Retention', 'wc-affiliate-email-tester' ); ?></h2>

			<div class="wcaet-field wcaet-field--checkbox">
				<label>
					<input type="checkbox" name="logger_retention_count_enabled" value="1" id="logger_retention_count_enabled" <?php checked( $options['logger_retention_count_enabled'] ); ?>>
					<?php esc_html_e( 'Limit total log entries', 'wc-affiliate-email-tester' ); ?>
				</label>
			</div>

			<div class="wcaet-field wcaet-dependent" data-depends-on="logger_retention_count_enabled">
				<label for="logger_retention_count"><?php esc_html_e( 'Maximum entries to keep', 'wc-affiliate-email-tester' ); ?></label>
				<input
					type="number"
					id="logger_retention_count"
					name="logger_retention_count"
					value="<?php echo esc_attr( $options['logger_retention_count'] ); ?>"
					min="10"
					max="100000"
					style="width:120px;"
				>
				<p class="description"><?php esc_html_e( 'Oldest entries beyond this limit are deleted on an hourly cron.', 'wc-affiliate-email-tester' ); ?></p>
			</div>

			<hr>

			<div class="wcaet-field wcaet-field--checkbox">
				<label>
					<input type="checkbox" name="logger_retention_days_enabled" value="1" id="logger_retention_days_enabled" <?php checked( $options['logger_retention_days_enabled'] ); ?>>
					<?php esc_html_e( 'Delete entries older than N days', 'wc-affiliate-email-tester' ); ?>
				</label>
			</div>

			<div class="wcaet-field wcaet-dependent" data-depends-on="logger_retention_days_enabled">
				<label for="logger_retention_days"><?php esc_html_e( 'Days to retain logs', 'wc-affiliate-email-tester' ); ?></label>
				<input
					type="number"
					id="logger_retention_days"
					name="logger_retention_days"
					value="<?php echo esc_attr( $options['logger_retention_days'] ); ?>"
					min="1"
					max="3650"
					style="width:120px;"
				>
			</div>
		</div>

		<div class="wcaet-card wcaet-settings-section">
			<h2><?php esc_html_e( 'Uninstall', 'wc-affiliate-email-tester' ); ?></h2>

			<div class="wcaet-field wcaet-field--checkbox">
				<label>
					<input type="checkbox" name="logger_delete_on_uninstall" value="1" <?php checked( $options['logger_delete_on_uninstall'] ); ?>>
					<?php esc_html_e( 'Delete all log data on uninstall', 'wc-affiliate-email-tester' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When checked, the log table will be dropped when the plugin is deleted.', 'wc-affiliate-email-tester' ); ?></p>
			</div>
		</div>

		<p>
			<button type="submit" class="button button-primary">
				<?php WCA_Email_Tester_Icons::render( 'save' ); ?>
				<?php esc_html_e( 'Save Settings', 'wc-affiliate-email-tester' ); ?>
			</button>
		</p>

	</form>

</div>
