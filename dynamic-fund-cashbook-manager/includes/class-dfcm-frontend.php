<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Frontend {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'fund_report', [ $this, 'shortcode_fund_report' ] );
	}

	private function is_authorized(): bool {
		$cookie = isset( $_COOKIE['dfcm_auth'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dfcm_auth'] ) ) : '';
		$pass = (string) get_option( 'dfcm_front_password', '' );
		if ( $pass === '' ) { return true; }
		$expected = hash( 'sha256', $pass . '|' . wp_get_session_token() . '|' . NONCE_SALT );
		return hash_equals( $expected, $cookie );
	}

	private function authorize_with_password( string $password ): bool {
		$set = false;
		$pass = (string) get_option( 'dfcm_front_password', '' );
		if ( $pass !== '' && hash_equals( $pass, $password ) ) {
			$val = hash( 'sha256', $pass . '|' . wp_get_session_token() . '|' . NONCE_SALT );
			setcookie( 'dfcm_auth', $val, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			$set = true;
		}
		return $set;
	}

	public function shortcode_fund_report( $atts ): string {
		wp_enqueue_style( 'dfcm-bootstrap' );
		wp_enqueue_style( 'dfcm-datatables' );
		wp_enqueue_style( 'dfcm-frontend' );
		wp_enqueue_script( 'dfcm-bootstrap' );
		wp_enqueue_script( 'dfcm-datatables' );
		wp_enqueue_script( 'dfcm-frontend' );

		$notice = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['dfcm_password'] ) ) {
			if ( $this->authorize_with_password( sanitize_text_field( wp_unslash( $_POST['dfcm_password'] ) ) ) ) {
				$notice = __( 'Access granted.', 'dfcm' );
			} else {
				$notice = __( 'Invalid password.', 'dfcm' );
			}
		}

		if ( ! $this->is_authorized() ) {
			ob_start();
			include DFCM_PLUGIN_DIR . 'templates/frontend-password.php';
			return (string) ob_get_clean();
		}

		ob_start();
		include DFCM_PLUGIN_DIR . 'templates/frontend-report.php';
		return (string) ob_get_clean();
	}
}