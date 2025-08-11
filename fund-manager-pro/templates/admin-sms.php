<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$members_table  = $wpdb->prefix . 'fmp_members';
$payments_table = $wpdb->prefix . 'fmp_payments';

$year   = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
$month  = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : (int) current_time( 'n' );
$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'unpaid'; // unpaid|paid|all

// Default message based on status
$default_due  = get_option( 'fmp_message_template', get_option( 'fmp_msg_due_template', '' ) );
$default_paid = get_option( 'fmp_msg_paid_template', '' );
$default_message = $status === 'paid' ? $default_paid : $default_due;

$params = [ $year, $month ];
$sql = "SELECT m.id, m.name, m.phone, m.whatsapp, p.paid
	FROM $members_table m
	LEFT JOIN $payments_table p ON p.member_id = m.id AND p.year = %d AND p.month = %d
	WHERE m.active = 1";

if ( $status === 'paid' ) {
	$sql .= ' AND p.paid = 1';
} elseif ( $status === 'unpaid' ) {
	$sql .= ' AND (p.paid = 0 OR p.member_id IS NULL)';
}
$sql .= ' ORDER BY m.name ASC';
$members = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Bulk SMS', 'fund-manager-pro' ); ?></h1>
	<form method="get" action="">
		<input type="hidden" name="page" value="fmp-sms" />
		<p>
			<label><?php esc_html_e( 'Year', 'fund-manager-pro' ); ?></label>
			<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2000" max="2100" />
			<label><?php esc_html_e( 'Month', 'fund-manager-pro' ); ?></label>
			<select name="month">
				<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
					<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $month, $m ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
				<?php endfor; ?>
			</select>
			<label><?php esc_html_e( 'Status', 'fund-manager-pro' ); ?></label>
			<select name="status">
				<option value="unpaid" <?php selected( $status, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'fund-manager-pro' ); ?></option>
				<option value="paid" <?php selected( $status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'fund-manager-pro' ); ?></option>
				<option value="all" <?php selected( $status, 'all' ); ?>><?php esc_html_e( 'All', 'fund-manager-pro' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'fund-manager-pro' ), 'secondary', '', false ); ?>
		</p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'fmp_send_sms' ); ?>
		<input type="hidden" name="action" value="fmp_send_sms" />
		<p><?php esc_html_e( 'Use template placeholders: {name}, {month}, {amount}, {whatsapp}', 'fund-manager-pro' ); ?></p>
		<p>
			<textarea name="message" rows="4" class="large-text" required><?php echo esc_textarea( $default_message ); ?></textarea>
		</p>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><input type="checkbox" id="fmp-check-all" /></th>
					<th><?php esc_html_e( 'Name', 'fund-manager-pro' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'fund-manager-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'fund-manager-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $members as $m ) : ?>
					<tr>
						<td><input type="checkbox" name="member_ids[]" value="<?php echo esc_attr( $m->id ); ?>" /></td>
						<td><?php echo esc_html( $m->name ); ?></td>
						<td><?php echo esc_html( $m->phone ); ?></td>
						<td><?php echo ( isset( $m->paid ) && (int) $m->paid === 1 ) ? '<span style="color:green">' . esc_html__( 'Paid', 'fund-manager-pro' ) . '</span>' : '<span style="color:red">' . esc_html__( 'Unpaid', 'fund-manager-pro' ) . '</span>'; ?></td>
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