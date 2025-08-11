<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Fund Manager Pro Settings', 'fund-manager-pro' ); ?></h1>
	<form method="post" action="options.php">
		<?php settings_fields( 'fmp_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="fmp_api_key"><?php esc_html_e( 'BulkSMSBD API Key', 'fund-manager-pro' ); ?></label></th>
					<td><input type="text" id="fmp_api_key" name="fmp_api_key" class="regular-text" value="<?php echo esc_attr( get_option( 'fmp_api_key', '' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fmp_sender_id"><?php esc_html_e( 'Sender ID', 'fund-manager-pro' ); ?></label></th>
					<td><input type="text" id="fmp_sender_id" name="fmp_sender_id" class="regular-text" value="<?php echo esc_attr( get_option( 'fmp_sender_id', '' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fmp_cron_day"><?php esc_html_e( 'Default Cron Day of Month', 'fund-manager-pro' ); ?></label></th>
					<td><input type="number" id="fmp_cron_day" name="fmp_cron_day" min="1" max="28" value="<?php echo esc_attr( get_option( 'fmp_cron_day', 5 ) ); ?>" /> <span class="description"><?php esc_html_e( 'Automated SMS runs on this day each month.', 'fund-manager-pro' ); ?></span></td>
				</tr>
				<tr>
					<th><label for="fmp_message_template"><?php esc_html_e( 'Default Message Template', 'fund-manager-pro' ); ?></label></th>
					<td>
						<textarea id="fmp_message_template" name="fmp_message_template" class="large-text" rows="4"><?php echo esc_textarea( get_option( 'fmp_message_template', '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Available placeholders: {name}, {month}, {amount}, {whatsapp}', 'fund-manager-pro' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>