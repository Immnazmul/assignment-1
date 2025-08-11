<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Map CSV Columns', 'mfm' ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mfm-card">
		<?php wp_nonce_field( 'mfm_import_do' ); ?>
		<input type="hidden" name="action" value="mfm_import_do" />
		<input type="hidden" name="file" value="<?php echo esc_attr( $upload['file'] ); ?>" />
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Plugin Field', 'mfm' ); ?></th><th><?php esc_html_e( 'CSV Column', 'mfm' ); ?></th></tr>
			<?php
			$fields = [
				'fund' => __( 'Fund Name (optional if selecting a fund below)', 'mfm' ),
				'trx_date' => __( 'Date (YYYY-MM-DD)', 'mfm' ),
				'trx_time' => __( 'Time (HH:MM[:SS])', 'mfm' ),
				'trx_type' => __( 'Type (in/out)', 'mfm' ),
				'amount' => __( 'Amount', 'mfm' ),
				'description' => __( 'Description', 'mfm' ),
				'entry_by' => __( 'Entry By', 'mfm' ),
				'mode' => __( 'Mode', 'mfm' ),
			];
			foreach ( $fields as $key => $label ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $label ); ?></strong></td>
				<td>
					<select name="map[<?php echo esc_attr( $key ); ?>]"><option value=""><?php esc_html_e( '— Select —', 'mfm' ); ?></option>
						<?php foreach ( $header as $i => $col ) : ?><option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $col ); ?></option><?php endforeach; ?>
					</select>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<hr />
		<h3><?php esc_html_e( 'Fund Mapping', 'mfm' ); ?></h3>
		<p>
			<label><input type="radio" name="fund_mapping" value="use_selected" checked /> <?php esc_html_e( 'Import into selected fund:', 'mfm' ); ?></label>
			<select name="selected_fund">
				<?php foreach ( $funds as $f ) : ?><option value="<?php echo esc_attr( $f->id ); ?>"><?php echo esc_html( $f->name ); ?></option><?php endforeach; ?>
			</select>
		</p>
		<p>
			<label><input type="radio" name="fund_mapping" value="from_column" /> <?php esc_html_e( 'Read fund name from CSV column (and auto-create if missing)', 'mfm' ); ?></label>
		</p>
		<p><button class="button button-primary" type="submit"><?php esc_html_e( 'Import', 'mfm' ); ?></button></p>
	</form>
</div>