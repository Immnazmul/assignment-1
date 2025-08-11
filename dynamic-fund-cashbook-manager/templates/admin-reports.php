<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = DFCM_Export::get_funds();
?>
<div class="wrap container-fluid">
	<h1><?php esc_html_e( 'Reports', 'dfcm' ); ?></h1>
	<div class="card card-body mb-3">
		<h3><?php esc_html_e( 'Fund Balances', 'dfcm' ); ?></h3>
		<table class="table table-striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Fund', 'dfcm' ); ?></th>
					<th><?php esc_html_e( 'Total In', 'dfcm' ); ?></th>
					<th><?php esc_html_e( 'Total Out', 'dfcm' ); ?></th>
					<th><?php esc_html_e( 'Balance', 'dfcm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $funds as $f ) : $s = DFCM_Export::get_fund_summary( $f ); ?>
				<tr>
					<td><?php echo esc_html( $f ); ?></td>
					<td><?php echo esc_html( number_format( $s['in'], 2 ) ); ?></td>
					<td><?php echo esc_html( number_format( $s['out'], 2 ) ); ?></td>
					<td><strong><?php echo esc_html( number_format( $s['balance'], 2 ) ); ?></strong></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="card card-body">
		<h3><?php esc_html_e( 'This Month Summary (cached)', 'dfcm' ); ?></h3>
		<?php $key = 'dfcm_summary_' . date( 'Y_m' ); $sum = get_option( $key ); ?>
		<?php if ( $sum ) : ?>
			<p><?php echo esc_html( $sum['period'] ); ?></p>
			<table class="table table-sm">
				<thead><tr><th>Fund</th><th>In</th><th>Out</th><th>Balance</th></tr></thead>
				<tbody>
					<?php foreach ( $sum['funds'] as $name => $s ) : ?>
					<tr><td><?php echo esc_html( $name ); ?></td><td><?php echo esc_html( number_format( $s['in'], 2 ) ); ?></td><td><?php echo esc_html( number_format( $s['out'], 2 ) ); ?></td><td><?php echo esc_html( number_format( $s['balance'], 2 ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No cached summary yet. It will be generated monthly by cron.', 'dfcm' ); ?></p>
		<?php endif; ?>
	</div>
</div>