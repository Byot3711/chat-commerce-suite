<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_WhatsApp_API {

	private $phone_number_id;
	private $access_token;
	private $api_version;

	public function __construct() {
		$general = get_option( 'wcws_settings_general', array() );
		$this->phone_number_id = isset( $general['phone_number_id'] ) ? $general['phone_number_id'] : '';
		$this->access_token    = isset( $general['access_token'] ) ? $general['access_token'] : '';
		$this->api_version     = isset( $general['api_version'] ) ? $general['api_version'] : 'v17.0';
	}

	public function send_message( $to, $message ) {
		if ( empty( $this->phone_number_id ) || empty( $this->access_token ) ) {
			return new WP_Error( 'missing_credentials', __( 'WhatsApp API credentials are not configured.', 'wc-whatsapp-suite' ) );
		}

		$url = "https://graph.facebook.com/{$this->api_version}/{$this->phone_number_id}/messages";

		$body = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
			'type'              => 'text',
			'text'              => array(
				'body' => $message,
			),
		);

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			$this->log_message( $to, $message, 'out', 'sent' );
			return $data;
		}

		$this->log_message( $to, $message, 'out', 'failed' );
		return new WP_Error( 'api_error', isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'wc-whatsapp-suite' ) );
	}

	private function log_message( $phone, $message, $direction, $status ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wc_whatsapp_logs',
			array(
				'phone'     => $phone,
				'message'   => $message,
				'direction' => $direction,
				'status'    => $status,
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}
}
