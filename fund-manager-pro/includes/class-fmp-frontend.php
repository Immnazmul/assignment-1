<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FMP_Frontend {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'fmp_member_payments', [ $this, 'shortcode_member_payments' ] );
	}

	public function shortcode_member_payments( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return esc_html__( 'Please log in to view your payment history.', 'fund-manager-pro' );
		}

		$current_user = wp_get_current_user();
		if ( ! in_array( 'fmp_member', (array) $current_user->roles, true ) && ! current_user_can( 'fmp_view_own' ) ) {
			return esc_html__( 'You do not have permission to view this.', 'fund-manager-pro' );
		}

		global $wpdb;
		$members_table  = $wpdb->prefix . 'fmp_members';
		$payments_table = $wpdb->prefix . 'fmp_payments';

		$member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $members_table WHERE user_id = %d", get_current_user_id() ) );
		if ( ! $member ) {
			return esc_html__( 'Member record not found.', 'fund-manager-pro' );
		}

		$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $payments_table WHERE member_id=%d AND year=%d ORDER BY month ASC", $member->id, $year ) );

		ob_start();
		?>
		<div class="fmp-member-history">
			<h3><?php echo esc_html( sprintf( __( 'Payment History for %1$s (%2$d)', 'fund-manager-pro' ), $member->name, $year ) ); ?></h3>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Month', 'fund-manager-pro' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'fund-manager-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'fund-manager-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, (int) $r->month, 1 ) ) ); ?></td>
							<td><?php echo esc_html( number_format( (float) $r->amount, 2 ) ); ?></td>
							<td><?php echo $r->paid ? '<span style="color:green">' . esc_html__( 'Paid', 'fund-manager-pro' ) . '</span>' : '<span style="color:red">' . esc_html__( 'Unpaid', 'fund-manager-pro' ) . '</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}