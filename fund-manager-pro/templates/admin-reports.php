<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Reports', 'fund-manager-pro' ); ?></h1>

	<canvas id="fmpMonthlyChart" height="100"></canvas>
	<canvas id="fmpYearlyChart" height="100" style="margin-top:30px"></canvas>

	<script type="application/json" id="fmpReportsData">
		<?php echo wp_json_encode( [ 'monthly' => $payments, 'yearly' => $yearly ] ); ?>
	</script>
</div>