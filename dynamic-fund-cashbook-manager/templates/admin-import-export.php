<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$funds = DFCM_Export::get_funds();
?>
<div class="wrap container-fluid">
	<h1><?php esc_html_e( 'Import / Export', 'dfcm' ); ?></h1>
	<div class="row g-3">
		<div class="col-md-6">
			<div class="card card-body">
				<h3><?php esc_html_e( 'Import CSV', 'dfcm' ); ?></h3>
				<p><?php esc_html_e( 'CSV columns: Date (YYYY-MM-DD), Type (in/out), Amount, Fund, Purpose, Method, Notes', 'dfcm' ); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'dfcm_import_csv' ); ?>
					<input type="hidden" name="action" value="dfcm_import_csv" />
					<input type="file" name="dfcm_csv" accept=".csv" required />
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Import', 'dfcm' ); ?></button>
				</form>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card card-body">
				<h3><?php esc_html_e( 'Export Report', 'dfcm' ); ?></h3>
				<form class="row g-2" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'dfcm_export_csv' ); ?>
					<input type="hidden" name="action" value="dfcm_export_csv" />
					<div class="col-md-4">
						<label class="form-label"><?php esc_html_e( 'Fund', 'dfcm' ); ?></label>
						<select name="fund_name" class="form-select">
							<option value=""><?php esc_html_e( 'All', 'dfcm' ); ?></option>
							<?php foreach ( $funds as $f ) : ?>
								<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-3">
						<label class="form-label"><?php esc_html_e( 'From', 'dfcm' ); ?></label>
						<input type="date" name="from" class="form-control" />
					</div>
					<div class="col-md-3">
						<label class="form-label"><?php esc_html_e( 'To', 'dfcm' ); ?></label>
						<input type="date" name="to" class="form-control" />
					</div>
					<div class="col-md-2 d-flex align-items-end">
						<button class="button" type="submit"><?php esc_html_e( 'Export CSV', 'dfcm' ); ?></button>
					</div>
				</form>
				<form class="row g-2 mt-2" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'dfcm_export_pdf' ); ?>
					<input type="hidden" name="action" value="dfcm_export_pdf" />
					<div class="col-md-4">
						<label class="form-label"><?php esc_html_e( 'Fund', 'dfcm' ); ?></label>
						<select name="fund_name" class="form-select">
							<option value=""><?php esc_html_e( 'All', 'dfcm' ); ?></option>
							<?php foreach ( $funds as $f ) : ?>
								<option value="<?php echo esc_attr( $f ); ?>"><?php echo esc_html( $f ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-3">
						<label class="form-label"><?php esc_html_e( 'From', 'dfcm' ); ?></label>
						<input type="date" name="from" class="form-control" />
					</div>
					<div class="col-md-3">
						<label class="form-label"><?php esc_html_e( 'To', 'dfcm' ); ?></label>
						<input type="date" name="to" class="form-control" />
					</div>
					<div class="col-md-2 d-flex align-items-end">
						<button class="button" type="submit"><?php esc_html_e( 'Export PDF', 'dfcm' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>