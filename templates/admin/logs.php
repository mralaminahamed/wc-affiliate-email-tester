<?php defined( 'ABSPATH' ) || exit;
/** @var WCA_Email_Tester_Log_List $list_table */

if ( ! empty( $_GET['deleted'] ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php
			$n = absint( $_GET['deleted'] );
			echo esc_html( sprintf(
				/* translators: %d: number of deleted logs */
				_n( '%d log entry deleted.', '%d log entries deleted.', $n, 'wc-affiliate-email-tester' ),
				$n
			) );
		?></p>
	</div>
<?php endif;

if ( ! empty( $_GET['cleared'] ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e( 'All log entries cleared.', 'wc-affiliate-email-tester' ); ?></p>
	</div>
<?php endif;

if ( ! empty( $_GET['purged'] ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e( 'Retention rules applied. Stale log entries purged.', 'wc-affiliate-email-tester' ); ?></p>
	</div>
<?php endif; ?>

<div class="wrap wcaet-wrap">

	<div class="wcaet-page-header">
		<h1><?php WCA_Email_Tester_Icons::render( 'list' ); ?> <?php esc_html_e( 'Email Logs', 'wc-affiliate-email-tester' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wca-email-tester' ) ); ?>" class="wcaet-back-link">
			<?php WCA_Email_Tester_Icons::render( 'arrow-left' ); ?>
			<?php esc_html_e( 'Dashboard', 'wc-affiliate-email-tester' ); ?>
		</a>
	</div>

	<div class="wcaet-logs-controls">
		<form method="post" class="wcaet-clear-form" onsubmit="return confirm('<?php esc_attr_e( 'Delete ALL log entries? This cannot be undone.', 'wc-affiliate-email-tester' ); ?>')">
			<?php wp_nonce_field( 'wca_et_clear_logs', 'wca_et_clear_logs_nonce' ); ?>
			<button type="submit" class="button wcaet-clear-btn">
				<?php WCA_Email_Tester_Icons::render( 'trash' ); ?>
				<?php esc_html_e( 'Clear All Logs', 'wc-affiliate-email-tester' ); ?>
			</button>
		</form>
		<form method="post" class="wcaet-clear-form">
			<?php wp_nonce_field( 'wca_et_purge', 'wca_et_purge_nonce' ); ?>
			<button type="submit" class="button">
				<?php esc_html_e( 'Purge Now (apply retention rules)', 'wc-affiliate-email-tester' ); ?>
			</button>
		</form>
	</div>

	<form id="wcaet-logs-form" method="get">
		<input type="hidden" name="page" value="wca-email-tester-logs">
		<?php
		$list_table->views();
		$list_table->search_box( __( 'Search Logs', 'wc-affiliate-email-tester' ), 'wcaet-logs' );
		$list_table->display();
		?>
	</form>

</div>
