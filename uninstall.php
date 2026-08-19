<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'ccs_logs',
	$wpdb->prefix . 'ccs_carts',
);

foreach ( $tables as $table ) {
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
