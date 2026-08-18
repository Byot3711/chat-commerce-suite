<?php
/**
 * Plugin Name: WC WhatsApp Commerce Suite
 * Plugin URI:  https://github.com/Byot3711/wc-whatsapp-suite
 * Description: Transform your WooCommerce store into a WhatsApp sales channel with catalog, cart, abandoned cart recovery, and order notifications.
 * Version:     1.0.0
 * Author:      byot
 * Author URI:  https://github.com/Byot3711
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-whatsapp-suite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCWS_VERSION', '1.0.0' );
define( 'WCWS_PLUGIN_FILE', __FILE__ );
define( 'WCWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCWS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WCWS_PLUGIN_DIR . 'includes/class-wc-whatsapp-suite.php';

function wcws() {
	return WC_WhatsApp_Suite::instance();
}

wcws();
