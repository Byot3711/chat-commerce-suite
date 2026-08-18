<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_WhatsApp_Suite {

	protected static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-api.php';
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-admin.php';
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-webhook.php';
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-abandoned-cart.php';
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-notifications.php';
		require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-frontend.php';
	}

	private function init_hooks() {
		register_activation_hook( WCWS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WCWS_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function activate() {
		$this->create_tables();
		update_option( 'wcws_version', WCWS_VERSION );
	}

	public function deactivate() {}

	public function load_textdomain() {
		load_plugin_textdomain( 'wc-whatsapp-suite', false, dirname( WCWS_PLUGIN_BASENAME ) . '/languages' );
	}

	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql_logs = "CREATE TABLE {$wpdb->prefix}wc_whatsapp_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			phone varchar(20) NOT NULL,
			message text NOT NULL,
			direction varchar(10) NOT NULL DEFAULT 'out',
			status varchar(20) NOT NULL DEFAULT 'sent',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql_carts = "CREATE TABLE {$wpdb->prefix}wc_whatsapp_carts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			cart_key varchar(64) NOT NULL,
			phone varchar(20) DEFAULT NULL,
			cart_data longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY cart_key (cart_key)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_logs );
		dbDelta( $sql_carts );
	}
}
