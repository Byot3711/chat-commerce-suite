<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WhatsApp_Commerce_Abandoned_Cart {

	public function __construct() {
		add_action( 'woocommerce_cart_updated', array( $this, 'track_cart' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_cart' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'track_cart' ) );
		add_action( 'wp_login', array( $this, 'track_cart_on_login' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'maybe_send_abandoned_cart_messages' ) );
	}

	public function track_cart() {
		if ( is_admin() || is_cart() || is_checkout() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		$cart_key = $this->get_cart_key();
		if ( ! $cart_key ) {
			return;
		}

		$cart_data = $cart->get_cart();
		$phone = $this->get_customer_phone();

		global $wpdb;
		$table = $wpdb->prefix . 'wacs_carts';

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table WHERE cart_key = %s", $cart_key ) );
		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'phone'      => $phone,
					'cart_data'  => maybe_serialize( $cart_data ),
					'status'     => 'pending',
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'cart_key'  => $cart_key,
					'phone'     => $phone,
					'cart_data' => maybe_serialize( $cart_data ),
					'status'    => 'pending',
				),
				array( '%s', '%s', '%s', '%s' )
			);
		}
	}

	public function track_cart_on_login( $user_login, $user ) {
		$cart_key = get_user_meta( $user->ID, '_wacs_cart_key', true );
		if ( $cart_key ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'wacs_carts',
				array( 'phone' => get_user_meta( $user->ID, 'billing_phone', true ) ),
				array( 'cart_key' => $cart_key ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

	public function maybe_send_abandoned_cart_messages() {
		$settings = get_option( 'wacs_settings_abandoned', array() );
		if ( ! isset( $settings['enabled'] ) || 'yes' !== $settings['enabled'] ) {
			return;
		}

		$delay_minutes = isset( $settings['delay_minutes'] ) ? absint( $settings['delay_minutes'] ) : 30;
		$cutoff = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $delay_minutes * MINUTE_IN_SECONDS );

		global $wpdb;
		$carts = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wacs_carts WHERE status = 'pending' AND updated_at < %s ORDER BY updated_at ASC LIMIT 10",
			$cutoff
		) );

		foreach ( $carts as $cart ) {
			$phone = $cart->phone;
			if ( empty( $phone ) ) {
				continue;
			}

			$cart_data = maybe_unserialize( $cart->cart_data );
			if ( empty( $cart_data ) ) {
				continue;
			}

			$cart_link = wc_get_cart_url();
			$customer_name = $this->get_customer_name_from_phone( $phone );
			$templates = get_option( 'wacs_settings_templates', array() );
			$template = isset( $templates['abandoned_cart'] ) ? $templates['abandoned_cart'] : __( 'Hi {first_name}! You left some items in your cart. Complete your order now and get free shipping: {cart_link}', 'whatsapp-commerce-suite' );

			$replacements = array(
				'{first_name}' => $customer_name,
				'{last_name}'  => '',
				'{cart_link}'  => $cart_link,
			);
			$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

			$api = new WhatsApp_Commerce_API();
			$result = $api->send_message( $phone, $message );

			if ( ! is_wp_error( $result ) ) {
				$wpdb->update(
					$wpdb->prefix . 'wacs_carts',
					array( 'status' => 'sent' ),
					array( 'id' => $cart->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	private function get_cart_key() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$meta_key = get_user_meta( $user_id, '_wacs_cart_key', true );
			if ( ! $meta_key ) {
				$meta_key = md5( uniqid( $user_id, true ) );
				update_user_meta( $user_id, '_wacs_cart_key', $meta_key );
			}
			return $meta_key;
		} else {
			if ( isset( WC()->session ) ) {
				$session_key = WC()->session->get( 'wacs_cart_key' );
				if ( ! $session_key ) {
					$session_key = md5( uniqid( '', true ) );
					WC()->session->set( 'wacs_cart_key', $session_key );
				}
				return $session_key;
			}
		}
		return false;
	}

	private function get_customer_phone() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$phone = get_user_meta( $user_id, 'billing_phone', true );
			return $phone ? $phone : '';
		} elseif ( isset( WC()->customer ) ) {
			return WC()->customer->get_billing_phone();
		}
		return '';
	}

	private function get_customer_name_from_phone( $phone ) {
		$users = get_users( array(
			'meta_key'   => 'billing_phone',
			'meta_value' => $phone,
			'number'     => 1,
		) );

		if ( ! empty( $users ) ) {
			return $users[0]->first_name;
		}
		return __( 'there', 'whatsapp-commerce-suite' );
	}
}
