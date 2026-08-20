<?php
/**
 * Abandoned cart tracking and recovery.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Handle cart persistence and recovery messages. */
class Chat_Commerce_Abandoned_Cart {

	/** Register cart tracking hooks. */
	public function __construct() {
		add_action( 'woocommerce_cart_updated', array( $this, 'track_cart' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'track_cart' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'track_cart' ) );
		add_action( 'wp_login', array( $this, 'track_cart_on_login' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'maybe_send_abandoned_cart_messages' ) );
	}

	/** Persist the current cart for possible recovery. */
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
		$phone     = $this->get_customer_phone();

		global $wpdb;
		$table = $wpdb->prefix . 'ccs_carts';

		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM %i WHERE cart_key = %s', $table, $cart_key ) );
		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'phone'      => $phone,
					'cart_data'  => maybe_serialize( $cart_data ),
					'status'     => 'pending',
					'updated_at' => current_time( 'mysql', true ),
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

	/**
	 * Attach a logged-in customer's phone number to their cart.
	 *
	 * @param string  $user_login User login name.
	 * @param WP_User $user Logged-in user.
	 */
	public function track_cart_on_login( $user_login, $user ) {
		$cart_key = get_user_meta( $user->ID, '_ccs_cart_key', true );
		if ( $cart_key ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'ccs_carts',
				array( 'phone' => get_user_meta( $user->ID, 'billing_phone', true ) ),
				array( 'cart_key' => $cart_key ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

	/** Send messages for carts that have passed the configured delay. */
	public function maybe_send_abandoned_cart_messages() {
		$settings = get_option( 'ccs_settings_abandoned', array() );
		if ( ! isset( $settings['enabled'] ) || 'yes' !== $settings['enabled'] ) {
			return;
		}

		$delay_minutes = isset( $settings['delay_minutes'] ) ? absint( $settings['delay_minutes'] ) : 30;
		$cutoff        = gmdate( 'Y-m-d H:i:s', time() - $delay_minutes * MINUTE_IN_SECONDS );

		global $wpdb;
		$carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ccs_carts WHERE status = 'pending' AND updated_at < %s ORDER BY updated_at ASC LIMIT 10",
				$cutoff
			)
		);

		foreach ( $carts as $cart ) {
			$phone = $cart->phone;
			if ( empty( $phone ) ) {
				continue;
			}

			$cart_data = maybe_unserialize( $cart->cart_data );
			if ( empty( $cart_data ) ) {
				continue;
			}

			$cart_link     = wc_get_cart_url();
			$customer_name = $this->get_customer_name_from_phone( $phone );
			$templates     = get_option( 'ccs_settings_templates', array() );
			$template      = isset( $templates['abandoned_cart'] ) ? $templates['abandoned_cart'] : __( 'Hi {first_name}! You left some items in your cart. Complete your order now and get free shipping: {cart_link}', 'chat-commerce-suite' );

			$replacements = array(
				'{first_name}' => $customer_name,
				'{last_name}'  => '',
				'{cart_link}'  => $cart_link,
			);
			$message      = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );

			$api    = new Chat_Commerce_API();
			$result = $api->send_message( $phone, $message );

			if ( ! is_wp_error( $result ) ) {
				$wpdb->update(
					$wpdb->prefix . 'ccs_carts',
					array( 'status' => 'sent' ),
					array( 'id' => $cart->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/** Return the stable cart identifier for the current visitor. */
	private function get_cart_key() {
		if ( is_user_logged_in() ) {
			$user_id  = get_current_user_id();
			$meta_key = get_user_meta( $user_id, '_ccs_cart_key', true );
			if ( ! $meta_key ) {
				$meta_key = md5( uniqid( $user_id, true ) );
				update_user_meta( $user_id, '_ccs_cart_key', $meta_key );
			}
			return $meta_key;
		} elseif ( isset( WC()->session ) ) {
				$session_key = WC()->session->get( 'ccs_cart_key' );
			if ( ! $session_key ) {
				$session_key = md5( uniqid( '', true ) );
				WC()->session->set( 'ccs_cart_key', $session_key );
			}
				return $session_key;
		}
		return false;
	}

	/** Return the current customer's billing phone number. */
	private function get_customer_phone() {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$phone   = get_user_meta( $user_id, 'billing_phone', true );
			return $phone ? $phone : '';
		} elseif ( isset( WC()->customer ) ) {
			return WC()->customer->get_billing_phone();
		}
		return '';
	}

	/**
	 * Find a customer's first name from their billing phone number.
	 *
	 * @param string $phone Phone number.
	 * @return string Customer first name or fallback text.
	 */
	private function get_customer_name_from_phone( $phone ) {
		$users = get_users(
			array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $phone,
				'number'     => 1,
			)
		);

		if ( ! empty( $users ) ) {
			return $users[0]->first_name;
		}
		return __( 'there', 'chat-commerce-suite' );
	}
}
