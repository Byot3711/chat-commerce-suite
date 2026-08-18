<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_WhatsApp_Webhook {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function register_rest_routes() {
		register_rest_route( 'wc-whatsapp-suite/v1', '/webhook', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( $this, 'handle_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function handle_webhook( WP_REST_Request $request ) {
		if ( 'GET' === $request->get_method() ) {
			$hub_mode = $request->get_param( 'hub_mode' );
			$hub_verify_token = $request->get_param( 'hub_verify_token' );
			$hub_challenge = $request->get_param( 'hub_challenge' );

			$general = get_option( 'wcws_settings_general', array() );
			$verify_token = isset( $general['verify_token'] ) ? $general['verify_token'] : '';

			if ( 'subscribe' === $hub_mode && $hub_verify_token === $verify_token ) {
				return new WP_REST_Response( $hub_challenge, 200 );
			}
			return new WP_REST_Response( 'Verification failed', 403 );
		}

		$body = $request->get_body();
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['entry'][0]['changes'][0]['value'] ) ) {
			return new WP_REST_Response( 'Invalid payload', 400 );
		}

		$value = $data['entry'][0]['changes'][0]['value'];

		if ( isset( $value['messages'] ) && is_array( $value['messages'] ) ) {
			foreach ( $value['messages'] as $message ) {
				if ( isset( $message['text']['body'] ) && isset( $message['from'] ) ) {
					$phone = $message['from'];
					$text = $message['text']['body'];
					$this->handle_incoming_message( $phone, $text );
				}
			}
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	private function handle_incoming_message( $phone, $text ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wc_whatsapp_logs',
			array(
				'phone'     => $phone,
				'message'   => $text,
				'direction' => 'in',
				'status'    => 'received',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		$api = new WC_WhatsApp_API();
		$response_text = $this->generate_auto_reply( $text, $phone );
		if ( $response_text ) {
			$api->send_message( $phone, $response_text );
		}
	}

	private function generate_auto_reply( $text, $phone ) {
		$site_name = get_bloginfo( 'name' );
		$text = strtolower( trim( $text ) );

		if ( strpos( $text, 'help' ) !== false || strpos( $text, 'ajutor' ) !== false ) {
			return sprintf(
				__( "Welcome to %s!\nCommands:\n- Type 'status' to check your order\n- Type 'products' to see our catalog\n- Type 'contact' to reach support", 'wc-whatsapp-suite' ),
				$site_name
			);
		}

		if ( strpos( $text, 'status' ) !== false || strpos( $text, 'order' ) !== false ) {
			$order = $this->get_recent_order_by_phone( $phone );
			if ( $order ) {
				$status = wc_get_order_status_name( $order->get_status() );
				return sprintf(
					__( "Your order #%s is currently: %s", 'wc-whatsapp-suite' ),
					$order->get_order_number(),
					$status
				);
			}
			return __( 'No recent order found for this number. Please provide an order number.', 'wc-whatsapp-suite' );
		}

		if ( strpos( $text, 'product' ) !== false || strpos( $text, 'catalog' ) !== false ) {
			$products_link = wc_get_page_permalink( 'shop' );
			return sprintf(
				__( "View our products at %s", 'wc-whatsapp-suite' ),
				$products_link
			);
		}

		if ( strpos( $text, 'contact' ) !== false || strpos( $text, 'support' ) !== false ) {
			$admin_email = get_option( 'admin_email' );
			return sprintf(
				__( "Contact us at %s", 'wc-whatsapp-suite' ),
				$admin_email
			);
		}

		return __( "I'm sorry, I didn't understand. Type 'help' for options.", 'wc-whatsapp-suite' );
	}

	private function get_recent_order_by_phone( $phone ) {
		$orders = wc_get_orders( array(
			'limit'      => 1,
			'meta_key'   => '_billing_phone',
			'meta_value' => $phone,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'return'     => 'objects',
		) );

		return ! empty( $orders ) ? $orders[0] : false;
	}
}
