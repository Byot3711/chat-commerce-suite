<?php
/**
 * WhatsApp Cloud API integration.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Send and log WhatsApp messages. */
class Chat_Commerce_API {

	/**
	 * WhatsApp phone number ID.
	 *
	 * @var string
	 */
	private $phone_number_id;
	/**
	 * WhatsApp API access token.
	 *
	 * @var string
	 */
	private $access_token;
	/**
	 * Configured Graph API version.
	 *
	 * @var string
	 */
	private $api_version;

	/** Load API credentials from plugin settings. */
	public function __construct() {
		$general               = get_option( 'ccs_settings_general', array() );
		$this->phone_number_id = isset( $general['phone_number_id'] ) ? $general['phone_number_id'] : '';
		$this->access_token    = isset( $general['access_token'] ) ? $general['access_token'] : '';
		$this->api_version     = isset( $general['api_version'] ) ? $general['api_version'] : 'v17.0';
	}

	/**
	 * Send a text message through the WhatsApp Cloud API.
	 *
	 * @param string $to Recipient phone number.
	 * @param string $message Message body.
	 * @return array|WP_Error API response or error.
	 */
	public function send_message( $to, $message ) {
		if ( empty( $this->phone_number_id ) || empty( $this->access_token ) ) {
			return new WP_Error( 'missing_credentials', __( 'WhatsApp API credentials are not configured.', 'chat-commerce-suite' ) );
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

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

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
		return new WP_Error( 'api_error', isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'chat-commerce-suite' ) );
	}

	/**
	 * Store a message in the plugin log table.
	 *
	 * @param string $phone Phone number.
	 * @param string $message Message body.
	 * @param string $direction Message direction.
	 * @param string $status Delivery status.
	 */
	private function log_message( $phone, $message, $direction, $status ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ccs_logs',
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
