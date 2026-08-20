<?php
/**
 * Order notification integration.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Send configured order status notifications. */
class Chat_Commerce_Notifications {

	/** Register order status hooks. */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'send_order_notification' ), 10, 4 );
	}

	/**
	 * Send a notification when an order changes status.
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Previous order status.
	 * @param string   $new_status New order status.
	 * @param WC_Order $order      Order object.
	 */
	public function send_order_notification( $order_id, $old_status, $new_status, $order ) {
		$settings = get_option( 'ccs_settings_notifications', array() );
		if ( empty( $settings['statuses'] ) || ! is_array( $settings['statuses'] ) ) {
			return;
		}
		$clean_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		if ( ! in_array( $clean_status, $settings['statuses'], true ) ) {
			return;
		}

		$phone = $order->get_billing_phone();
		if ( empty( $phone ) ) {
			return;
		}

		$templates    = get_option( 'ccs_settings_templates', array() );
		$template_key = 'order_' . $clean_status;
		$template     = isset( $templates[ $template_key ] ) ? $templates[ $template_key ] : '';

		if ( empty( $template ) ) {
			return;
		}

		$replacements = array(
			'{first_name}'   => $order->get_billing_first_name(),
			'{last_name}'    => $order->get_billing_last_name(),
			'{order_number}' => $order->get_order_number(),
			'{order_total}'  => $order->get_total(),
		);
		$message      = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		$api = new Chat_Commerce_API();
		$api->send_message( $phone, $message );
	}
}
