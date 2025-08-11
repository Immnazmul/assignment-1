<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = MFM_Export::get_funds();
$fund_id = isset( $_GET['fund_id'] ) ? absint( $_GET['fund_id'] ) : ( isset( $funds[0] ) ? (int) $funds[0]->id : 0 );
$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
$summary = $fund_id ? MFM_Export::get_fund_summary( $fund_id, $from, $to, $type ) : [ 'opening'=>0,'in'=>0,'out'=>0,'balance'=>0 ];

// Pagination
global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
$params = [ $fund_id ]; $where = ' WHERE fund_id=%d';
if ( $type && in_array( $type, [ 'in','out' ], true ) ) { $where .= ' AND trx_type=%s'; $params[] = $type; }
if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
$page = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); $per_page = 20; $offset = ( $page - 1 ) * $per_page;
$total = $fund_id ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tx $where", $params ) ) : 0;
$sql = "SELECT * FROM $tx $where ORDER BY trx_date DESC, trx_time DESC, id DESC LIMIT %d OFFSET %d";
$q_params = array_merge( $params, [ $per_page, $offset ] );
$rows = $fund_id ? $wpdb->get_results( $wpdb->prepare( $sql, $q_params ) ) : [];
$can_crud = current_user_can( 'mfm_manage' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Transactions', 'mfm' ); ?></h1>
	<form method="get" class="mfm-card">
		<input type="hidden" name="page" value="mfm-transactions" />
		<div class="mfm-grid">
			<label><?php esc_html_e( 'Fund', 'mfm' ); ?>
				<select name="fund_id" required>
					<?php foreach ( $funds as $f ) : ?>
						<option value="<?php echo esc_attr( $f->id ); ?>" <?php selected( $fund_id, $f->id ); ?>><?php echo esc_html( $f->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Type', 'mfm' ); ?>
				<select name="type">
					<option value=""><?php esc_html_e( 'All', 'mfm' ); ?></option>
					<option value="in" <?php selected( $type, 'in' ); ?>><?php esc_html_e( 'Cash In', 'mfm' ); ?></option>
					<option value="out" <?php selected( $type, 'out' ); ?>><?php esc_html_e( 'Cash Out', 'mfm' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'From', 'mfm' ); ?><input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" /></label>
			<label><?php esc_html_e( 'To', 'mfm' ); ?><input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" /></label>
		</div>
		<p><button class="button" type="submit"><?php esc_html_e( 'Filter', 'mfm' ); ?></button></p>
	</form>

	<?php if ( $can_crud ) : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfm-card">
		<?php wp_nonce_field( 'mfm_save_trx' ); ?>
		<input type="hidden" name="action" value="mfm_save_trx" />
		<input type="hidden" name="fund_id" value="<?php echo esc_attr( $fund_id ); ?>" />
		<h3><?php esc_html_e( 'Add Transaction', 'mfm' ); ?></h3>
		<div class="mfm-grid">
			<label><?php esc_html_e( 'Date', 'mfm' ); ?><input type="date" name="trx_date" required /></label>
			<label><?php esc_html_e( 'Time', 'mfm' ); ?><input type="time" name="trx_time" required /></label>
			<label><?php esc_html_e( 'Type', 'mfm' ); ?>
				<select name="trx_type" required>
					<option value="in"><?php esc_html_e( 'Cash In', 'mfm' ); ?></option>
					<option value="out"><?php esc_html_e( 'Cash Out', 'mfm' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'Amount', 'mfm' ); ?><input type="number" step="0.01" name="amount" required /></label>
			<label><?php esc_html_e( 'Mode', 'mfm' ); ?><input type="text" name="mode" /></label>
			<label><?php esc_html_e( 'Entry By', 'mfm' ); ?><input type="text" name="entry_by" value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>" /></label>
			<label class="mfm-col2"><?php esc_html_e( 'Description', 'mfm' ); ?><textarea name="description"></textarea></label>
		</div>
		<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save', 'mfm' ); ?></button></p>
	</form>
	<?php else : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Admins can view/export. Accountants can add/delete transactions.', 'mfm' ); ?></p></div>
	<?php endif; ?>

	<div class="mfm-card">
		<div class="mfm-totals">
			<span><?php esc_html_e( 'Opening', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['opening'], 2 ) ); ?></span>
			<span><?php esc_html_e( 'Total In', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['in'], 2 ) ); ?></span>
			<span><?php esc_html_e( 'Total Out', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['out'], 2 ) ); ?></span>
			<span><strong><?php esc_html_e( 'Balance', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['balance'], 2 ) ); ?></strong></span>
		</div>
		<table class="widefat fixed striped">
			<thead><tr><th><?php esc_html_e( 'Date', 'mfm' ); ?></th><th><?php esc_html_e( 'Time', 'mfm' ); ?></th><th><?php esc_html_e( 'Type', 'mfm' ); ?></th><th><?php esc_html_e( 'Amount', 'mfm' ); ?></th><th><?php esc_html_e( 'Mode', 'mfm' ); ?></th><th><?php esc_html_e( 'Entry By', 'mfm' ); ?></th><th><?php esc_html_e( 'Description', 'mfm' ); ?></th><th><?php esc_html_e( 'Balance', 'mfm' ); ?></th><?php if ( $can_crud ) : ?><th><?php esc_html_e( 'Actions', 'mfm' ); ?></th><?php endif; ?></tr></thead>
			<tbody>
				<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r->trx_date ); ?></td>
					<td><?php echo esc_html( $r->trx_time ); ?></td>
					<td><?php echo esc_html( strtoupper( $r->trx_type ) ); ?></td>
					<td><?php echo esc_html( number_format( (float) $r->amount, 2 ) ); ?></td>
					<td><?php echo esc_html( $r->mode ); ?></td>
					<td><?php echo esc_html( $r->entry_by ); ?></td>
					<td><?php echo esc_html( wp_strip_all_tags( (string) $r->description ) ); ?></td>
					<td><strong><?php echo esc_html( number_format( (float) $r->balance, 2 ) ); ?></strong></td>
					<?php if ( $can_crud ) : ?>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete transaction?');">
							<?php wp_nonce_field( 'mfm_delete_trx' ); ?>
							<input type="hidden" name="action" value="mfm_delete_trx" />
							<input type="hidden" name="id" value="<?php echo esc_attr( $r->id ); ?>" />
							<input type="hidden" name="fund_id" value="<?php echo esc_attr( $fund_id ); ?>" />
							<button class="button button-link-delete" type="submit"><?php esc_html_e( 'Delete', 'mfm' ); ?></button>
						</form>
					</td>
					<?php endif; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php $total_pages = (int) ceil( $total / $per_page ); if ( $total_pages > 1 ) : ?>
		<div class="mfm-pagination">
			<?php for ( $i = 1; $i <= $total_pages; $i++ ) { $url = add_query_arg( array_merge( $_GET, [ 'paged' => $i ] ) ); echo $i === $page ? "<span class='current'>$i</span> " : "<a href='" . esc_url( $url ) . "'>$i</a> "; } ?>
		</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfm-export">
			<?php wp_nonce_field( 'mfm_export' ); ?>
			<input type="hidden" name="action" value="mfm_export" />
			<input type="hidden" name="fund_id" value="<?php echo esc_attr( $fund_id ); ?>" />
			<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>" />
			<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>" />
			<input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>" />
			<button class="button" name="format" value="csv" type="submit"><?php esc_html_e( 'Export CSV', 'mfm' ); ?></button>
			<button class="button" name="format" value="xls" type="submit"><?php esc_html_e( 'Export Excel', 'mfm' ); ?></button>
		</form>
	</div>
</div>