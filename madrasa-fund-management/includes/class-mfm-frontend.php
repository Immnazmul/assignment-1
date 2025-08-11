<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MFM_Frontend {
	private static $instance = null;
	public static function get_instance(): self { if ( null === self::$instance ) self::$instance = new self(); return self::$instance; }
	private function __construct() { add_shortcode( 'mfm_report', [ $this, 'shortcode_report' ] ); add_shortcode( 'mfm_login', [ $this, 'shortcode_login' ] ); }

	public function shortcode_login(): string {
		if ( is_user_logged_in() ) return '<div class="mfm-card">' . esc_html__( 'You are already logged in.', 'mfm' ) . '</div>';
		$error = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['log'], $_POST['pwd'] ) ) {
			$creds = [ 'user_login' => sanitize_text_field( wp_unslash( $_POST['log'] ) ), 'user_password' => wp_unslash( $_POST['pwd'] ), 'remember' => ! empty( $_POST['rememberme'] ) ];
			$user = wp_signon( $creds, is_ssl() );
			if ( ! is_wp_error( $user ) ) return '<div class="mfm-card">' . esc_html__( 'Login successful.', 'mfm' ) . '</div>';
			$error = esc_html( $user->get_error_message() );
		}
		ob_start(); ?>
		<form method="post" class="mfm-card mfm-login">
			<h3><?php esc_html_e( 'Login', 'mfm' ); ?></h3>
			<?php if ( $error ) : ?><div class="mfm-error"><?php echo $error; ?></div><?php endif; ?>
			<label><?php esc_html_e( 'Username', 'mfm' ); ?><input type="text" name="log" required></label>
			<label><?php esc_html_e( 'Password', 'mfm' ); ?><input type="password" name="pwd" required></label>
			<label><input type="checkbox" name="rememberme" value="1"> <?php esc_html_e( 'Remember me', 'mfm' ); ?></label>
			<button type="submit"><?php esc_html_e( 'Login', 'mfm' ); ?></button>
		</form>
		<?php return (string) ob_get_clean();
	}

	public function shortcode_report(): string {
		if ( ! is_user_logged_in() ) return '<div class="mfm-card">' . esc_html__( 'Please log in to view reports.', 'mfm' ) . '</div>';
		if ( wp_style_is( 'mfm-frontend', 'registered' ) ) wp_enqueue_style( 'mfm-frontend' );
		$fund_id = absint( $_GET['fund_id'] ?? 0 );
		$type = sanitize_text_field( wp_unslash( $_GET['type'] ?? '' ) );
		$from = sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) );
		$to   = sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) );
		$funds = MFM_Export::get_funds();
		$summary = $fund_id ? MFM_Export::get_fund_summary( $fund_id, $from, $to, $type ) : [ 'opening'=>0,'in'=>0,'out'=>0,'balance'=>0 ];
		// Pagination
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
		$params = [ $fund_id ]; $where = ' WHERE fund_id = %d';
		if ( $type && in_array( $type, [ 'in','out' ], true ) ) { $where .= ' AND trx_type = %s'; $params[] = $type; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		$page = max( 1, (int) ( $_GET['rpage'] ?? 1 ) ); $per_page = 20; $offset = ( $page - 1 ) * $per_page;
		$total = $fund_id ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tx $where", $params ) ) : 0;
		$sql = "SELECT * FROM $tx $where ORDER BY trx_date DESC, trx_time DESC, id DESC LIMIT %d OFFSET %d";
		$q_params = array_merge( $params, [ $per_page, $offset ] );
		$rows = $fund_id ? $wpdb->get_results( $wpdb->prepare( $sql, $q_params ) ) : [];
		ob_start(); ?>
		<div class="mfm-card">
			<h3><?php esc_html_e( 'Madrasa Fund Report', 'mfm' ); ?></h3>
			<form method="get" class="mfm-grid">
				<label><?php esc_html_e( 'Fund', 'mfm' ); ?>
					<select name="fund_id" required>
						<option value="">—</option>
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
				<button class="button" type="submit"><?php esc_html_e( 'Filter', 'mfm' ); ?></button>
			</form>
			<div class="mfm-totals">
				<span><?php esc_html_e( 'Opening', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['opening'], 2 ) ); ?></span>
				<span><?php esc_html_e( 'Total In', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['in'], 2 ) ); ?></span>
				<span><?php esc_html_e( 'Total Out', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['out'], 2 ) ); ?></span>
				<span><strong><?php esc_html_e( 'Balance', 'mfm' ); ?>: <?php echo esc_html( number_format( $summary['balance'], 2 ) ); ?></strong></span>
			</div>
			<table class="mfm-table">
				<thead><tr><th><?php esc_html_e( 'Date', 'mfm' ); ?></th><th><?php esc_html_e( 'Time', 'mfm' ); ?></th><th><?php esc_html_e( 'Type', 'mfm' ); ?></th><th><?php esc_html_e( 'Amount', 'mfm' ); ?></th><th><?php esc_html_e( 'Mode', 'mfm' ); ?></th><th><?php esc_html_e( 'Entry By', 'mfm' ); ?></th><th><?php esc_html_e( 'Description', 'mfm' ); ?></th><th><?php esc_html_e( 'Balance', 'mfm' ); ?></th></tr></thead>
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
						<td><?php echo esc_html( number_format( (float) $r->balance, 2 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php $total_pages = (int) ceil( $total / $per_page ); if ( $total_pages > 1 ) : ?>
			<div class="mfm-pagination">
				<?php for ( $i = 1; $i <= $total_pages; $i++ ) { $url = add_query_arg( array_merge( $_GET, [ 'rpage' => $i ] ) ); echo $i === $page ? "<span class='current'>$i</span> " : "<a href='" . esc_url( $url ) . "'>$i</a> "; } ?>
			</div>
			<?php endif; ?>
		</div>
		<?php return (string) ob_get_clean();
	}
}