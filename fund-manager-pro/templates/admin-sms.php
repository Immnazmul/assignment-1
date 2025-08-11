<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$members_table = $wpdb->prefix . 'fmp_members';
$members = $wpdb->get_results( "SELECT id, name, phone FROM $members_table WHERE active=1 ORDER BY name ASC" );
$default_message = get_option( 'fmp_message_template', '' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Bulk SMS', 'fund-manager-pro' ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'fmp_send_sms' ); ?>
		<input type="hidden" name="action" value="fmp_send_sms" />
		<p><?php esc_html_e( 'Select members to send an SMS. Use template placeholders: {name}, {month}, {amount}, {whatsapp}', 'fund-manager-pro' ); ?></p>
		<p>
			<textarea name="message" rows="4" class="large-text" required><?php echo esc_textarea( $default_message ); ?></textarea>
		</p>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><input type="checkbox" id="fmp-check-all" /></th>
					<th><?php esc_html_e( 'Name', 'fund-manager-pro' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'fund-manager-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $members as $m ) : ?>
					<tr>
						<td><input type="checkbox" name="member_ids[]" value="<?php echo esc_attr( $m->id ); ?>" /></td>
						<td><?php echo esc_html( $m->name ); ?></td>
						<td><?php echo esc_html( $m->phone ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button( __( 'Send SMS', 'fund-manager-pro' ) ); ?>
	</form>
	<script>
		jQuery(function($){
			$('#fmp-check-all').on('change', function(){
				$('input[name="member_ids[]"]').prop('checked', $(this).is(':checked'));
			});
		});
	</script>
</div>