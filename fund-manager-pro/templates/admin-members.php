<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$members_table = $wpdb->prefix . 'fmp_members';

$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$where  = 'WHERE 1=1';
$params = [];
if ( $search ) {
	$where .= ' AND (name LIKE %s OR phone LIKE %s OR whatsapp LIKE %s)';
	$like = '%' . $wpdb->esc_like( $search ) . '%';
	$params[] = $like; $params[] = $like; $params[] = $like;
}

$query = "SELECT * FROM $members_table $where ORDER BY created_at DESC";
if ( $params ) {
	$query = $wpdb->prepare( $query, $params );
}
$members = $wpdb->get_results( $query );

$edit_member = null;
if ( isset( $_GET['edit'] ) ) {
	$edit_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $members_table WHERE id=%d", absint( $_GET['edit'] ) ) );
}

$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Members', 'fund-manager-pro' ); ?></h1>

	<form method="get" action="">
		<input type="hidden" name="page" value="fund-manager-pro" />
		<p class="search-box">
			<label class="screen-reader-text" for="member-search-input"><?php esc_html_e( 'Search Members', 'fund-manager-pro' ); ?></label>
			<input type="search" id="member-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<?php submit_button( __( 'Search', 'fund-manager-pro' ), 'secondary', '', false ); ?>
		</p>
	</form>

	<h2><?php echo $edit_member ? esc_html__( 'Edit Member', 'fund-manager-pro' ) : esc_html__( 'Add New Member', 'fund-manager-pro' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'fmp_save_member' ); ?>
		<input type="hidden" name="action" value="fmp_save_member" />
		<input type="hidden" name="member_id" value="<?php echo $edit_member ? esc_attr( $edit_member->id ) : 0; ?>" />
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="fmp_name"><?php esc_html_e( 'Name', 'fund-manager-pro' ); ?></label></th>
					<td><input name="name" type="text" id="fmp_name" value="<?php echo $edit_member ? esc_attr( $edit_member->name ) : ''; ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="fmp_phone"><?php esc_html_e( 'Phone Number', 'fund-manager-pro' ); ?></label></th>
					<td><input name="phone" type="text" id="fmp_phone" value="<?php echo $edit_member ? esc_attr( $edit_member->phone ) : ''; ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="fmp_whatsapp"><?php esc_html_e( 'WhatsApp Number', 'fund-manager-pro' ); ?></label></th>
					<td><input name="whatsapp" type="text" id="fmp_whatsapp" value="<?php echo $edit_member ? esc_attr( $edit_member->whatsapp ) : ''; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="fmp_amount"><?php esc_html_e( 'Monthly Subscription Amount', 'fund-manager-pro' ); ?></label></th>
					<td><input name="monthly_amount" type="number" step="0.01" id="fmp_amount" value="<?php echo $edit_member ? esc_attr( $edit_member->monthly_amount ) : ''; ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="fmp_user_id"><?php esc_html_e( 'Linked WP User (optional)', 'fund-manager-pro' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_users( [
							'show_option_none' => __( '— No User —', 'fund-manager-pro' ),
							'name'             => 'user_id',
							'selected'         => $edit_member ? (int) $edit_member->user_id : 0,
							'role__in'         => [ 'fmp_member' ],
						] );
						?>
					</td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Active', 'fund-manager-pro' ); ?></label></th>
					<td><label><input name="active" type="checkbox" <?php checked( $edit_member ? (int) $edit_member->active : 1, 1 ); ?> /> <?php esc_html_e( 'Active Member', 'fund-manager-pro' ); ?></label></td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Year', 'fund-manager-pro' ); ?></label></th>
					<td>
						<input name="year" type="number" min="2000" max="2100" value="<?php echo esc_attr( $year ); ?>" />
					</td>
				</tr>
				<tr>
					<th><label><?php esc_html_e( 'Months Paid', 'fund-manager-pro' ); ?></label></th>
					<td>
						<select name="months_paid[]" multiple size="6">
							<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
								<option value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?></option>
							<?php endfor; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button( $edit_member ? __( 'Update Member', 'fund-manager-pro' ) : __( 'Add Member', 'fund-manager-pro' ) ); ?>
	</form>

	<hr />
	<h2><?php esc_html_e( 'All Members', 'fund-manager-pro' ); ?></h2>
	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'WhatsApp', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Monthly Amount', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Active', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'fund-manager-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $members as $m ) : ?>
				<tr>
					<td><?php echo esc_html( $m->name ); ?></td>
					<td><?php echo esc_html( $m->phone ); ?></td>
					<td><?php echo esc_html( $m->whatsapp ); ?></td>
					<td><?php echo esc_html( number_format( (float) $m->monthly_amount, 2 ) ); ?></td>
					<td><?php echo $m->active ? esc_html__( 'Yes', 'fund-manager-pro' ) : esc_html__( 'No', 'fund-manager-pro' ); ?></td>
					<td>
						<a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => 'fund-manager-pro', 'edit' => $m->id ] , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'fund-manager-pro' ); ?></a>
						<a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => 'fmp-payments', 'member_id' => $m->id ] , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Payments', 'fund-manager-pro' ); ?></a>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
							<?php wp_nonce_field( 'fmp_delete_member' ); ?>
							<input type="hidden" name="action" value="fmp_delete_member" />
							<input type="hidden" name="member_id" value="<?php echo esc_attr( $m->id ); ?>" />
							<?php submit_button( __( 'Delete', 'fund-manager-pro' ), 'delete', '', false, [ 'onclick' => "return confirm('Are you sure?');" ] ); ?>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>