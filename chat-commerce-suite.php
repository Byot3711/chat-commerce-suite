<?php
/**
 * Plugin Name: Chat Commerce Suite
 * Plugin URI:  https://github.com/Byot3711/whatsapp-suite
 * Description: Transform your WooCommerce store into a WhatsApp sales channel with catalog, cart, abandoned cart recovery, and order notifications.
 * Version:     1.1.0
 * Author:      byot
 * Author URI:  https://github.com/Byot3711
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: chat-commerce-suite
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 11.0
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CCS_VERSION', '1.1.0' );
define( 'CCS_PLUGIN_FILE', __FILE__ );
define( 'CCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once CCS_PLUGIN_DIR . 'includes/class-chat-commerce-suite.php';

/**
 * Return the singleton plugin instance.
 *
 * @return Chat_Commerce_Suite
 */
function ccs() {
	return Chat_Commerce_Suite::instance();
}

ccs();
