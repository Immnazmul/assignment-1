<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$members_table  = $wpdb->prefix . 'fmp_members';
$payments_table = $wpdb->prefix . 'fmp_payments';

$member_id = isset( $_GET['member_id'] ) ? absint( $_GET['member_id'] ) : 0;
$year      = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );

$member = $member_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $members_table WHERE id=%d", $member_id ) ) : null;

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Payments', 'fund-manager-pro' ); ?></h1>
	<form method="get">
		<input type="hidden" name="page" value="fmp-payments" />
		<p>
			<label><?php esc_html_e( 'Member', 'fund-manager-pro' ); ?></label>
			<select name="member_id" required>
				<option value="">
					<?php esc_html_e( 'Select a member', 'fund-manager-pro' ); ?>
				</option>
				<?php
				$all_members = $wpdb->get_results( "SELECT id, name FROM $members_table WHERE active=1 ORDER BY name ASC" );
				foreach ( $all_members as $m ) {
					echo '<option value="' . esc_attr( $m->id ) . '" ' . selected( $member_id, $m->id, false ) . '>' . esc_html( $m->name ) . '</option>';
				}
				?>
			</select>
			<label><?php esc_html_e( 'Year', 'fund-manager-pro' ); ?></label>
			<input type="number" name="year" value="<?php echo esc_attr( $year ); ?>" min="2000" max="2100" />
			<?php submit_button( __( 'Load', 'fund-manager-pro' ), 'secondary', '', false ); ?>
		</p>
	</form>

	<?php if ( $member ) : ?>
		<h2><?php echo esc_html( $member->name ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'fmp_save_payments' ); ?>
			<input type="hidden" name="action" value="fmp_save_payments" />
			<input type="hidden" name="member_id" value="<?php echo esc_attr( $member->id ); ?>" />
			<input type="hidden" name="year" value="<?php echo esc_attr( $year ); ?>" />

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Month', 'fund-manager-pro' ); ?></th>
						<th><?php esc_html_e( 'Paid', 'fund-manager-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rows = $wpdb->get_results( $wpdb->prepare( "SELECT month, paid FROM $payments_table WHERE member_id=%d AND year=%d ORDER BY month ASC", $member->id, $year ) );
					$paid_map = [];
					foreach ( $rows as $r ) { $paid_map[ (int) $r->month ] = (int) $r->paid; }
					for ( $m = 1; $m <= 12; $m++ ) :
					?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></td>
						<td>
							<label>
								<input type="checkbox" name="months[]" value="<?php echo esc_attr( $m ); ?>" <?php checked( isset( $paid_map[ $m ] ) ? $paid_map[ $m ] : 0, 1 ); ?> />
							</label>
						</td>
					</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			<p>
				<label><?php esc_html_e( 'Monthly Amount', 'fund-manager-pro' ); ?></label>
				<input type="number" step="0.01" name="monthly_amount" value="<?php echo esc_attr( $member->monthly_amount ); ?>" />
			</p>
			<?php submit_button( __( 'Save Payments', 'fund-manager-pro' ) ); ?>
		</form>
	<?php endif; ?>
</div>