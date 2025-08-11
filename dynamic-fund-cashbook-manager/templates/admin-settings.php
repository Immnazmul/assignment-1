<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = get_option( 'dfcm_funds', [] );
if ( ! is_array( $funds ) ) { $funds = []; }
?>
<div class="wrap container-fluid">
	<h1><?php esc_html_e( 'Settings', 'dfcm' ); ?></h1>
	<form method="post" action="options.php" class="card card-body">
		<?php settings_fields( 'dfcm_settings_group' ); ?>
		<h3><?php esc_html_e( 'Fund Categories', 'dfcm' ); ?></h3>
		<div id="dfcm-funds">
			<?php foreach ( $funds as $i => $f ) : ?>
				<div class="mb-2"><input type="text" name="dfcm_funds[]" class="regular-text" value="<?php echo esc_attr( $f ); ?>" /></div>
			<?php endforeach; ?>
			<div class="mb-2"><input type="text" name="dfcm_funds[]" class="regular-text" placeholder="<?php esc_attr_e( 'Add new fund', 'dfcm' ); ?>" /></div>
		</div>
		<h3 class="mt-3"><?php esc_html_e( 'Frontend Report Password', 'dfcm' ); ?></h3>
		<p><input type="text" name="dfcm_front_password" class="regular-text" value="<?php echo esc_attr( get_option( 'dfcm_front_password', '' ) ); ?>" /></p>
		<?php submit_button(); ?>
	</form>
	<p class="description mt-2"><?php esc_html_e( 'Note: If password is empty, the report is public.', 'dfcm' ); ?></p>
</div>