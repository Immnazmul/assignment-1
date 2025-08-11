<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import / Export', 'fund-manager-pro' ); ?></h1>

	<h2><?php esc_html_e( 'Export Members', 'fund-manager-pro' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'fmp_export_csv' ); ?>
		<input type="hidden" name="action" value="fmp_export_csv" />
		<p>
			<label><?php esc_html_e( 'Year', 'fund-manager-pro' ); ?></label>
			<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2000" max="2100" />
		</p>
		<?php submit_button( __( 'Export CSV', 'fund-manager-pro' ) ); ?>
	</form>

	<hr />
	<h2><?php esc_html_e( 'Import Members', 'fund-manager-pro' ); ?></h2>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'fmp_import_csv' ); ?>
		<input type="hidden" name="action" value="fmp_import_csv" />
		<p>
			<label><?php esc_html_e( 'CSV File', 'fund-manager-pro' ); ?></label>
			<input type="file" name="fmp_csv" accept=".csv" required />
		</p>
		<p>
			<label><?php esc_html_e( 'Year', 'fund-manager-pro' ); ?></label>
			<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2000" max="2100" />
		</p>
		<p><?php esc_html_e( 'CSV header: name,phone,whatsapp,monthly_amount,months_paid (e.g., 1|3|12)', 'fund-manager-pro' ); ?></p>
		<?php submit_button( __( 'Import CSV', 'fund-manager-pro' ) ); ?>
	</form>
</div>