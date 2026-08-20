<?php
/**
 * Main plugin bootstrap and lifecycle management.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Main plugin coordinator. */
class Chat_Commerce_Suite {

	/**
	 * Singleton instance.
	 *
	 * @var Chat_Commerce_Suite|null
	 */
	protected static $instance = null;

	/** Return the singleton plugin instance. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Bootstrap the plugin components. */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/** Load component classes. */
	private function includes() {
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-api.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-admin.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-webhook.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-abandoned-cart.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-notifications.php';
		require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-frontend.php';
	}

	/** Register plugin lifecycle and loading hooks. */
	private function init_hooks() {
		register_activation_hook( CCS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( CCS_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/** Create plugin tables and record the installed version. */
	public function activate() {
		$this->create_tables();
		update_option( 'ccs_version', CCS_VERSION );
	}

	/** Run plugin deactivation tasks. */
	public function deactivate() {}

	/** Load translations from the plugin languages directory. */
	public function load_textdomain() {
		load_plugin_textdomain( 'chat-commerce-suite', false, dirname( CCS_PLUGIN_BASENAME ) . '/languages' );
	}

	/** Create or update the plugin database tables. */
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
