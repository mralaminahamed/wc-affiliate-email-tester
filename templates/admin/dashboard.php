<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap wcaet-wrap">

	<div class="wcaet-hero">
		<div class="wcaet-hero__icon"><?php WCA_Email_Tester_Icons::render( 'mail' ); ?></div>
		<div class="wcaet-hero__content">
			<span class="wcaet-hero__badge">v<?php echo esc_html( WCA_ET_VERSION ); ?></span>
			<h1 class="wcaet-hero__title"><?php esc_html_e( 'WC Affiliate Email Tester', 'wc-affiliate-email-tester' ); ?></h1>
			<p class="wcaet-hero__desc"><?php esc_html_e( 'Preview and debug every WC Affiliate email notification without real triggers or live SMTP.', 'wc-affiliate-email-tester' ); ?></p>
		</div>
	</div>

	<div class="wcaet-stats">
		<div class="wcaet-stat-card">
			<div class="wcaet-stat-card__icon"><?php WCA_Email_Tester_Icons::render( 'mail' ); ?></div>
			<div class="wcaet-stat-card__body">
				<div class="wcaet-stat-card__value"><?php echo esc_html( number_format_i18n( $total ) ); ?></div>
				<div class="wcaet-stat-card__label"><?php esc_html_e( 'Total Logged', 'wc-affiliate-email-tester' ); ?></div>
			</div>
		</div>
		<div class="wcaet-stat-card wcaet-stat-card--sent">
			<div class="wcaet-stat-card__icon"><?php WCA_Email_Tester_Icons::render( 'circle-check' ); ?></div>
			<div class="wcaet-stat-card__body">
				<div class="wcaet-stat-card__value"><?php echo esc_html( number_format_i18n( $sent ) ); ?></div>
				<div class="wcaet-stat-card__label"><?php esc_html_e( 'Sent', 'wc-affiliate-email-tester' ); ?></div>
			</div>
		</div>
		<div class="wcaet-stat-card wcaet-stat-card--failed">
			<div class="wcaet-stat-card__icon"><?php WCA_Email_Tester_Icons::render( 'alert' ); ?></div>
			<div class="wcaet-stat-card__body">
				<div class="wcaet-stat-card__value"><?php echo esc_html( number_format_i18n( $failed ) ); ?></div>
				<div class="wcaet-stat-card__label"><?php esc_html_e( 'Failed', 'wc-affiliate-email-tester' ); ?></div>
			</div>
		</div>
		<div class="wcaet-stat-card wcaet-stat-card--test">
			<div class="wcaet-stat-card__icon"><?php WCA_Email_Tester_Icons::render( 'send' ); ?></div>
			<div class="wcaet-stat-card__body">
				<div class="wcaet-stat-card__value"><?php echo esc_html( number_format_i18n( $test_count ) ); ?></div>
				<div class="wcaet-stat-card__label"><?php esc_html_e( 'Test Sends', 'wc-affiliate-email-tester' ); ?></div>
			</div>
		</div>
	</div>

	<div class="wcaet-features">

		<div class="wcaet-feature-card">
			<div class="wcaet-feature-card__icon"><?php WCA_Email_Tester_Icons::render( 'send' ); ?></div>
			<h3><?php esc_html_e( 'Test Emails', 'wc-affiliate-email-tester' ); ?></h3>
			<p><?php esc_html_e( 'Fire any WC Affiliate email against a real affiliate, referral, or transaction. Preview rendered output before it reaches a real inbox.', 'wc-affiliate-email-tester' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester-testing' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Open Tester', 'wc-affiliate-email-tester' ); ?>
			</a>
		</div>

		<div class="wcaet-feature-card">
			<div class="wcaet-feature-card__icon"><?php WCA_Email_Tester_Icons::render( 'list' ); ?></div>
			<h3><?php esc_html_e( 'Email Logs', 'wc-affiliate-email-tester' ); ?></h3>
			<p><?php esc_html_e( 'Browse all captured outgoing emails. Filter by status, source, or search recipient and subject.', 'wc-affiliate-email-tester' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester-logs' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'View Logs', 'wc-affiliate-email-tester' ); ?>
			</a>
		</div>

		<div class="wcaet-feature-card">
			<div class="wcaet-feature-card__icon"><?php WCA_Email_Tester_Icons::render( 'eye-off' ); ?></div>
			<h3><?php esc_html_e( 'Dry-Run Mode', 'wc-affiliate-email-tester' ); ?></h3>
			<p><?php esc_html_e( 'Block actual wp_mail() dispatch while still capturing the fully-rendered email so you can inspect it without sending anything.', 'wc-affiliate-email-tester' ); ?></p>
		</div>

		<div class="wcaet-feature-card">
			<div class="wcaet-feature-card__icon"><?php WCA_Email_Tester_Icons::render( 'settings' ); ?></div>
			<h3><?php esc_html_e( 'Settings', 'wc-affiliate-email-tester' ); ?></h3>
			<p><?php esc_html_e( 'Configure logging retention, test email capture, and defaults for the test form.', 'wc-affiliate-email-tester' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester-settings' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Settings', 'wc-affiliate-email-tester' ); ?>
			</a>
		</div>

		<?php if ( defined( 'WC_AFFILIATE_PRO_VERSION' ) ) : ?>
		<div class="wcaet-feature-card wcaet-feature-card--pro">
			<div class="wcaet-feature-card__icon"><?php WCA_Email_Tester_Icons::render( 'users' ); ?></div>
			<h3><?php esc_html_e( 'Pro Email Types', 'wc-affiliate-email-tester' ); ?></h3>
			<p><?php esc_html_e( 'WC Affiliate Pro detected. MLC commission, signup bonus, and referral bonus emails are available in the tester.', 'wc-affiliate-email-tester' ); ?></p>
		</div>
		<?php endif; ?>

	</div>

</div>
