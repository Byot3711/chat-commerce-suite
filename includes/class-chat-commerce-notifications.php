<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chat_Commerce_Notifications {

	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'send_order_notification' ), 10, 4 );
	}

	public function send_order_notification( $order_id, $old_status, $new_status, $order ) {
		$settings = get_option( 'ccs_settings_notifications', array() );
		if ( empty( $settings['statuses'] ) || ! is_array( $settings['statuses'] ) ) {
			return;
		}
		$clean_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		if ( ! in_array( $clean_status, $settings['statuses'] ) ) {
			return;
		}

		$phone = $order->get_billing_phone();
		if ( empty( $phone ) ) {
			return;
		}

		$templates = get_option( 'ccs_settings_templates', array() );
		$template_key = 'order_' . $clean_status;
		$template = isset( $templates[ $template_key ] ) ? $templates[ $template_key ] : '';

		if ( empty( $template ) ) {
			return;
		}

		$replacements = array(
			'{first_name}'   => $order->get_billing_first_name(),
			'{last_name}'    => $order->get_billing_last_name(),
			'{order_number}' => $order->get_order_number(),
			'{order_total}'  => $order->get_total(),
		);
		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

		$api = new Chat_Commerce_API();
		$api->send_message( $phone, $message );
	}
}
