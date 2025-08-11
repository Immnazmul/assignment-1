<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import Transactions (CSV)', 'mfm' ); ?></h1>
	<p><?php esc_html_e( 'Upload a CSV with a header row. On the next step you will map CSV columns to fields, and choose fund mapping.', 'mfm' ); ?></p>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfm-card">
		<?php wp_nonce_field( 'mfm_import_step1' ); ?>
		<input type="hidden" name="action" value="mfm_import_step1" />
		<input type="file" name="mfm_csv" accept=".csv" required />
		<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Upload & Continue', 'mfm' ); ?></button></p>
	</form>
</div>