<?php
/**
 * Remove plugin data during uninstall.
 *
 * @package Chat_Commerce_Suite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'ccs_logs',
	$wpdb->prefix . 'ccs_carts',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are generated from the site prefix and fixed plugin suffixes.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

$options = array(
	'ccs_settings_general',
	'ccs_settings_abandoned',
	'ccs_settings_notifications',
	'ccs_settings_catalog',
	'ccs_settings_templates',
	'ccs_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
