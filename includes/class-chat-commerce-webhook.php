<?php
/**
 * WhatsApp webhook endpoint and automated replies.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Process signed webhook requests from Meta. */
class Chat_Commerce_Webhook {

	/** Register webhook hooks. */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/** Register the public webhook REST route. */
	public function register_rest_routes() {
		register_rest_route(
			'chat-commerce-suite/v1',
			'/webhook',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Validate and process a webhook request.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return WP_REST_Response REST response.
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		if ( 'GET' === $request->get_method() ) {
			$hub_mode         = $request->get_param( 'hub_mode' );
			$hub_verify_token = $request->get_param( 'hub_verify_token' );
			$hub_challenge    = $request->get_param( 'hub_challenge' );

			$general      = get_option( 'ccs_settings_general', array() );
			$verify_token = isset( $general['verify_token'] ) ? $general['verify_token'] : '';

			if ( 'subscribe' === $hub_mode && ! empty( $verify_token ) && hash_equals( $verify_token, (string) $hub_verify_token ) ) {
				return new WP_REST_Response( $hub_challenge, 200 );
			}
			return new WP_REST_Response( 'Verification failed', 403 );
		}

		$general    = get_option( 'ccs_settings_general', array() );
		$app_secret = isset( $general['app_secret'] ) ? $general['app_secret'] : '';
		$signature  = $request->get_header( 'x-hub-signature-256' );
		if ( empty( $app_secret ) || ! $this->is_valid_signature( $request->get_body(), $signature, $app_secret ) ) {
			return new WP_REST_Response( 'Invalid signature', 403 );
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
					$text  = $message['text']['body'];
					$this->handle_incoming_message( $phone, $text );
				}
			}
		}

		return new WP_REST_Response( 'OK', 200 );
	}

	/**
	 * Validate the Meta HMAC signature.
	 *
	 * @param string $body Request body.
	 * @param string $signature Request signature.
	 * @param string $app_secret Meta app secret.
	 * @return bool Whether the signature is valid.
	 */
	private function is_valid_signature( $body, $signature, $app_secret ) {
		if ( 0 !== strpos( $signature, 'sha256=' ) ) {
			return false;
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', $body, $app_secret );
		return hash_equals( $expected, $signature );
	}

	/**
	 * Log and respond to an incoming message.
	 *
	 * @param string $phone Sender phone number.
	 * @param string $text Message text.
	 */
	private function handle_incoming_message( $phone, $text ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ccs_logs',
			array(
				'phone'     => $phone,
				'message'   => $text,
				'direction' => 'in',
				'status'    => 'received',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		$api           = new Chat_Commerce_API();
		$response_text = $this->generate_auto_reply( $text, $phone );
		if ( $response_text ) {
			$api->send_message( $phone, $response_text );
		}
	}

	/**
	 * Generate a rule-based reply for an incoming message.
	 *
	 * @param string $text Message text.
	 * @param string $phone Sender phone number.
	 * @return string Reply text.
	 */
	private function generate_auto_reply( $text, $phone ) {
		$site_name = get_bloginfo( 'name' );
		$text      = strtolower( trim( $text ) );

		if ( strpos( $text, 'help' ) !== false || strpos( $text, 'ajutor' ) !== false ) {
			return sprintf(
				/* Translators: %s is the site name. */
				__( "Welcome to %s!\nCommands:\n- Type 'status' to check your order\n- Type 'products' to see our catalog\n- Type 'contact' to reach support", 'chat-commerce-suite' ),
				$site_name
			);
		}

		if ( strpos( $text, 'status' ) !== false || strpos( $text, 'order' ) !== false ) {
			$order = $this->get_recent_order_by_phone( $phone );
			if ( $order ) {
				$status = wc_get_order_status_name( $order->get_status() );
				return sprintf(
					/* Translators: %1$s is the order number and %2$s is the order status. */
					__( 'Your order #%1$s is currently: %2$s', 'chat-commerce-suite' ),
					$order->get_order_number(),
					$status
				);
			}
			return __( 'No recent order found for this number. Please provide an order number.', 'chat-commerce-suite' );
		}

		if ( strpos( $text, 'product' ) !== false || strpos( $text, 'catalog' ) !== false ) {
			$products_link = wc_get_page_permalink( 'shop' );
			return sprintf(
				/* Translators: %s is the shop URL. */
				__( 'View our products at %s', 'chat-commerce-suite' ),
				$products_link
			);
		}

		if ( strpos( $text, 'contact' ) !== false || strpos( $text, 'support' ) !== false ) {
			$admin_email = get_option( 'admin_email' );
			return sprintf(
				/* Translators: %s is the support email address. */
				__( 'Contact us at %s', 'chat-commerce-suite' ),
				$admin_email
			);
		}

		return __( "I'm sorry, I didn't understand. Type 'help' for options.", 'chat-commerce-suite' );
	}

	/**
	 * Find the most recent order for a phone number.
	 *
	 * @param string $phone Phone number.
	 * @return WC_Order|false Most recent order or false.
	 */
	private function get_recent_order_by_phone( $phone ) {
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => '_billing_phone',
				'meta_value' => $phone,
				'orderby'    => 'date',
				'order'      => 'DESC',
				'return'     => 'objects',
			)
		);

		return ! empty( $orders ) ? $orders[0] : false;
	}
}
