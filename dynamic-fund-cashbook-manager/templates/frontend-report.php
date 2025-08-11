<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = DFCM_Export::get_funds();
$fund  = isset( $_GET['fund'] ) ? sanitize_text_field( wp_unslash( $_GET['fund'] ) ) : '';
$type  = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
$from  = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
$to    = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
$rows  = DFCM_Export::get_transactions( $fund, $from, $to, $type );
$summary = DFCM_Export::get_fund_summary( $fund, $from, $to );
?>
<div class="container my-4">
	<h3><?php esc_html_e( 'Fund Report', 'dfcm' ); ?></h3>
	<form class="row g-2 mb-3" method="get">
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
		<div class="col-md-3 d-flex align-items-end">
			<button class="btn btn-secondary" type="submit"><?php esc_html_e( 'Filter', 'dfcm' ); ?></button>
		</div>
	</form>
	<div class="mb-2">
		<strong><?php esc_html_e( 'Total In', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['in'], 2 ) ); ?>
		<strong class="ms-3"><?php esc_html_e( 'Total Out', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['out'], 2 ) ); ?>
		<strong class="ms-3"><?php esc_html_e( 'Balance', 'dfcm' ); ?>:</strong> <?php echo esc_html( number_format( $summary['balance'], 2 ) ); ?>
	</div>
	<table id="dfcmFrontTable" class="display table table-striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date', 'dfcm' ); ?></th>
				<th><?php esc_html_e( 'Type', 'dfcm' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'dfcm' ); ?></th>
				<th><?php esc_html_e( 'Fund', 'dfcm' ); ?></th>
				<th><?php esc_html_e( 'Purpose', 'dfcm' ); ?></th>
				<th><?php esc_html_e( 'Method', 'dfcm' ); ?></th>
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
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<script>jQuery(function($){ $('#dfcmFrontTable').DataTable(); });</script>