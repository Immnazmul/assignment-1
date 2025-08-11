<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = DFCM_Export::get_funds();
$fund  = isset( $_GET['fund'] ) ? sanitize_text_field( wp_unslash( $_GET['fund'] ) ) : '';
$type  = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
$from  = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$to    = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
$rows  = DFCM_Export::get_transactions( $fund, $from, $to, $type );
$summary = DFCM_Export::get_fund_summary( $fund, $from, $to );
?>
<div class="wrap container-fluid">
	<h1><?php esc_html_e( 'Transactions', 'dfcm' ); ?></h1>

	<div class="row g-3 mb-3">
		<div class="col-md-3">
			<form class="card card-body" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'dfcm_save_trx' ); ?>
				<input type="hidden" name="action" value="dfcm_save_trx" />
				<h5><?php esc_html_e( 'Add / Edit Transaction', 'dfcm' ); ?></h5>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Fund', 'dfcm' ); ?></label>
					<select name="fund_name" class="form-select" required>
						<?php foreach ( $funds as $f ) : ?>
							<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Date', 'dfcm' ); ?></label>
					<input type="date" name="trx_date" class="form-control" required />
				</div>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Type', 'dfcm' ); ?></label>
					<select name="trx_type" class="form-select" required>
						<option value="in"><?php esc_html_e( 'Cash In', 'dfcm' ); ?></option>
						<option value="out"><?php esc_html_e( 'Cash Out', 'dfcm' ); ?></option>
					</select>
				</div>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Amount', 'dfcm' ); ?></label>
					<input type="number" step="0.01" name="amount" class="form-control" required />
				</div>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Purpose / Description', 'dfcm' ); ?></label>
					<textarea name="purpose" class="form-control" rows="2"></textarea>
				</div>
				<div class="mb-2">
					<label class="form-label"><?php esc_html_e( 'Payment Method', 'dfcm' ); ?></label>
					<input type="text" name="payment_method" class="form-control" />
				</div>
				<div class="mb-3">
					<label class="form-label"><?php esc_html_e( 'Notes', 'dfcm' ); ?></label>
					<textarea name="notes" class="form-control" rows="2"></textarea>
				</div>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Save', 'dfcm' ); ?></button>
			</form>
		</div>
		<div class="col-md-9">
			<div class="card card-body mb-3">
				<form class="row g-2" method="get">
					<input type="hidden" name="page" value="dfcm-transactions" />
					<div class="col-md-3">
						<label class="form-label"><?php esc_html_e( 'Fund', 'dfcm' ); ?></label>
						<select name="fund" class="form-select">
							<option value=""><?php esc_html_e( 'All', 'dfcm' ); ?></option>
							<?php foreach ( $funds as $f ) : ?>
								<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $fund, $f ); ?>><?php echo esc_html( $f ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label"><?php esc_html_e( 'Type', 'dfcm' ); ?></label>
						<select name="type" class="form-select">
							<option value=""><?php esc_html_e( 'All', 'dfcm' ); ?></option>
							<option value="in" <?php selected( $type, 'in' ); ?>><?php esc_html_e( 'Cash In', 'dfcm' ); ?></option>
							<option value="out" <?php selected( $type, 'out' ); ?>><?php esc_html_e( 'Cash Out', 'dfcm' ); ?></option>
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label"><?php esc_html_e( 'From', 'dfcm' ); ?></label>
						<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" class="form-control" />
					</div>
					<div class="col-md-2">
						<label class="form-label"><?php esc_html_e( 'To', 'dfcm' ); ?></label>
						<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" class="form-control" />
					</div>
					<div class="col-md-3 d-flex align-items-end gap-2">
						<button class="button" type="submit"><?php esc_html_e( 'Filter', 'dfcm' ); ?></button>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'dfcm_export_csv' ); ?>
							<input type="hidden" name="action" value="dfcm_export_csv" />
							<input type="hidden" name="fund_name" value="<?php echo esc_attr( $fund ); ?>" />
							<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>" />
							<input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>" />
							<button class="button" type="submit"><?php esc_html_e( 'Export CSV', 'dfcm' ); ?></button>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'dfcm_export_pdf' ); ?>
							<input type="hidden" name="action" value="dfcm_export_pdf" />
							<input type="hidden" name="fund_name" value="<?php echo esc_attr( $fund ); ?>" />
							<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>" />
							<input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>" />
							<button class="button" type="submit"><?php esc_html_e( 'Export PDF', 'dfcm' ); ?></button>
						</form>
					</div>
				</form>
			</div>

			<div class="card card-body">
				<div class="row mb-3">
					<div class="col">
						<strong><?php esc_html_e( 'Total In', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['in'], 2 ) ); ?>
						<strong class="ms-3"><?php esc_html_e( 'Total Out', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['out'], 2 ) ); ?>
						<strong class="ms-3"><?php esc_html_e( 'Balance', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['balance'], 2 ) ); ?>
					</div>
				</div>
				<table id="dfcmTable" class="display table table-striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Type', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Fund', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Purpose', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Method', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'dfcm' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'dfcm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r->trx_date ); ?></td>
								<td><?php echo esc_html( strtoupper( $r->trx_type ) ); ?></td>
								<td><?php echo esc_html( number_format( (float) $r->amount, 2 ) ); ?></td>
								<td><?php echo esc_html( $r->fund_name ); ?></td>
								<td><?php echo esc_html( wp_strip_all_tags( (string) $r->purpose ) ); ?></td>
								<td><?php echo esc_html( $r->payment_method ); ?></td>
								<td><?php echo esc_html( wp_strip_all_tags( (string) $r->notes ) ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete this transaction?');">
										<?php wp_nonce_field( 'dfcm_delete_trx' ); ?>
										<input type="hidden" name="action" value="dfcm_delete_trx" />
										<input type="hidden" name="id" value="<?php echo esc_attr( $r->id ); ?>" />
										<button class="button button-link-delete" type="submit"><?php esc_html_e( 'Delete', 'dfcm' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
	jQuery(function($){ $('#dfcmTable').DataTable(); });
</script>