<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FMP_SMS {
	public static function send_bulk( array $phone_numbers, string $message ): array {
		$api_key   = get_option( 'fmp_api_key', '' );
		$sender_id = get_option( 'fmp_sender_id', '' );

		$results = [ 'success' => [], 'failed' => [] ];
		if ( empty( $api_key ) || empty( $sender_id ) ) {
			return $results;
		}

		$endpoint = 'http://bulksmsbd.net/api/smsapi';

		$numbers = array_filter( array_map( 'sanitize_text_field', $phone_numbers ) );
		$chunks  = array_chunk( $numbers, 100 ); // Avoid too many numbers at once

		foreach ( $chunks as $chunk ) {
			$payload = [
				'api_key'  => $api_key,
				'senderid' => $sender_id,
				'number'   => implode( ',', $chunk ),
				'message'  => wp_strip_all_tags( $message ),
			];

			$response = wp_remote_post( $endpoint, [
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 20,
			] );

			if ( is_wp_error( $response ) ) {
				$results['failed'] = array_merge( $results['failed'], $chunk );
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( $code >= 200 && $code < 300 ) {
				$results['success'] = array_merge( $results['success'], $chunk );
			} else {
				$results['failed'] = array_merge( $results['failed'], $chunk );
			}
		}

		return $results;
	}

	public static function render_template( string $template, array $data ): string {
		$replacements = [
			'{name}'    => isset( $data['name'] ) ? $data['name'] : '',
			'{month}'   => isset( $data['month'] ) ? $data['month'] : '',
			'{amount}'  => isset( $data['amount'] ) ? $data['amount'] : '',
			'{whatsapp}'=> isset( $data['whatsapp'] ) ? $data['whatsapp'] : '',
		];
		return strtr( $template, $replacements );
	}
}