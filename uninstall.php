<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'wacs_logs',
	$wpdb->prefix . 'wacs_carts',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

$options = array(
	'wacs_settings_general',
	'wacs_settings_abandoned',
	'wacs_settings_notifications',
	'wacs_settings_catalog',
	'wacs_settings_templates',
	'wacs_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
