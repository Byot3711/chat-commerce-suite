<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chat_Commerce_Suite {

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
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-api.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-admin.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-webhook.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-abandoned-cart.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-notifications.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-frontend.php';
	}

	private function init_hooks() {
		register_activation_hook( CCS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( CCS_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function activate() {
		$this->create_tables();
		update_option( 'ccs_version', CCS_VERSION );
	}

	public function deactivate() {}

	public function load_textdomain() {
		load_plugin_textdomain( 'chat-commerce-suite', false, dirname( CCS_PLUGIN_BASENAME ) . '/languages' );
	}

	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql_logs = "CREATE TABLE {$wpdb->prefix}ccs_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			phone varchar(20) NOT NULL,
			message text NOT NULL,
			direction varchar(10) NOT NULL DEFAULT 'out',
			status varchar(20) NOT NULL DEFAULT 'sent',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		$sql_carts = "CREATE TABLE {$wpdb->prefix}ccs_carts (
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
