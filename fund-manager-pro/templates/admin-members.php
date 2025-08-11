<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$members_table  = $wpdb->prefix . 'fmp_members';
$payments_table = $wpdb->prefix . 'fmp_payments';

$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$year   = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
$month  = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : (int) current_time( 'n' );
$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all'; // all|paid|unpaid
$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
$order   = isset( $_GET['order'] ) ? ( strtolower( $_GET['order'] ) === 'asc' ? 'ASC' : 'DESC' ) : 'ASC';

$params = [ $year, $month ];
$sql = "SELECT m.*, p.paid AS paid_status FROM $members_table m
LEFT JOIN $payments_table p ON p.member_id = m.id AND p.year = %d AND p.month = %d
WHERE 1=1";

if ( $search ) {
	$sql    .= ' AND (m.name LIKE %s OR m.phone LIKE %s OR m.whatsapp LIKE %s)';
	$like    = '%' . $wpdb->esc_like( $search ) . '%';
	$params[] = $like; $params[] = $like; $params[] = $like;
}

if ( $status === 'paid' ) {
	$sql .= ' AND p.paid = 1';
} elseif ( $status === 'unpaid' ) {
	$sql .= ' AND (p.paid = 0 OR p.member_id IS NULL)';
}

if ( $orderby === 'status' ) {
	$sql .= ' ORDER BY p.paid ' . $order . ', m.name ASC';
} else {
	$sql .= ' ORDER BY m.created_at DESC';
}

$query = $wpdb->prepare( $sql, $params );
$members = $wpdb->get_results( $query );

$edit_member = null;
if ( isset( $_GET['edit'] ) ) {
	$edit_member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $members_table WHERE id=%d", absint( $_GET['edit'] ) ) );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Members', 'fund-manager-pro' ); ?></h1>

	<form method="get" action="">
		<input type="hidden" name="page" value="fund-manager-pro" />
		<p class="search-box">
			<label class="screen-reader-text" for="member-search-input"><?php esc_html_e( 'Search Members', 'fund-manager-pro' ); ?></label>
			<input type="search" id="member-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" />
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
				<option value="all" <?php selected( $status, 'all' ); ?>><?php esc_html_e( 'All', 'fund-manager-pro' ); ?></option>
				<option value="paid" <?php selected( $status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'fund-manager-pro' ); ?></option>
				<option value="unpaid" <?php selected( $status, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'fund-manager-pro' ); ?></option>
			</select>
			<label><?php esc_html_e( 'Sort By', 'fund-manager-pro' ); ?></label>
			<select name="orderby">
				<option value="" <?php selected( $orderby, '' ); ?>><?php esc_html_e( 'Default', 'fund-manager-pro' ); ?></option>
				<option value="status" <?php selected( $orderby, 'status' ); ?>><?php esc_html_e( 'Status', 'fund-manager-pro' ); ?></option>
			</select>
			<select name="order">
				<option value="asc" <?php selected( strtolower( $order ), 'asc' ); ?>><?php esc_html_e( 'ASC', 'fund-manager-pro' ); ?></option>
				<option value="desc" <?php selected( strtolower( $order ), 'desc' ); ?>><?php esc_html_e( 'DESC', 'fund-manager-pro' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'fund-manager-pro' ), 'secondary', '', false ); ?>
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
				<th><?php esc_html_e( 'Member Name', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'WhatsApp', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Paid Months', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Status', 'fund-manager-pro' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'fund-manager-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $members as $m ) : ?>
				<?php
				$paid_months = $wpdb->get_col( $wpdb->prepare( "SELECT month FROM $payments_table WHERE member_id=%d AND year=%d AND paid=1 ORDER BY month ASC", $m->id, $year ) );
				$paid_label  = implode( ', ', array_map( function( $mm ) { return date_i18n( 'M', mktime( 0, 0, 0, (int) $mm, 1 ) ); }, $paid_months ) );
				$status_label = ( isset( $m->paid_status ) && (int) $m->paid_status === 1 ) ? __( 'Paid', 'fund-manager-pro' ) : __( 'Unpaid', 'fund-manager-pro' );
				?>
				<tr>
					<td><?php echo esc_html( $m->name ); ?></td>
					<td><?php echo esc_html( $m->phone ); ?></td>
					<td><?php echo esc_html( $m->whatsapp ); ?></td>
					<td><?php echo esc_html( number_format( (float) $m->monthly_amount, 2 ) ); ?></td>
					<td><?php echo esc_html( $paid_label ); ?></td>
					<td><?php echo ( isset( $m->paid_status ) && (int) $m->paid_status === 1 ) ? '<span style="color:green">' . esc_html( $status_label ) . '</span>' : '<span style="color:red">' . esc_html( $status_label ) . '</span>'; ?></td>
					<td>
						<a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => 'fund-manager-pro', 'edit' => $m->id ] , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'fund-manager-pro' ); ?></a>
						<a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => 'fmp-payments', 'member_id' => $m->id, 'year' => $year ] , admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Payments', 'fund-manager-pro' ); ?></a>
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